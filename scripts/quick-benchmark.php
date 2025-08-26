#!/usr/bin/env php
<?php

/**
 * Quick Performance Comparison Script
 * Compares original vs optimized patterns
 */

declare(strict_types=1);

const ITERATIONS = 1000;
const COLOR_GREEN = "\033[32m";
const COLOR_RED = "\033[31m";
const COLOR_CYAN = "\033[36m";
const COLOR_RESET = "\033[0m";
const COLOR_BOLD = "\033[1m";

class QuickBenchmark
{
    private array $testStrings = [
        'Call to an undefined method Illuminate\\Database\\Eloquent\\Builder::whereEmail()',
        'Call to an undefined method App\\Models\\User::scopeActive()',
        'Call to an undefined method Filament\\Forms\\Components\\TextInput::required()',
        'Property App\\Models\\User::$name has no type specified',
        'Method App\\Http\\Controllers\\UserController::index() has no return type specified',
        'Variable $record might not be defined',
        'Cannot access offset \'name\' on mixed',
        'Parameter #1 $key of method Illuminate\\Http\\Request::input() expects string, mixed given',
    ];
    
    public function run(): void
    {
        echo COLOR_BOLD . "PHPStan Pattern Optimization Benchmark\n" . COLOR_RESET;
        echo str_repeat("=", 60) . "\n\n";
        
        // Compare specific patterns
        $this->comparePatterns();
        
        // Compare file sizes
        $this->compareFileSizes();
        
        // Count total patterns
        $this->countPatterns();
    }
    
    private function comparePatterns(): void
    {
        echo COLOR_BOLD . "Pattern Performance Comparison:\n" . COLOR_RESET;
        echo str_repeat("-", 40) . "\n\n";
        
        $comparisons = [
            [
                'name' => 'Where Clauses',
                'original' => [
                    '#Call to an undefined method .+::whereEmail\(\)#',
                    '#Call to an undefined method .+::wherePhone\(\)#',
                    '#Call to an undefined method .+::whereName\(\)#',
                ],
                'optimized' => '#^Call to an undefined method .+::where[A-Z]\w*\(\)$#',
            ],
            [
                'name' => 'Method Return Types',
                'original' => [
                    '#Method .+::mount\(\) has no return type specified#',
                    '#Method .+::boot\(\) has no return type specified#',
                    '#Method .+::handle\(\) has no return type specified#',
                    '#Method .+::authorize\(\) has no return type specified#',
                ],
                'optimized' => '#^Method .+::(mount|boot|handle|authorize)\(\) has no return type specified$#',
            ],
            [
                'name' => 'Property Types',
                'original' => [
                    '#Property .+::\$data has no type specified#',
                    '#Property .+::\$rules has no type specified#',
                    '#Property .+::\$listeners has no type specified#',
                ],
                'optimized' => '#^Property .+::\$(data|rules|listeners) has no type specified$#',
            ],
        ];
        
        foreach ($comparisons as $comparison) {
            echo COLOR_CYAN . $comparison['name'] . ":\n" . COLOR_RESET;
            
            // Test original patterns
            $originalTime = 0;
            foreach ($comparison['original'] as $pattern) {
                $start = microtime(true);
                for ($i = 0; $i < ITERATIONS; $i++) {
                    foreach ($this->testStrings as $string) {
                        @preg_match("/$pattern/", $string);
                    }
                }
                $originalTime += (microtime(true) - $start) * 1000;
            }
            
            // Test optimized pattern
            $start = microtime(true);
            for ($i = 0; $i < ITERATIONS; $i++) {
                foreach ($this->testStrings as $string) {
                    @preg_match($comparison['optimized'], $string);
                }
            }
            $optimizedTime = (microtime(true) - $start) * 1000;
            
            $improvement = (($originalTime - $optimizedTime) / $originalTime) * 100;
            
            echo "  Original (" . count($comparison['original']) . " patterns): " . 
                 COLOR_RED . number_format($originalTime, 2) . " ms\n" . COLOR_RESET;
            echo "  Optimized (1 pattern): " . 
                 COLOR_GREEN . number_format($optimizedTime, 2) . " ms\n" . COLOR_RESET;
            echo "  Improvement: " . COLOR_BOLD . COLOR_GREEN . 
                 number_format($improvement, 1) . "%\n\n" . COLOR_RESET;
        }
    }
    
    private function compareFileSizes(): void
    {
        echo COLOR_BOLD . "File Size Comparison:\n" . COLOR_RESET;
        echo str_repeat("-", 40) . "\n\n";
        
        $files = [
            ['original' => 'filament-3.neon', 'optimized' => 'filament-3-optimized.neon'],
            ['original' => 'laravel-11.neon', 'optimized' => 'laravel-11-optimized.neon'],
            ['original' => 'livewire-3.neon', 'optimized' => 'livewire-3-optimized.neon'],
        ];
        
        $totalOriginal = 0;
        $totalOptimized = 0;
        
        foreach ($files as $file) {
            $originalPath = __DIR__ . '/../baselines/' . $file['original'];
            $optimizedPath = __DIR__ . '/../baselines/' . $file['optimized'];
            
            if (file_exists($originalPath) && file_exists($optimizedPath)) {
                $originalSize = filesize($originalPath);
                $optimizedSize = filesize($optimizedPath);
                $reduction = (($originalSize - $optimizedSize) / $originalSize) * 100;
                
                $totalOriginal += $originalSize;
                $totalOptimized += $optimizedSize;
                
                echo $file['original'] . ":\n";
                echo "  Original: " . $this->formatBytes($originalSize) . "\n";
                echo "  Optimized: " . $this->formatBytes($optimizedSize) . "\n";
                echo "  Reduction: " . COLOR_GREEN . number_format($reduction, 1) . "%\n\n" . COLOR_RESET;
            }
        }
        
        if ($totalOriginal > 0) {
            $totalReduction = (($totalOriginal - $totalOptimized) / $totalOriginal) * 100;
            echo COLOR_BOLD . "Total Reduction: " . COLOR_GREEN . 
                 number_format($totalReduction, 1) . "%\n" . COLOR_RESET;
        }
    }
    
    private function countPatterns(): void
    {
        echo "\n" . COLOR_BOLD . "Pattern Count Comparison:\n" . COLOR_RESET;
        echo str_repeat("-", 40) . "\n\n";
        
        $files = [
            'filament-3.neon',
            'filament-3-optimized.neon',
            'laravel-11.neon', 
            'laravel-11-optimized.neon',
            'livewire-3.neon',
            'livewire-3-optimized.neon',
            'master-optimized.neon',
        ];
        
        foreach ($files as $file) {
            $path = __DIR__ . '/../baselines/' . $file;
            if (file_exists($path)) {
                $content = file_get_contents($path);
                preg_match_all('/^\s*-\s*[\'"]#.+#[\'"]$/m', $content, $matches);
                $count = count($matches[0]);
                
                $color = strpos($file, 'optimized') !== false ? COLOR_GREEN : COLOR_RESET;
                echo sprintf("%-30s: %s%3d patterns%s\n", $file, $color, $count, COLOR_RESET);
            }
        }
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return number_format($bytes, 2) . ' ' . $units[$unitIndex];
    }
}

// Run the benchmark
$benchmark = new QuickBenchmark();
$benchmark->run();