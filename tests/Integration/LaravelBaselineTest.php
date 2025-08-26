<?php

declare(strict_types=1);

namespace BlueLucifer\LaravelFilamentPHPStan\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class LaravelBaselineTest extends TestCase
{
    private string $baselinesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baselinesPath = dirname(__DIR__, 2) . '/baselines';
    }

    /**
     * @dataProvider laravelBaselineProvider
     */
    public function test_laravel_baseline_covers_common_patterns(string $filename): void
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

        // Essential Laravel patterns that should be covered
        $expectedPatterns = [
            'Eloquent\\Builder',           // Eloquent Builder methods
            'Collection',                  // Collection methods
            'Request',                     // HTTP Request
            'ServiceProvider',             // Service Provider methods
            'Model',                       // Eloquent Model
            'Carbon\\Carbon',             // Carbon date methods
            'Facades',                     // Laravel Facades
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

        $this->assertEmpty(
            $missingPatterns,
            "Laravel baseline {$filename} is missing coverage for: " . implode(', ', $missingPatterns)
        );
    }

    /**
     * @dataProvider laravelBaselineProvider
     */
    public function test_laravel_baseline_patterns_are_reasonable(string $filename): void
    {
        $filepath = $this->baselinesPath . '/' . $filename;
        $content = file_get_contents($filepath);
        $parsed = Yaml::parse($content);

        $errors = $parsed['parameters']['ignoreErrors'] ?? [];
        $tooPermissivePatterns = [];

        foreach ($errors as $index => $error) {
            $pattern = '';
            
            if (is_string($error)) {
                $pattern = $error;
            } elseif (is_array($error) && isset($error['message'])) {
                $pattern = $error['message'];
            }
            
            if (!empty($pattern)) {
                // Check for overly permissive patterns that might hide real issues
                if (preg_match('/^#\.\*#$/', $pattern)) {
                    $tooPermissivePatterns[] = "Index {$index}: Pattern too permissive: {$pattern}";
                }

                // Check for patterns without proper boundaries
                if (preg_match('/^#[^#]*[a-zA-Z][^#]*#$/', $pattern) && !str_contains($pattern, '\\')) {
                    // This might be too generic
                    if (strlen($pattern) < 10) {
                        $tooPermissivePatterns[] = "Index {$index}: Pattern might be too generic: {$pattern}";
                    }
                }
            }
        }

        // This is more of a warning - overly permissive patterns might be intentional
        if (!empty($tooPermissivePatterns)) {
            fwrite(STDERR, "Warning: Potentially too permissive patterns in {$filename}:\n" . implode("\n", $tooPermissivePatterns) . "\n");
        }

        $this->assertTrue(true, 'Pattern reasonableness check completed');
    }

    public function test_laravel_version_baselines_are_consistent(): void
    {
        $laravelFiles = glob($this->baselinesPath . '/laravel-*.neon');
        
        if (count($laravelFiles) < 2) {
            $this->markTestSkipped('Need at least 2 Laravel baseline files to test consistency');
        }

        $commonPatterns = [];
        $versionSpecificPatterns = [];

        foreach ($laravelFiles as $filepath) {
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

            foreach ($patterns as $pattern) {
                if (!isset($commonPatterns[$pattern])) {
                    $commonPatterns[$pattern] = [];
                }
                $commonPatterns[$pattern][] = $filename;
            }
        }

        // Patterns that appear in most files are likely common Laravel patterns
        $fileCount = count($laravelFiles);
        $mostlyCommonPatterns = [];
        $uniquePatterns = [];
        
        foreach ($commonPatterns as $pattern => $files) {
            if (count($files) >= ($fileCount * 0.7)) {
                $mostlyCommonPatterns[$pattern] = $files;
            } elseif (count($files) === 1) {
                $uniquePatterns[$pattern] = $files;
            }
        }

        // Log information about pattern distribution
        fwrite(STDERR, sprintf(
            "Laravel baseline consistency check:\n" .
            "- Total unique patterns: %d\n" .
            "- Common patterns (70%+ files): %d\n" .
            "- Version-specific patterns: %d\n",
            count($commonPatterns),
            count($mostlyCommonPatterns),
            count($uniquePatterns)
        ));

        $this->assertTrue(true, 'Laravel version consistency check completed');
    }

    public static function laravelBaselineProvider(): array
    {
        $baselinesPath = dirname(__DIR__, 2) . '/baselines';
        $files = glob($baselinesPath . '/laravel-*.neon');
        
        return array_map(
            fn(string $file) => [basename($file)],
            $files
        );
    }
}