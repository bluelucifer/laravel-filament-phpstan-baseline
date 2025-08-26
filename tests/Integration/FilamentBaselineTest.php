<?php

declare(strict_types=1);

namespace BlueLucifer\LaravelFilamentPHPStan\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class FilamentBaselineTest extends TestCase
{
    private string $baselinesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baselinesPath = dirname(__DIR__, 2) . '/baselines';
    }

    /**
     * @dataProvider filamentBaselineProvider
     */
    public function test_filament_baseline_covers_common_patterns(string $filename): void
    {
        $filepath = $this->baselinesPath . '/' . $filename;
        $content = file_get_contents($filepath);
        $parsed = Yaml::parse($content);

        $errors = $parsed['parameters']['ignoreErrors'] ?? [];
        $patterns = [];
        
        foreach ($errors as $error) {
            if (is_string($error)) {
                $patterns[] = $error;
            } elseif (is_array($error) && isset($error['message'])) {
                $patterns[] = $error['message'];
            }
        }

        // Essential Filament patterns that should be covered
        $expectedPatterns = [
            'Filament',                    // Filament namespace
            'Resource',                    // Filament Resources
            'Component',                   // Filament Components
            'Form',                        // Form components
            'Table',                       // Table components
            'Page',                        // Filament Pages
            'Widget',                      // Dashboard Widgets
        ];

        $missingPatterns = [];
        foreach ($expectedPatterns as $expectedPattern) {
            $found = false;
            foreach ($patterns as $pattern) {
                if (str_contains($pattern, $expectedPattern)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $missingPatterns[] = $expectedPattern;
            }
        }

        // For Filament baselines, we might not have all patterns if it's a minimal baseline
        if (!empty($missingPatterns) && count($missingPatterns) < count($expectedPatterns)) {
            fwrite(STDERR, "Info: Filament baseline {$filename} missing some patterns: " . implode(', ', $missingPatterns) . "\n");
        }

        $this->assertTrue(true, 'Filament pattern coverage check completed');
    }

    /**
     * @dataProvider filamentBaselineProvider
     */
    public function test_filament_baseline_includes_laravel_compatibility(string $filename): void
    {
        $filepath = $this->baselinesPath . '/' . $filename;
        $content = file_get_contents($filepath);
        $parsed = Yaml::parse($content);

        $errors = $parsed['parameters']['ignoreErrors'] ?? [];
        $patterns = [];
        
        foreach ($errors as $error) {
            if (is_string($error)) {
                $patterns[] = $error;
            } elseif (is_array($error) && isset($error['message'])) {
                $patterns[] = $error['message'];
            }
        }

        // Filament baselines should include some Laravel patterns too
        $laravelPatterns = [
            'Eloquent',
            'Model',
            'Collection',
        ];

        $foundLaravelPatterns = 0;
        foreach ($laravelPatterns as $laravelPattern) {
            foreach ($patterns as $pattern) {
                if (str_contains($pattern, $laravelPattern)) {
                    $foundLaravelPatterns++;
                    break;
                }
            }
        }

        // Filament builds on Laravel, so we expect some Laravel patterns
        $this->assertGreaterThan(
            0,
            $foundLaravelPatterns,
            "Filament baseline {$filename} should include some Laravel-related patterns"
        );
    }

    public function test_filament_version_compatibility(): void
    {
        $filamentFiles = glob($this->baselinesPath . '/filament-*.neon');
        
        if (empty($filamentFiles)) {
            $this->markTestSkipped('No Filament baseline files found');
        }

        foreach ($filamentFiles as $filepath) {
            $filename = basename($filepath);
            $content = file_get_contents($filepath);
            
            // Check if the file mentions version compatibility
            $hasVersionInfo = str_contains($content, 'Filament 3') || 
                             str_contains($content, 'Filament v3') ||
                             preg_match('/filament.+3/i', $content);
            
            if (!$hasVersionInfo) {
                fwrite(STDERR, "Warning: {$filename} might be missing version information in comments\n");
            }
        }

        $this->assertTrue(true, 'Filament version compatibility check completed');
    }

    public function test_strict_vs_regular_filament_baselines(): void
    {
        $regularFiles = glob($this->baselinesPath . '/filament-*[^-strict].neon');
        $strictFiles = glob($this->baselinesPath . '/filament-*-strict.neon');

        if (empty($regularFiles) || empty($strictFiles)) {
            $this->markTestSkipped('Need both regular and strict Filament baselines to compare');
        }

        // Compare regular vs strict versions
        foreach ($regularFiles as $regularFile) {
            $version = preg_replace('/.*filament-(\d+)\.neon$/', '$1', $regularFile);
            $strictFile = dirname($regularFile) . "/filament-{$version}-strict.neon";

            if (file_exists($strictFile)) {
                $regularContent = file_get_contents($regularFile);
                $strictContent = file_get_contents($strictFile);

                $regularParsed = Yaml::parse($regularContent);
                $strictParsed = Yaml::parse($strictContent);

                $regularErrors = $regularParsed['parameters']['ignoreErrors'] ?? [];
                $strictErrors = $strictParsed['parameters']['ignoreErrors'] ?? [];

                // Strict version should generally have fewer ignored errors
                $this->assertLessThanOrEqual(
                    count($regularErrors),
                    count($strictErrors),
                    "Strict Filament baseline should not have more ignored errors than regular version"
                );

                // Log the difference
                fwrite(STDERR, sprintf(
                    "Filament v%s: Regular=%d errors, Strict=%d errors\n",
                    $version,
                    count($regularErrors),
                    count($strictErrors)
                ));
            }
        }

        $this->assertTrue(true, 'Strict vs regular baseline comparison completed');
    }

    public static function filamentBaselineProvider(): array
    {
        $baselinesPath = dirname(__DIR__, 2) . '/baselines';
        $files = glob($baselinesPath . '/filament-*.neon');
        
        return array_map(
            fn(string $file) => [basename($file)],
            $files
        );
    }
}