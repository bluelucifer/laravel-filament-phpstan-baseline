<?php
/**
 * PHPStan Baseline Pattern Optimizer
 * 
 * This script identifies and optimizes baseline patterns:
 * - Finds duplicate patterns across files
 * - Optimizes complex regex patterns
 * - Suggests performance improvements
 * - Creates consolidated baseline files
 */

declare(strict_types=1);

class PatternOptimizer
{
    private string $baselineDir;
    private array $allPatterns = [];
    private array $duplicates = [];
    private array $optimizations = [];
    
    public function __construct(string $baselineDir)
    {
        $this->baselineDir = rtrim($baselineDir, '/');
    }
    
    public function analyze(): array
    {
        $files = glob($this->baselineDir . '/*.neon');
        
        foreach ($files as $file) {
            $this->extractPatterns($file);
        }
        
        $this->findDuplicates();
        $this->findOptimizations();
        
        return [
            'total_files' => count($files),
            'total_patterns' => array_sum(array_map('count', $this->allPatterns)),
            'unique_patterns' => count($this->getUniquePatterns()),
            'duplicates' => $this->duplicates,
            'optimizations' => $this->optimizations,
        ];
    }
    
    private function extractPatterns(string $file): void
    {
        $filename = basename($file);
        $content = file_get_contents($file);
        
        if (!$content) {
            return;
        }
        
        $this->allPatterns[$filename] = [];
        
        // Extract patterns more accurately
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Look for pattern lines starting with '-'
            if (preg_match('/^\s*-\s*[\'"]?#(.+)#[\'"]?\s*$/', $line, $matches)) {
                $pattern = '#' . $matches[1] . '#';
                $this->allPatterns[$filename][] = $pattern;
            }
        }
    }
    
    private function getUniquePatterns(): array
    {
        $unique = [];
        
        foreach ($this->allPatterns as $filename => $patterns) {
            foreach ($patterns as $pattern) {
                if (!isset($unique[$pattern])) {
                    $unique[$pattern] = [];
                }
                $unique[$pattern][] = $filename;
            }
        }
        
        return $unique;
    }
    
    private function findDuplicates(): void
    {
        $unique = $this->getUniquePatterns();
        
        foreach ($unique as $pattern => $files) {
            if (count($files) > 1) {
                $this->duplicates[$pattern] = [
                    'files' => $files,
                    'count' => count($files),
                    'complexity' => $this->calculateComplexity($pattern),
                ];
            }
        }
        
        // Sort by most duplicated
        uasort($this->duplicates, fn($a, $b) => $b['count'] - $a['count']);
    }
    
    private function calculateComplexity(string $pattern): int
    {
        $complexity = 0;
        
        // Count regex complexity indicators
        $complexity += substr_count($pattern, '+');
        $complexity += substr_count($pattern, '*');
        $complexity += substr_count($pattern, '?');
        $complexity += substr_count($pattern, '[');
        $complexity += substr_count($pattern, '(');
        $complexity += substr_count($pattern, '|');
        $complexity += substr_count($pattern, '\\');
        
        return $complexity;
    }
    
    private function findOptimizations(): void
    {
        $unique = $this->getUniquePatterns();
        
        foreach ($unique as $pattern => $files) {
            $complexity = $this->calculateComplexity($pattern);
            
            // Flag very complex patterns
            if ($complexity > 20) {
                $this->optimizations['complex'][] = [
                    'pattern' => $pattern,
                    'complexity' => $complexity,
                    'files' => $files,
                    'suggestion' => 'Consider breaking down this complex pattern',
                ];
            }
            
            // Look for potentially inefficient patterns
            if (strpos($pattern, '.+') !== false && strpos($pattern, '.*') !== false) {
                $this->optimizations['inefficient'][] = [
                    'pattern' => $pattern,
                    'files' => $files,
                    'suggestion' => 'Mix of .+ and .* can be inefficient',
                ];
            }
            
            // Look for patterns that could be more specific
            if (substr_count($pattern, '.+') > 2) {
                $this->optimizations['vague'][] = [
                    'pattern' => $pattern,
                    'files' => $files,
                    'suggestion' => 'Multiple .+ wildcards could be more specific',
                ];
            }
        }
    }
    
    public function generateOptimizedBaselines(): array
    {
        $commonPatterns = [];
        $fileSpecificPatterns = [];
        
        // Identify common patterns (in 3+ files)
        foreach ($this->duplicates as $pattern => $data) {
            if ($data['count'] >= 3) {
                $commonPatterns[] = $pattern;
            }
        }
        
        // Group remaining patterns by file
        $unique = $this->getUniquePatterns();
        foreach ($unique as $pattern => $files) {
            if (!in_array($pattern, $commonPatterns)) {
                foreach ($files as $file) {
                    $fileSpecificPatterns[$file][] = $pattern;
                }
            }
        }
        
        return [
            'common' => $commonPatterns,
            'file_specific' => $fileSpecificPatterns,
        ];
    }
    
    public function generateReport(): string
    {
        $data = $this->analyze();
        
        $report = "# PHPStan Baseline Optimization Report\n\n";
        $report .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        // Summary
        $report .= "## 📊 Summary\n\n";
        $report .= "- **Total Files**: {$data['total_files']}\n";
        $report .= "- **Total Patterns**: {$data['total_patterns']}\n";
        $report .= "- **Unique Patterns**: {$data['unique_patterns']}\n";
        $report .= "- **Duplicate Patterns**: " . count($data['duplicates']) . "\n";
        $duplicatePercent = count($data['duplicates']) > 0 ? 
            round((count($data['duplicates']) / $data['unique_patterns']) * 100, 2) : 0;
        $report .= "- **Duplication Rate**: {$duplicatePercent}%\n\n";
        
        // Top duplicates
        if (!empty($data['duplicates'])) {
            $report .= "## 🔄 Most Duplicated Patterns\n\n";
            
            $topDuplicates = array_slice($data['duplicates'], 0, 10, true);
            foreach ($topDuplicates as $pattern => $info) {
                $fileList = implode(', ', $info['files']);
                $report .= "### Duplicated {$info['count']} times\n";
                $report .= "**Pattern**: `{$pattern}`\n";
                $report .= "**Files**: {$fileList}\n";
                $report .= "**Complexity**: {$info['complexity']}\n\n";
            }
        }
        
        // Optimization suggestions
        if (!empty($data['optimizations'])) {
            $report .= "## 🚀 Optimization Opportunities\n\n";
            
            if (isset($data['optimizations']['complex'])) {
                $report .= "### Complex Patterns\n\n";
                foreach ($data['optimizations']['complex'] as $opt) {
                    $report .= "- **Pattern**: `{$opt['pattern']}`\n";
                    $report .= "  - Complexity: {$opt['complexity']}\n";
                    $report .= "  - Files: " . implode(', ', $opt['files']) . "\n";
                    $report .= "  - Suggestion: {$opt['suggestion']}\n\n";
                }
            }
            
            if (isset($data['optimizations']['inefficient'])) {
                $report .= "### Potentially Inefficient Patterns\n\n";
                foreach ($data['optimizations']['inefficient'] as $opt) {
                    $report .= "- **Pattern**: `{$opt['pattern']}`\n";
                    $report .= "  - Files: " . implode(', ', $opt['files']) . "\n";
                    $report .= "  - Suggestion: {$opt['suggestion']}\n\n";
                }
            }
        }
        
        // Recommendations
        $report .= "## 💡 Recommendations\n\n";
        
        if (count($data['duplicates']) > 0) {
            $report .= "### 1. Create Common Baseline Files\n\n";
            $report .= "Extract frequently duplicated patterns into shared baseline files:\n\n";
            $report .= "```neon\n";
            $report .= "# baselines/common-patterns.neon\n";
            $report .= "parameters:\n";
            $report .= "    ignoreErrors:\n";
            
            $commonPatterns = array_slice($data['duplicates'], 0, 5, true);
            foreach ($commonPatterns as $pattern => $info) {
                if ($info['count'] >= 3) {
                    $report .= "        - '{$pattern}'\n";
                }
            }
            $report .= "```\n\n";
        }
        
        $report .= "### 2. Organize by PHPStan Level\n\n";
        $report .= "Consider organizing patterns by PHPStan level for better maintainability:\n\n";
        $report .= "- `baselines/level-0-2.neon` - Basic patterns\n";
        $report .= "- `baselines/level-3-5.neon` - Moderate patterns\n";
        $report .= "- `baselines/level-6-8.neon` - Strict patterns\n";
        $report .= "- `baselines/level-9-10.neon` - Maximum strictness\n\n";
        
        $report .= "### 3. Performance Testing\n\n";
        $report .= "Run PHPStan with different baseline configurations to measure performance impact:\n\n";
        $report .= "```bash\n";
        $report .= "# Test original configuration\n";
        $report .= "time vendor/bin/phpstan analyse\n\n";
        $report .= "# Test optimized configuration\n";
        $report .= "time vendor/bin/phpstan analyse --configuration=phpstan-optimized.neon\n";
        $report .= "```\n\n";
        
        return $report;
    }
}

// CLI usage
if (php_sapi_name() === 'cli') {
    $baselineDir = $argv[1] ?? __DIR__ . '/../baselines';
    
    if (!is_dir($baselineDir)) {
        echo "Error: Baseline directory not found: {$baselineDir}\n";
        exit(1);
    }
    
    $optimizer = new PatternOptimizer($baselineDir);
    echo $optimizer->generateReport();
}