<?php
/**
 * PHPStan Baseline Pattern Analysis Tool
 * 
 * This script analyzes baseline files to identify:
 * - Duplicate patterns across files
 * - Complex regex patterns that could be optimized
 * - Performance metrics and statistics
 * - Recommendations for optimization
 */

declare(strict_types=1);

class BaselineAnalyzer
{
    private string $baselineDir;
    private array $patterns = [];
    private array $duplicates = [];
    private array $statistics = [];
    
    public function __construct(string $baselineDir)
    {
        $this->baselineDir = rtrim($baselineDir, '/');
    }
    
    public function analyze(): array
    {
        $files = glob($this->baselineDir . '/*.neon');
        
        foreach ($files as $file) {
            $this->analyzeFile($file);
        }
        
        $this->findDuplicates();
        $this->analyzeComplexity();
        $this->generateStatistics();
        
        return [
            'patterns' => $this->patterns,
            'duplicates' => $this->duplicates,
            'statistics' => $this->statistics,
            'recommendations' => $this->generateRecommendations(),
        ];
    }
    
    private function analyzeFile(string $file): void
    {
        $filename = basename($file);
        $content = file_get_contents($file);
        
        if (!$content) {
            return;
        }
        
        // Extract ignore error patterns (handle both quoted and unquoted patterns)
        preg_match_all('/^\s*-\s*[\'"]?([^\'"\r\n]*)[\'"]?$/m', $content, $matches);
        
        foreach ($matches[1] as $pattern) {
            $pattern = trim($pattern);
            
            // Skip empty patterns and only process regex patterns
            if (empty($pattern) || strpos($pattern, '#') !== 0) {
                continue;
            }
            
            $normalized = $this->normalizePattern($pattern);
            
            if (!isset($this->patterns[$normalized])) {
                $this->patterns[$normalized] = [
                    'pattern' => $pattern,
                    'normalized' => $normalized,
                    'files' => [],
                    'complexity' => $this->calculateComplexity($pattern),
                    'type' => $this->categorizePattern($pattern),
                ];
            }
            
            $this->patterns[$normalized]['files'][] = $filename;
        }
    }
    
    private function normalizePattern(string $pattern): string
    {
        // Remove file paths and line numbers for comparison
        $normalized = preg_replace('/#[^#]*#/', '#REGEX#', $pattern);
        $normalized = preg_replace('/\\\\\w+(\\\\\w+)*/', 'NAMESPACE', $normalized);
        return trim($normalized);
    }
    
    private function calculateComplexity(string $pattern): int
    {
        $complexity = 0;
        
        // Count regex special characters
        $complexity += substr_count($pattern, '+');
        $complexity += substr_count($pattern, '*');
        $complexity += substr_count($pattern, '?');
        $complexity += substr_count($pattern, '[');
        $complexity += substr_count($pattern, '(');
        $complexity += substr_count($pattern, '|');
        $complexity += substr_count($pattern, '\\');
        
        // Count character classes
        $complexity += substr_count($pattern, '[A-Z]');
        $complexity += substr_count($pattern, '[a-z]');
        $complexity += substr_count($pattern, '[0-9]');
        
        return $complexity;
    }
    
    private function categorizePattern(string $pattern): string
    {
        if (strpos($pattern, 'Eloquent') !== false || strpos($pattern, 'Builder') !== false) {
            return 'eloquent';
        }
        
        if (strpos($pattern, 'Filament') !== false) {
            return 'filament';
        }
        
        if (strpos($pattern, 'Livewire') !== false) {
            return 'livewire';
        }
        
        if (strpos($pattern, 'Request') !== false || strpos($pattern, 'Http') !== false) {
            return 'http';
        }
        
        if (strpos($pattern, 'Collection') !== false) {
            return 'collection';
        }
        
        if (strpos($pattern, 'Facade') !== false) {
            return 'facade';
        }
        
        return 'other';
    }
    
    private function findDuplicates(): void
    {
        foreach ($this->patterns as $normalized => $data) {
            if (count($data['files']) > 1) {
                $this->duplicates[$normalized] = $data;
            }
        }
    }
    
    private function analyzeComplexity(): void
    {
        $complexityLevels = [
            'simple' => 0,
            'moderate' => 0,
            'complex' => 0,
            'very_complex' => 0,
        ];
        
        foreach ($this->patterns as $data) {
            $complexity = $data['complexity'];
            
            if ($complexity <= 5) {
                $complexityLevels['simple']++;
            } elseif ($complexity <= 15) {
                $complexityLevels['moderate']++;
            } elseif ($complexity <= 30) {
                $complexityLevels['complex']++;
            } else {
                $complexityLevels['very_complex']++;
            }
        }
        
        $this->statistics['complexity'] = $complexityLevels;
    }
    
    private function generateStatistics(): void
    {
        $files = glob($this->baselineDir . '/*.neon');
        
        $this->statistics['files_count'] = count($files);
        $this->statistics['total_patterns'] = count($this->patterns);
        $this->statistics['duplicate_patterns'] = count($this->duplicates);
        $this->statistics['unique_patterns'] = count($this->patterns) - count($this->duplicates);
        $this->statistics['duplicate_percentage'] = round((count($this->duplicates) / count($this->patterns)) * 100, 2);
        
        // Categorize patterns
        $categories = [];
        foreach ($this->patterns as $data) {
            $type = $data['type'];
            $categories[$type] = ($categories[$type] ?? 0) + 1;
        }
        $this->statistics['categories'] = $categories;
        
        // Most common duplicates
        $duplicatesByCount = $this->duplicates;
        uasort($duplicatesByCount, function($a, $b) {
            return count($b['files']) - count($a['files']);
        });
        
        $this->statistics['most_duplicated'] = array_slice($duplicatesByCount, 0, 10, true);
    }
    
    private function generateRecommendations(): array
    {
        $recommendations = [];
        
        // Duplicate pattern recommendations
        if (count($this->duplicates) > 0) {
            $recommendations[] = [
                'type' => 'duplicates',
                'priority' => 'high',
                'title' => 'Remove duplicate patterns',
                'description' => sprintf(
                    'Found %d duplicate patterns across multiple files (%.2f%% of total patterns). Consider consolidating common patterns into shared baseline files.',
                    count($this->duplicates),
                    $this->statistics['duplicate_percentage']
                ),
                'action' => 'Create common baseline files for shared patterns',
                'patterns' => array_keys(array_slice($this->duplicates, 0, 5, true)),
            ];
        }
        
        // Complexity recommendations
        $veryComplex = array_filter($this->patterns, fn($p) => $p['complexity'] > 30);
        if (count($veryComplex) > 0) {
            $recommendations[] = [
                'type' => 'complexity',
                'priority' => 'medium',
                'title' => 'Optimize complex regex patterns',
                'description' => sprintf(
                    'Found %d very complex regex patterns that may impact performance. Consider simplifying or breaking them down.',
                    count($veryComplex)
                ),
                'action' => 'Review and optimize complex patterns',
                'patterns' => array_keys(array_slice($veryComplex, 0, 5, true)),
            ];
        }
        
        // File organization recommendations
        if ($this->statistics['files_count'] > 10) {
            $recommendations[] = [
                'type' => 'organization',
                'priority' => 'low',
                'title' => 'Consider file organization',
                'description' => sprintf(
                    'With %d baseline files, consider organizing patterns by level-specific includes.',
                    $this->statistics['files_count']
                ),
                'action' => 'Create level-based baseline organization',
            ];
        }
        
        return $recommendations;
    }
    
    public function generateReport(): string
    {
        $data = $this->analyze();
        
        $report = "# PHPStan Baseline Pattern Analysis Report\n\n";
        $report .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        // Statistics
        $report .= "## 📊 Statistics\n\n";
        $report .= "- **Total Files**: {$data['statistics']['files_count']}\n";
        $report .= "- **Total Patterns**: {$data['statistics']['total_patterns']}\n";
        $report .= "- **Unique Patterns**: {$data['statistics']['unique_patterns']}\n";
        $report .= "- **Duplicate Patterns**: {$data['statistics']['duplicate_patterns']} ({$data['statistics']['duplicate_percentage']}%)\n\n";
        
        // Categories
        $report .= "### Pattern Categories\n\n";
        foreach ($data['statistics']['categories'] as $category => $count) {
            $report .= "- **" . ucfirst($category) . "**: {$count} patterns\n";
        }
        $report .= "\n";
        
        // Complexity
        $report .= "### Complexity Distribution\n\n";
        foreach ($data['statistics']['complexity'] as $level => $count) {
            $report .= "- **" . ucfirst(str_replace('_', ' ', $level)) . "**: {$count} patterns\n";
        }
        $report .= "\n";
        
        // Top duplicates
        $report .= "## 🔄 Most Duplicated Patterns\n\n";
        if (!empty($data['statistics']['most_duplicated'])) {
            foreach (array_slice($data['statistics']['most_duplicated'], 0, 5, true) as $pattern => $patternData) {
                $fileList = implode(', ', array_unique($patternData['files']));
                $report .= "### Pattern: `{$pattern}`\n";
                $report .= "- **Files**: {$fileList}\n";
                $report .= "- **Count**: " . count($patternData['files']) . "\n";
                $report .= "- **Original**: `{$patternData['pattern']}`\n\n";
            }
        } else {
            $report .= "No duplicate patterns found.\n\n";
        }
        
        // Recommendations
        $report .= "## 💡 Recommendations\n\n";
        if (!empty($data['recommendations'])) {
            foreach ($data['recommendations'] as $rec) {
                $priority = strtoupper($rec['priority']);
                $report .= "### [{$priority}] {$rec['title']}\n\n";
                $report .= "{$rec['description']}\n\n";
                $report .= "**Action**: {$rec['action']}\n\n";
                
                if (isset($rec['patterns'])) {
                    $report .= "**Example patterns**:\n";
                    foreach (array_slice($rec['patterns'], 0, 3) as $pattern) {
                        $report .= "- `{$pattern}`\n";
                    }
                    $report .= "\n";
                }
            }
        } else {
            $report .= "No specific recommendations at this time.\n\n";
        }
        
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
    
    $analyzer = new BaselineAnalyzer($baselineDir);
    echo $analyzer->generateReport();
}