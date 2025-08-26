<?php

declare(strict_types=1);

namespace BlueLucifer\LaravelFilamentPHPStan\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

final class BaselineValidationTest extends TestCase
{
    private string $baselinesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baselinesPath = dirname(__DIR__, 2) . '/baselines';
    }

    /**
     * @dataProvider baselineFilesProvider
     */
    public function test_baseline_files_have_valid_neon_syntax(string $filename): void
    {
        $filepath = $this->baselinesPath . '/' . $filename;
        $content = file_get_contents($filepath);

        $this->assertNotFalse($content, "Could not read baseline file: {$filename}");

        try {
            // Parse NEON as YAML (compatible syntax)
            $parsed = Yaml::parse($content);
            $this->assertIsArray($parsed, "Baseline file {$filename} should parse to an array");
        } catch (ParseException $e) {
            $this->fail("Baseline file {$filename} has invalid NEON/YAML syntax: " . $e->getMessage());
        }
    }

    /**
     * @dataProvider baselineFilesProvider
     */
    public function test_baseline_files_have_required_structure(string $filename): void
    {
        $filepath = $this->baselinesPath . '/' . $filename;
        $content = file_get_contents($filepath);
        $parsed = Yaml::parse($content);

        $this->assertArrayHasKey('parameters', $parsed, "Baseline file {$filename} must have 'parameters' key");
        $this->assertArrayHasKey('ignoreErrors', $parsed['parameters'], "Baseline file {$filename} must have 'ignoreErrors' under parameters");
        $this->assertIsArray($parsed['parameters']['ignoreErrors'], "ignoreErrors must be an array in {$filename}");
    }

    /**
     * @dataProvider baselineFilesProvider
     */
    public function test_baseline_files_have_valid_regex_patterns(string $filename): void
    {
        $filepath = $this->baselinesPath . '/' . $filename;
        $content = file_get_contents($filepath);
        $parsed = Yaml::parse($content);

        $errors = $parsed['parameters']['ignoreErrors'] ?? [];
        
        foreach ($errors as $index => $pattern) {
            $patternString = '';
            
            if (is_string($pattern)) {
                $patternString = $pattern;
            } elseif (is_array($pattern) && isset($pattern['message'])) {
                $patternString = $pattern['message'];
            }
            
            if (!empty($patternString) && preg_match('/^#.*#[a-zA-Z]*$/', $patternString)) {
                // Test if the regex pattern is valid
                $testResult = @preg_match($patternString, 'test string');
                $this->assertNotFalse($testResult, "Invalid regex pattern at index {$index} in {$filename}: {$patternString}");
            }
        }
    }

    /**
     * @dataProvider baselineFilesProvider
     */
    public function test_baseline_files_follow_naming_convention(string $filename): void
    {
        // Check if filename follows expected patterns
        $validPatterns = [
            '/^laravel-\d+(-strict)?\.neon$/',  // laravel-11.neon, laravel-11-strict.neon
            '/^filament-\d+(-strict)?\.neon$/', // filament-3.neon, filament-3-strict.neon
            '/^livewire-\d+(-strict)?\.neon$/', // livewire-3.neon, livewire-3-strict.neon
            '/^level-\d+-\d+\.neon$/',          // level-0-2.neon, level-3-5.neon
            '/^[a-z-]+\.neon$/',               // spatie-packages.neon, laravel-excel.neon
        ];

        $isValid = false;
        foreach ($validPatterns as $pattern) {
            if (preg_match($pattern, $filename)) {
                $isValid = true;
                break;
            }
        }

        $this->assertTrue($isValid, "Filename {$filename} does not follow expected naming conventions");
    }

    /**
     * @dataProvider baselineFilesProvider
     */
    public function test_baseline_files_have_proper_documentation(string $filename): void
    {
        $filepath = $this->baselinesPath . '/' . $filename;
        $content = file_get_contents($filepath);
        
        // Check if file has header comment
        $lines = explode("\n", $content);
        $hasHeader = false;
        
        foreach (array_slice($lines, 0, 5) as $line) {
            if (str_contains($line, 'PHPStan Baseline') || str_contains($line, 'Community-maintained')) {
                $hasHeader = true;
                break;
            }
        }

        $this->assertTrue($hasHeader, "Baseline file {$filename} should have a descriptive header comment");
    }

    public static function baselineFilesProvider(): array
    {
        $baselinesPath = dirname(__DIR__, 2) . '/baselines';
        $files = glob($baselinesPath . '/*.neon');
        
        return array_map(
            fn(string $file) => [basename($file)],
            $files
        );
    }
}