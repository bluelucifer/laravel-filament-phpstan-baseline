#!/usr/bin/env php
<?php

/**
 * PHPStan Pattern Performance Benchmark Script
 * 
 * This script measures the performance of PHPStan baseline patterns
 * by testing regex matching speed and memory usage.
 */

declare(strict_types=1);

// Configuration
const ITERATIONS = 10000;
const WARM_UP_ITERATIONS = 1000;
const MEMORY_PRECISION = 2;

// Color codes for terminal output
const COLOR_GREEN = "\033[32m";
const COLOR_YELLOW = "\033[33m";
const COLOR_RED = "\033[31m";
const COLOR_CYAN = "\033[36m";
const COLOR_RESET = "\033[0m";
const COLOR_BOLD = "\033[1m";

class PatternBenchmark
{
    private array $patterns = [];
    private array $testStrings = [];
    private array $results = [];
    
    public function __construct()
    {
        $this->loadPatterns();
        $this->generateTestStrings();
    }
    
    /**
     * Load all patterns from baseline files
     */
    private function loadPatterns(): void
    {
        $baselineDir = __DIR__ . '/../baselines';
        $files = glob($baselineDir . '/*.neon');
        
        foreach ($files as $file) {
            $filename = basename($file);
            $content = file_get_contents($file);
            
            // Extract patterns from ignoreErrors section
            preg_match_all('/^\s*-\s*[\'"]#(.+)#[\'"]$/m', $content, $matches);
            
            if (!empty($matches[1])) {
                $this->patterns[$filename] = array_unique($matches[1]);
            }
        }
        
        $this->printInfo("Loaded " . array_sum(array_map('count', $this->patterns)) . " patterns from " . count($this->patterns) . " files");
    }
    
    /**
     * Generate realistic test strings based on common PHPStan errors
     */
    private function generateTestStrings(): void
    {
        $this->testStrings = [
            // Laravel patterns
            'Call to an undefined method Illuminate\\Database\\Eloquent\\Builder::whereEmail()',
            'Call to an undefined method App\\Models\\User::scopeActive()',
            'Property App\\Models\\User::$name has no type specified',
            'Method App\\Http\\Controllers\\UserController::index() has no return type specified',
            'Access to an undefined property Illuminate\\Http\\Request::$email',
            
            // Filament patterns
            'Call to an undefined method Filament\\Forms\\Components\\TextInput::required()',
            'Call to an undefined method Filament\\Tables\\Columns\\TextColumn::searchable()',
            'Variable $record might not be defined',
            'Property App\\Filament\\Resources\\UserResource::$data has no type specified',
            
            // Livewire patterns
            'Property App\\Http\\Livewire\\UserComponent::$listeners has no type specified',
            'Method App\\Http\\Livewire\\UserComponent::mount() has no return type specified',
            'Method App\\Http\\Livewire\\UserComponent::updatedEmail() has no return type specified',
            
            // Generic patterns
            'Parameter #1 $key of method Illuminate\\Http\\Request::input() expects string, mixed given',
            'Method App\\Services\\UserService::getUser() return type has no value type specified',
            'Cannot access offset \'name\' on mixed',
            'Variable $data might not be defined',
            
            // Non-matching strings (to test false positives)
            'This is a regular string that should not match any pattern',
            'Another normal string without any PHPStan error patterns',
            'public function normalMethod(): void',
        ];
        
        // Generate more variations
        $variations = [];
        foreach ($this->testStrings as $string) {
            // Add variations with different class/method names
            $variations[] = str_replace('User', 'Product', $string);
            $variations[] = str_replace('email', 'username', $string);
            $variations[] = str_replace('App\\', 'Domain\\', $string);
        }
        
        $this->testStrings = array_merge($this->testStrings, $variations);
        $this->printInfo("Generated " . count($this->testStrings) . " test strings");
    }
    
    /**
     * Run benchmark for all patterns
     */
    public function run(): void
    {
        $this->printHeader("Starting PHPStan Pattern Benchmark");
        
        foreach ($this->patterns as $file => $patterns) {
            $this->printSection("File: $file");
            $this->benchmarkFile($file, $patterns);
        }
        
        $this->printSummary();
        $this->identifyOptimizations();
    }
    
    /**
     * Benchmark patterns from a single file
     */
    private function benchmarkFile(string $file, array $patterns): void
    {
        $fileResults = [
            'total_time' => 0,
            'total_matches' => 0,
            'pattern_times' => [],
            'memory_usage' => 0,
        ];
        
        $startMemory = memory_get_usage(true);
        
        foreach ($patterns as $index => $pattern) {
            $result = $this->benchmarkPattern($pattern);
            $fileResults['pattern_times'][$index] = $result;
            $fileResults['total_time'] += $result['time'];
            $fileResults['total_matches'] += $result['matches'];
            
            // Show progress for long lists
            if (($index + 1) % 10 === 0) {
                $this->printProgress($index + 1, count($patterns));
            }
        }
        
        $fileResults['memory_usage'] = memory_get_usage(true) - $startMemory;
        $this->results[$file] = $fileResults;
        
        $this->printFileStats($file, $fileResults);
    }
    
    /**
     * Benchmark a single pattern
     */
    private function benchmarkPattern(string $pattern): array
    {
        $matches = 0;
        $errors = 0;
        
        // Warm up
        for ($i = 0; $i < WARM_UP_ITERATIONS; $i++) {
            foreach ($this->testStrings as $string) {
                @preg_match("/$pattern/", $string);
            }
        }
        
        // Actual benchmark
        $start = microtime(true);
        
        for ($i = 0; $i < ITERATIONS; $i++) {
            foreach ($this->testStrings as $string) {
                $result = @preg_match("/$pattern/", $string);
                if ($result === 1) {
                    $matches++;
                } elseif ($result === false) {
                    $errors++;
                }
            }
        }
        
        $end = microtime(true);
        $time = ($end - $start) * 1000; // Convert to milliseconds
        
        return [
            'pattern' => $pattern,
            'time' => $time,
            'matches' => $matches,
            'errors' => $errors,
            'avg_time_per_match' => $matches > 0 ? $time / $matches : 0,
        ];
    }
    
    /**
     * Identify potential optimizations
     */
    private function identifyOptimizations(): void
    {
        $this->printHeader("Optimization Opportunities");
        
        $allPatterns = [];
        foreach ($this->patterns as $file => $patterns) {
            foreach ($patterns as $pattern) {
                $allPatterns[] = ['file' => $file, 'pattern' => $pattern];
            }
        }
        
        // Find duplicate patterns
        $this->findDuplicates($allPatterns);
        
        // Find overlapping patterns
        $this->findOverlapping($allPatterns);
        
        // Find inefficient patterns
        $this->findInefficient($allPatterns);
        
        // Suggest consolidations
        $this->suggestConsolidations($allPatterns);
    }
    
    /**
     * Find duplicate patterns across files
     */
    private function findDuplicates(array $allPatterns): void
    {
        $patternMap = [];
        
        foreach ($allPatterns as $item) {
            $pattern = $item['pattern'];
            if (!isset($patternMap[$pattern])) {
                $patternMap[$pattern] = [];
            }
            $patternMap[$pattern][] = $item['file'];
        }
        
        $duplicates = array_filter($patternMap, fn($files) => count($files) > 1);
        
        if (!empty($duplicates)) {
            $this->printWarning("Found " . count($duplicates) . " duplicate patterns:");
            foreach ($duplicates as $pattern => $files) {
                echo "  Pattern: " . COLOR_CYAN . substr($pattern, 0, 80) . COLOR_RESET . "\n";
                echo "  Files: " . implode(', ', array_unique($files)) . "\n\n";
            }
        }
    }
    
    /**
     * Find overlapping patterns that could be consolidated
     */
    private function findOverlapping(array $allPatterns): void
    {
        $overlapping = [];
        
        // Check for patterns that could be combined
        $methodPatterns = [];
        $propertyPatterns = [];
        
        foreach ($allPatterns as $item) {
            $pattern = $item['pattern'];
            
            // Group method-related patterns
            if (strpos($pattern, 'method') !== false || strpos($pattern, 'Method') !== false) {
                $methodPatterns[] = $item;
            }
            
            // Group property-related patterns
            if (strpos($pattern, 'property') !== false || strpos($pattern, 'Property') !== false) {
                $propertyPatterns[] = $item;
            }
        }
        
        if (count($methodPatterns) > 5) {
            $this->printInfo("Found " . count($methodPatterns) . " method-related patterns that could potentially be consolidated");
        }
        
        if (count($propertyPatterns) > 5) {
            $this->printInfo("Found " . count($propertyPatterns) . " property-related patterns that could potentially be consolidated");
        }
    }
    
    /**
     * Find inefficient regex patterns
     */
    private function findInefficient(array $allPatterns): void
    {
        $inefficient = [];
        
        foreach ($allPatterns as $item) {
            $pattern = $item['pattern'];
            
            // Check for patterns starting with .*
            if (strpos($pattern, '^.*') === 0) {
                $inefficient[] = ['type' => 'starts_with_wildcard', 'item' => $item];
            }
            
            // Check for multiple .* in sequence
            if (preg_match('/\.\*.*\.\*/', $pattern)) {
                $inefficient[] = ['type' => 'multiple_wildcards', 'item' => $item];
            }
            
            // Check for unanchored patterns
            if (strpos($pattern, '^') !== 0 && strpos($pattern, '$') === false) {
                $inefficient[] = ['type' => 'unanchored', 'item' => $item];
            }
            
            // Check for patterns with too many alternatives
            if (substr_count($pattern, '|') > 10) {
                $inefficient[] = ['type' => 'too_many_alternatives', 'item' => $item];
            }
        }
        
        if (!empty($inefficient)) {
            $this->printWarning("Found " . count($inefficient) . " potentially inefficient patterns:");
            
            $byType = [];
            foreach ($inefficient as $item) {
                $type = $item['type'];
                if (!isset($byType[$type])) {
                    $byType[$type] = 0;
                }
                $byType[$type]++;
            }
            
            foreach ($byType as $type => $count) {
                echo "  - " . str_replace('_', ' ', ucfirst($type)) . ": $count patterns\n";
            }
        }
    }
    
    /**
     * Suggest pattern consolidations
     */
    private function suggestConsolidations(array $allPatterns): void
    {
        $this->printSection("Suggested Consolidations");
        
        // Group similar where* method patterns
        $wherePatterns = array_filter($allPatterns, fn($item) => 
            strpos($item['pattern'], '::where') !== false
        );
        
        if (count($wherePatterns) > 2) {
            echo COLOR_GREEN . "✓" . COLOR_RESET . " Consolidate where* patterns:\n";
            echo "  Instead of multiple patterns like:\n";
            echo "    - whereEmail, wherePhone, whereName\n";
            echo "  Use a single pattern:\n";
            echo "    - where[A-Z][a-zA-Z]+\n\n";
        }
        
        // Group scope patterns
        $scopePatterns = array_filter($allPatterns, fn($item) => 
            strpos($item['pattern'], '::scope') !== false
        );
        
        if (count($scopePatterns) > 2) {
            echo COLOR_GREEN . "✓" . COLOR_RESET . " Consolidate scope patterns:\n";
            echo "  Combine all scope patterns into:\n";
            echo "    - ::scope[A-Z][a-zA-Z]+\n\n";
        }
        
        // Group form/table component patterns
        $componentPatterns = array_filter($allPatterns, fn($item) => 
            strpos($item['pattern'], 'Components') !== false
        );
        
        if (count($componentPatterns) > 3) {
            echo COLOR_GREEN . "✓" . COLOR_RESET . " Consolidate component patterns:\n";
            echo "  Group similar component patterns by type\n\n";
        }
    }
    
    /**
     * Print benchmark summary
     */
    private function printSummary(): void
    {
        $this->printHeader("Benchmark Summary");
        
        $totalTime = 0;
        $totalMatches = 0;
        $totalMemory = 0;
        $patternCount = 0;
        
        foreach ($this->results as $file => $result) {
            $totalTime += $result['total_time'];
            $totalMatches += $result['total_matches'];
            $totalMemory += $result['memory_usage'];
            $patternCount += count($result['pattern_times']);
        }
        
        echo "Total Patterns Tested: " . COLOR_BOLD . $patternCount . COLOR_RESET . "\n";
        echo "Total Execution Time: " . COLOR_BOLD . number_format($totalTime, 2) . " ms" . COLOR_RESET . "\n";
        echo "Total Matches: " . COLOR_BOLD . number_format($totalMatches) . COLOR_RESET . "\n";
        echo "Total Memory Used: " . COLOR_BOLD . $this->formatBytes($totalMemory) . COLOR_RESET . "\n";
        echo "Average Time per Pattern: " . COLOR_BOLD . number_format($totalTime / $patternCount, 4) . " ms" . COLOR_RESET . "\n";
        
        // Find slowest patterns
        $slowest = [];
        foreach ($this->results as $file => $result) {
            foreach ($result['pattern_times'] as $patternResult) {
                $slowest[] = [
                    'file' => $file,
                    'pattern' => substr($patternResult['pattern'], 0, 60),
                    'time' => $patternResult['time'],
                ];
            }
        }
        
        usort($slowest, fn($a, $b) => $b['time'] <=> $a['time']);
        $slowest = array_slice($slowest, 0, 5);
        
        $this->printSection("Top 5 Slowest Patterns");
        foreach ($slowest as $index => $item) {
            echo ($index + 1) . ". " . COLOR_RED . number_format($item['time'], 4) . " ms" . COLOR_RESET;
            echo " - " . $item['pattern'] . "...\n";
            echo "   (from " . $item['file'] . ")\n";
        }
    }
    
    // Utility methods
    
    private function printHeader(string $text): void
    {
        echo "\n" . COLOR_BOLD . str_repeat("=", 80) . COLOR_RESET . "\n";
        echo COLOR_BOLD . $text . COLOR_RESET . "\n";
        echo COLOR_BOLD . str_repeat("=", 80) . COLOR_RESET . "\n\n";
    }
    
    private function printSection(string $text): void
    {
        echo "\n" . COLOR_BOLD . $text . COLOR_RESET . "\n";
        echo str_repeat("-", strlen($text)) . "\n";
    }
    
    private function printInfo(string $text): void
    {
        echo COLOR_CYAN . "ℹ " . COLOR_RESET . $text . "\n";
    }
    
    private function printWarning(string $text): void
    {
        echo COLOR_YELLOW . "⚠ " . COLOR_RESET . $text . "\n";
    }
    
    private function printProgress(int $current, int $total): void
    {
        $percentage = ($current / $total) * 100;
        echo "\r  Progress: " . COLOR_GREEN . number_format($percentage, 1) . "%" . COLOR_RESET;
        if ($current === $total) {
            echo "\n";
        }
    }
    
    private function printFileStats(string $file, array $stats): void
    {
        echo "\n  Total Time: " . number_format($stats['total_time'], 2) . " ms\n";
        echo "  Total Matches: " . number_format($stats['total_matches']) . "\n";
        echo "  Memory Usage: " . $this->formatBytes($stats['memory_usage']) . "\n";
        echo "  Patterns: " . count($stats['pattern_times']) . "\n";
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return number_format($bytes, MEMORY_PRECISION) . ' ' . $units[$unitIndex];
    }
}

// Run the benchmark
$benchmark = new PatternBenchmark();
$benchmark->run();