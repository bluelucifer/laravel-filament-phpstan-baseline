<?php
/**
 * PHPStan Performance Benchmark Tool
 * 
 * This script measures the performance impact of different baseline configurations
 * and provides recommendations for optimization.
 */

declare(strict_types=1);

class PerformanceBenchmark
{
    private string $projectDir;
    private array $results = [];
    
    public function __construct(string $projectDir)
    {
        $this->projectDir = rtrim($projectDir, '/');
    }
    
    public function runBenchmarks(): array
    {
        $configurations = $this->getConfigurations();
        
        foreach ($configurations as $name => $config) {
            echo "Running benchmark: {$name}...\n";
            $this->results[$name] = $this->benchmarkConfiguration($config);
        }
        
        return $this->results;
    }
    
    private function getConfigurations(): array
    {
        return [
            'baseline_none' => [
                'description' => 'No baseline patterns',
                'includes' => [],
                'level' => 5,
            ],
            'baseline_laravel_only' => [
                'description' => 'Laravel 11 baseline only',
                'includes' => ['baselines/laravel-11.neon'],
                'level' => 5,
            ],
            'baseline_filament_only' => [
                'description' => 'Filament 3 baseline only',
                'includes' => ['baselines/filament-3.neon'],
                'level' => 5,
            ],
            'baseline_combined' => [
                'description' => 'Laravel + Filament + Livewire',
                'includes' => [
                    'baselines/laravel-11.neon',
                    'baselines/filament-3.neon',
                    'baselines/livewire-3.neon',
                ],
                'level' => 5,
            ],
            'baseline_optimized' => [
                'description' => 'Optimized with common patterns',
                'includes' => [
                    'baselines/common-patterns.neon',
                    'baselines/laravel-11.neon',
                    'baselines/filament-3.neon',
                ],
                'level' => 5,
            ],
            'baseline_level_8' => [
                'description' => 'Level 8 with full baselines',
                'includes' => [
                    'baselines/common-patterns.neon',
                    'baselines/laravel-11.neon',
                    'baselines/filament-3.neon',
                    'baselines/livewire-3.neon',
                ],
                'level' => 8,
            ],
        ];
    }
    
    private function benchmarkConfiguration(array $config): array
    {
        // Create temporary config file
        $tempConfig = $this->createTempConfig($config);
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // Run PHPStan
        $command = "vendor/bin/phpstan analyse --configuration={$tempConfig} --no-progress --error-format=json 2>/dev/null";
        $output = shell_exec($command);
        
        $endTime = microtime(true);
        $endMemory = memory_get_peak_usage(true);
        
        // Parse results
        $errors = 0;
        if ($output) {
            $result = json_decode($output, true);
            if (isset($result['totals']['errors'])) {
                $errors = $result['totals']['errors'];
            }
        }
        
        // Clean up
        unlink($tempConfig);
        
        return [
            'execution_time' => round($endTime - $startTime, 3),
            'memory_usage' => $this->formatBytes($endMemory - $startMemory),
            'memory_peak' => $this->formatBytes($endMemory),
            'errors_found' => $errors,
            'description' => $config['description'],
        ];
    }
    
    private function createTempConfig(array $config): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'phpstan_benchmark_');
        
        $configContent = "parameters:\n";
        
        if (!empty($config['includes'])) {
            $configContent .= "includes:\n";
            foreach ($config['includes'] as $include) {
                $configContent .= "    - {$include}\n";
            }
        }
        
        $configContent .= "    level: {$config['level']}\n";
        $configContent .= "    paths:\n";
        $configContent .= "        - baselines/\n"; // Analyze baselines directory for testing
        $configContent .= "    checkMissingIterableValueType: false\n";
        
        file_put_contents($tempFile, $configContent);
        
        return $tempFile;
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    public function generateReport(): string
    {
        $results = $this->runBenchmarks();
        
        $report = "# PHPStan Performance Benchmark Report\n\n";
        $report .= "Generated: " . date('Y-m-d H:i:s') . "\n";
        $report .= "Test Environment: PHP " . PHP_VERSION . "\n\n";
        
        // Results table
        $report .= "## 📊 Performance Results\n\n";
        $report .= "| Configuration | Time (s) | Memory | Peak Memory | Errors |\n";
        $report .= "|---------------|----------|--------|-------------|--------|\n";
        
        foreach ($results as $name => $result) {
            $report .= sprintf(
                "| %s | %.3f | %s | %s | %d |\n",
                $result['description'],
                $result['execution_time'],
                $result['memory_usage'],
                $result['memory_peak'],
                $result['errors_found']
            );
        }
        
        $report .= "\n## 📈 Performance Analysis\n\n";
        
        // Find fastest and slowest
        $times = array_column($results, 'execution_time');
        $fastest = array_search(min($times), $times);
        $slowest = array_search(max($times), $times);
        
        $fastestConfig = array_keys($results)[$fastest];
        $slowestConfig = array_keys($results)[$slowest];
        
        $report .= "- **Fastest Configuration**: {$results[$fastestConfig]['description']} ({$results[$fastestConfig]['execution_time']}s)\n";
        $report .= "- **Slowest Configuration**: {$results[$slowestConfig]['description']} ({$results[$slowestConfig]['execution_time']}s)\n";
        
        $speedDiff = round(($results[$slowestConfig]['execution_time'] / $results[$fastestConfig]['execution_time']) * 100 - 100, 1);
        $report .= "- **Performance Difference**: {$speedDiff}%\n\n";
        
        // Memory analysis
        $report .= "### Memory Usage\n\n";
        $memoryUsages = array_column($results, 'memory_peak');
        $lowestMemory = min($memoryUsages);
        $highestMemory = max($memoryUsages);
        
        $report .= "- **Lowest Memory**: {$this->formatBytes($lowestMemory)}\n";
        $report .= "- **Highest Memory**: {$this->formatBytes($highestMemory)}\n";
        
        // Error analysis
        $report .= "### Error Detection\n\n";
        $errorCounts = array_column($results, 'errors_found');
        $minErrors = min($errorCounts);
        $maxErrors = max($errorCounts);
        
        $report .= "- **Minimum Errors Found**: {$minErrors}\n";
        $report .= "- **Maximum Errors Found**: {$maxErrors}\n\n";
        
        // Recommendations
        $report .= "## 💡 Optimization Recommendations\n\n";
        
        $report .= "### 1. Performance vs. Coverage Trade-off\n\n";
        $report .= "More baseline patterns generally mean:\n";
        $report .= "- Slightly slower analysis time\n";
        $report .= "- Higher memory usage\n";
        $report .= "- Fewer false positive errors\n";
        $report .= "- Better developer experience\n\n";
        
        $report .= "### 2. Recommended Configuration\n\n";
        $report .= "For most projects, use the **optimized baseline** approach:\n\n";
        $report .= "```neon\n";
        $report .= "includes:\n";
        $report .= "    - vendor/laravel-filament/phpstan-baseline/baselines/common-patterns.neon\n";
        $report .= "    - vendor/laravel-filament/phpstan-baseline/baselines/laravel-11.neon\n";
        $report .= "    - vendor/laravel-filament/phpstan-baseline/baselines/filament-3.neon\n";
        $report .= "parameters:\n";
        $report .= "    level: 6\n";
        $report .= "```\n\n";
        
        $report .= "### 3. CI/CD Optimization\n\n";
        $report .= "For continuous integration:\n";
        $report .= "- Use PHPStan result cache (`tmpDir` parameter)\n";
        $report .= "- Run analysis only on changed files when possible\n";
        $report .= "- Consider parallel analysis for large codebases\n\n";
        
        $report .= "### 4. Development vs. Production\n\n";
        $report .= "- **Development**: Use comprehensive baselines for better DX\n";
        $report .= "- **CI/CD**: Use minimal baselines for faster feedback\n";
        $report .= "- **Pre-commit**: Use level-appropriate baselines for quick checks\n\n";
        
        return $report;
    }
}

// CLI usage
if (php_sapi_name() === 'cli') {
    $projectDir = $argv[1] ?? getcwd();
    
    if (!is_dir($projectDir)) {
        echo "Error: Project directory not found: {$projectDir}\n";
        exit(1);
    }
    
    // Check if PHPStan is available
    if (!file_exists($projectDir . '/vendor/bin/phpstan')) {
        echo "Warning: PHPStan not found. Install with: composer require --dev phpstan/phpstan\n";
        echo "Running analysis on baseline files only...\n\n";
    }
    
    $benchmark = new PerformanceBenchmark($projectDir);
    echo $benchmark->generateReport();
}