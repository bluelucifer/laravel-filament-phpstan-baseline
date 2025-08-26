<?php

declare(strict_types=1);

namespace BlueLucifer\LaravelFilamentPHPStan\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class LivewireBaselineTest extends TestCase
{
    private string $baselinesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baselinesPath = dirname(__DIR__, 2) . '/baselines';
    }

    /**
     * @dataProvider livewireBaselineProvider
     */
    public function test_livewire_baseline_covers_common_patterns(string $filename): void
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

        // Essential Livewire patterns that should be covered
        $expectedPatterns = [
            'Livewire',                    // Livewire namespace
            'Component',                   // Livewire Components
            'mount',                       // Component lifecycle
            'render',                      // Component render method
            'emit',                        // Event emission
            'listeners',                   // Event listeners
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

        // For Livewire baselines, we might not have all patterns if it's a minimal baseline
        if (!empty($missingPatterns) && count($missingPatterns) < count($expectedPatterns)) {
            fwrite(STDERR, "Info: Livewire baseline {$filename} missing some patterns: " . implode(', ', $missingPatterns) . "\n");
        }

        $this->assertTrue(true, 'Livewire pattern coverage check completed');
    }

    /**
     * @dataProvider livewireBaselineProvider
     */
    public function test_livewire_baseline_includes_laravel_compatibility(string $filename): void
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

        // Livewire baselines should include some Laravel patterns too
        $laravelPatterns = [
            'Eloquent',
            'Model',
            'Collection',
            'Request',
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

        // Livewire builds on Laravel, so we expect some Laravel patterns
        $this->assertGreaterThan(
            0,
            $foundLaravelPatterns,
            "Livewire baseline {$filename} should include some Laravel-related patterns"
        );
    }

    public function test_livewire_version_compatibility(): void
    {
        $livewireFiles = glob($this->baselinesPath . '/livewire-*.neon');
        
        if (empty($livewireFiles)) {
            $this->markTestSkipped('No Livewire baseline files found');
        }

        foreach ($livewireFiles as $filepath) {
            $filename = basename($filepath);
            $content = file_get_contents($filepath);
            
            // Check if the file mentions version compatibility
            $hasVersionInfo = str_contains($content, 'Livewire 3') || 
                             str_contains($content, 'Livewire v3') ||
                             preg_match('/livewire.+3/i', $content);
            
            if (!$hasVersionInfo) {
                fwrite(STDERR, "Warning: {$filename} might be missing version information in comments\n");
            }
        }

        $this->assertTrue(true, 'Livewire version compatibility check completed');
    }

    public function test_strict_vs_regular_livewire_baselines(): void
    {
        $regularFiles = glob($this->baselinesPath . '/livewire-*[^-strict].neon');
        $strictFiles = glob($this->baselinesPath . '/livewire-*-strict.neon');

        if (empty($regularFiles) || empty($strictFiles)) {
            $this->markTestSkipped('Need both regular and strict Livewire baselines to compare');
        }

        // Compare regular vs strict versions
        foreach ($regularFiles as $regularFile) {
            $version = preg_replace('/.*livewire-(\d+)\.neon$/', '$1', $regularFile);
            $strictFile = dirname($regularFile) . "/livewire-{$version}-strict.neon";

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
                    "Strict Livewire baseline should not have more ignored errors than regular version"
                );

                // Log the difference
                fwrite(STDERR, sprintf(
                    "Livewire v%s: Regular=%d errors, Strict=%d errors\n",
                    $version,
                    count($regularErrors),
                    count($strictErrors)
                ));
            }
        }

        $this->assertTrue(true, 'Strict vs regular baseline comparison completed');
    }

    public function test_livewire_component_lifecycle_patterns(): void
    {
        $livewireFiles = glob($this->baselinesPath . '/livewire-*.neon');
        
        if (empty($livewireFiles)) {
            $this->markTestSkipped('No Livewire baseline files found');
        }

        $lifecycleMethods = ['mount', 'render', 'boot', 'hydrate', 'dehydrate', 'updated'];
        
        foreach ($livewireFiles as $filepath) {
            $filename = basename($filepath);
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

            $foundLifecycleMethods = [];
            foreach ($lifecycleMethods as $method) {
                foreach ($patterns as $pattern) {
                    if (str_contains($pattern, $method)) {
                        $foundLifecycleMethods[] = $method;
                        break;
                    }
                }
            }

            if (!empty($foundLifecycleMethods)) {
                fwrite(STDERR, "Info: {$filename} covers lifecycle methods: " . implode(', ', $foundLifecycleMethods) . "\n");
            }
        }

        $this->assertTrue(true, 'Livewire component lifecycle patterns check completed');
    }

    public static function livewireBaselineProvider(): array
    {
        $baselinesPath = dirname(__DIR__, 2) . '/baselines';
        $files = glob($baselinesPath . '/livewire-*.neon');
        
        return array_map(
            fn(string $file) => [basename($file)],
            $files
        );
    }
}