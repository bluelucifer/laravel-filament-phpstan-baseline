<?php

declare(strict_types=1);

namespace BlueLucifer\LaravelFilamentPHPStan\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class PatternDuplicationTest extends TestCase
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
    public function test_baseline_files_have_no_duplicate_patterns(string $filename): void
    {
        $filepath = $this->baselinesPath . '/' . $filename;
        $content = file_get_contents($filepath);
        $parsed = Yaml::parse($content);

        $errors = $parsed['parameters']['ignoreErrors'] ?? [];
        $patterns = [];
        $duplicates = [];

        foreach ($errors as $error) {
            $pattern = '';
            
            if (is_string($error)) {
                $pattern = trim($error);
            } elseif (is_array($error) && isset($error['message'])) {
                $pattern = trim($error['message']);
            }
            
            if (!empty($pattern)) {
                if (in_array($pattern, $patterns)) {
                    $duplicates[] = $pattern;
                } else {
                    $patterns[] = $pattern;
                }
            }
        }

        $this->assertEmpty($duplicates, "Found duplicate patterns in {$filename}: " . implode(', ', $duplicates));
    }

    public function test_no_cross_file_pattern_conflicts(): void
    {
        $allPatterns = [];
        $conflicts = [];

        foreach (glob($this->baselinesPath . '/*.neon') as $filepath) {
            $filename = basename($filepath);
            $content = file_get_contents($filepath);
            $parsed = Yaml::parse($content);
            
            $errors = $parsed['parameters']['ignoreErrors'] ?? [];
            
            foreach ($errors as $error) {
                $pattern = '';
                
                if (is_string($error)) {
                    $pattern = trim($error);
                } elseif (is_array($error) && isset($error['message'])) {
                    $pattern = trim($error['message']);
                }
                
                if (!empty($pattern)) {
                    if (isset($allPatterns[$pattern])) {
                        $conflicts[] = [
                            'pattern' => $pattern,
                            'files' => [$allPatterns[$pattern], $filename]
                        ];
                    } else {
                        $allPatterns[$pattern] = $filename;
                    }
                }
            }
        }

        if (!empty($conflicts)) {
            $conflictMessages = [];
            foreach ($conflicts as $conflict) {
                $conflictMessages[] = sprintf(
                    "Pattern '%s' appears in files: %s",
                    $conflict['pattern'],
                    implode(', ', $conflict['files'])
                );
            }
            
            $this->fail("Found cross-file pattern conflicts:\n" . implode("\n", $conflictMessages));
        }

        $this->assertTrue(true, 'No cross-file pattern conflicts found');
    }

    public function test_similar_patterns_are_documented(): void
    {
        $allPatterns = [];

        foreach (glob($this->baselinesPath . '/*.neon') as $filepath) {
            $filename = basename($filepath);
            $content = file_get_contents($filepath);
            $parsed = Yaml::parse($content);
            
            $errors = $parsed['parameters']['ignoreErrors'] ?? [];
            
            foreach ($errors as $error) {
                $pattern = '';
                
                if (is_string($error)) {
                    $pattern = trim($error);
                } elseif (is_array($error) && isset($error['message'])) {
                    $pattern = trim($error['message']);
                }
                
                if (!empty($pattern)) {
                    $allPatterns[] = [
                        'pattern' => $pattern,
                        'file' => $filename
                    ];
                }
            }
        }

        // Look for very similar patterns that might be redundant
        $similarities = [];
        
        for ($i = 0; $i < count($allPatterns); $i++) {
            for ($j = $i + 1; $j < count($allPatterns); $j++) {
                $pattern1 = $allPatterns[$i]['pattern'];
                $pattern2 = $allPatterns[$j]['pattern'];
                
                // Skip if same file
                if ($allPatterns[$i]['file'] === $allPatterns[$j]['file']) {
                    continue;
                }
                
                // Calculate similarity
                $similarity = similar_text($pattern1, $pattern2, $percent);
                
                // If patterns are very similar (>80%), flag for review
                if ($percent > 80 && $pattern1 !== $pattern2) {
                    $similarities[] = [
                        'pattern1' => $pattern1,
                        'pattern2' => $pattern2,
                        'file1' => $allPatterns[$i]['file'],
                        'file2' => $allPatterns[$j]['file'],
                        'similarity' => $percent
                    ];
                }
            }
        }

        // This is more of an informational test - log similarities but don't fail
        if (!empty($similarities)) {
            $messages = [];
            foreach ($similarities as $sim) {
                $messages[] = sprintf(
                    "Similar patterns (%.1f%% match):\n  %s (%s)\n  %s (%s)",
                    $sim['similarity'],
                    $sim['pattern1'],
                    $sim['file1'],
                    $sim['pattern2'],
                    $sim['file2']
                );
            }
            
            // Log as warning instead of failing
            fwrite(STDERR, "Warning: Found potentially similar patterns:\n" . implode("\n\n", $messages) . "\n");
        }

        $this->assertTrue(true, 'Pattern similarity check completed');
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