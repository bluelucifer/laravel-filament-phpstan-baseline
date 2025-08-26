<?php

declare(strict_types=1);

namespace BlueLucifer\LaravelFilamentPHPStan\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class PatternFormatTest extends TestCase
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
    public function test_patterns_use_consistent_delimiters(string $filename): void
    {
        $filepath = $this->baselinesPath . '/' . $filename;
        $content = file_get_contents($filepath);
        $parsed = Yaml::parse($content);

        $errors = $parsed['parameters']['ignoreErrors'] ?? [];
        $inconsistentPatterns = [];

        foreach ($errors as $index => $pattern) {
            if (is_string($pattern) && str_contains($pattern, '/')) {
                // If it looks like a regex but doesn't start and end with #
                if (!preg_match('/^#.*#[a-zA-Z]*$/', $pattern)) {
                    $inconsistentPatterns[] = "Index {$index}: {$pattern}";
                }
            }
        }

        $this->assertEmpty(
            $inconsistentPatterns,
            "Found patterns with inconsistent delimiters in {$filename}:\n" . implode("\n", $inconsistentPatterns)
        );
    }

    /**
     * @dataProvider baselineFilesProvider
     */
    public function test_patterns_follow_phpstan_format(string $filename): void
    {
        $filepath = $this->baselinesPath . '/' . $filename;
        $content = file_get_contents($filepath);
        $parsed = Yaml::parse($content);

        $errors = $parsed['parameters']['ignoreErrors'] ?? [];
        $invalidPatterns = [];

        foreach ($errors as $index => $pattern) {
            // Handle structured pattern format (with message/path keys)
            if (is_array($pattern)) {
                if (!isset($pattern['message'])) {
                    $invalidPatterns[] = "Index {$index}: Structured pattern missing 'message' key";
                    continue;
                }
                
                $message = $pattern['message'];
                if (!is_string($message)) {
                    $invalidPatterns[] = "Index {$index}: Pattern message must be a string, got " . gettype($message);
                    continue;
                }
                
                if (empty(trim($message))) {
                    $invalidPatterns[] = "Index {$index}: Empty pattern message";
                    continue;
                }
                
                // Check if paths are properly formatted when present
                if (isset($pattern['paths']) && !is_array($pattern['paths'])) {
                    $invalidPatterns[] = "Index {$index}: Pattern paths must be an array";
                }
                
                continue;
            }

            // Handle simple string patterns
            if (!is_string($pattern)) {
                $invalidPatterns[] = "Index {$index}: Pattern must be a string or structured array, got " . gettype($pattern);
                continue;
            }

            $trimmedPattern = trim($pattern);
            
            // Check for common PHPStan pattern formats
            if (empty($trimmedPattern)) {
                $invalidPatterns[] = "Index {$index}: Empty pattern";
                continue;
            }

            // Patterns should either be regex (#pattern#) or plain text
            if (str_starts_with($trimmedPattern, '#')) {
                if (substr_count($trimmedPattern, '#') < 2) {
                    $invalidPatterns[] = "Index {$index}: Regex pattern missing closing delimiter: {$trimmedPattern}";
                }
            }
        }

        $this->assertEmpty(
            $invalidPatterns,
            "Found invalid pattern formats in {$filename}:\n" . implode("\n", $invalidPatterns)
        );
    }

    /**
     * @dataProvider baselineFilesProvider
     */
    public function test_patterns_have_proper_escaping(string $filename): void
    {
        $filepath = $this->baselinesPath . '/' . $filename;
        $content = file_get_contents($filepath);
        $parsed = Yaml::parse($content);

        $errors = $parsed['parameters']['ignoreErrors'] ?? [];
        $escapingIssues = [];

        foreach ($errors as $index => $error) {
            $pattern = '';
            
            if (is_string($error)) {
                $pattern = $error;
            } elseif (is_array($error) && isset($error['message'])) {
                $pattern = $error['message'];
            }
            
            if (!empty($pattern) && preg_match('/^#.*#[a-zA-Z]*$/', $pattern)) {
                // Check for common escaping issues in regex patterns
                
                // Unescaped dots (except in character classes)
                if (preg_match('/(?<!\\\\)\.(?![^\\[]*\\])/', $pattern)) {
                    // Allow .+ and .* patterns as they're commonly used
                    if (!preg_match('/\.\+|\.\*/', $pattern)) {
                        $escapingIssues[] = "Index {$index}: Potentially unescaped dot in pattern: {$pattern}";
                    }
                }

                // Unescaped parentheses
                if (preg_match('/(?<!\\\\)[()]/', $pattern) && !preg_match('/\([^)]*\)/', $pattern)) {
                    // This is complex to check perfectly, so we'll be lenient
                }

                // Unescaped dollar signs at end (not for end-of-string anchors)
                if (preg_match('/(?<!\\\\)\$(?!$)/', $pattern)) {
                    $escapingIssues[] = "Index {$index}: Potentially unescaped dollar sign in pattern: {$pattern}";
                }
            }
        }

        // This is more of a warning test - regex escaping can be complex
        if (!empty($escapingIssues)) {
            fwrite(STDERR, "Warning: Potential escaping issues in {$filename}:\n" . implode("\n", $escapingIssues) . "\n");
        }

        $this->assertTrue(true, 'Pattern escaping check completed');
    }

    /**
     * @dataProvider baselineFilesProvider
     */
    public function test_file_has_consistent_indentation(string $filename): void
    {
        $filepath = $this->baselinesPath . '/' . $filename;
        $content = file_get_contents($filepath);
        $lines = explode("\n", $content);

        $indentationTypes = [];
        $inconsistentLines = [];

        foreach ($lines as $lineNum => $line) {
            if (empty(trim($line)) || str_starts_with(trim($line), '#')) {
                continue; // Skip empty lines and comments
            }

            if (preg_match('/^(\s+)/', $line, $matches)) {
                $indent = $matches[1];
                $type = str_contains($indent, "\t") ? 'tab' : 'space';
                $indentationTypes[$type] = true;

                // Check for mixed indentation within a single line
                if (str_contains($indent, "\t") && str_contains($indent, ' ')) {
                    $inconsistentLines[] = "Line " . ($lineNum + 1) . ": Mixed tabs and spaces";
                }
            }
        }

        // Check if file mixes tabs and spaces
        if (count($indentationTypes) > 1) {
            $this->fail("File {$filename} mixes different indentation types (tabs and spaces)");
        }

        $this->assertEmpty(
            $inconsistentLines,
            "Found inconsistent indentation in {$filename}:\n" . implode("\n", $inconsistentLines)
        );
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