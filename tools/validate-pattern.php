#!/usr/bin/env php
<?php
/**
 * Pattern Validation Tool
 * 
 * Validates PHPStan baseline patterns for correctness and efficiency.
 * 
 * Usage:
 *   php tools/validate-pattern.php <pattern>
 *   php tools/validate-pattern.php --file <baseline.neon>
 */

if ($argc < 2) {
    echo "Usage: php tools/validate-pattern.php <pattern>\n";
    echo "       php tools/validate-pattern.php --file <baseline.neon>\n";
    exit(1);
}

class PatternValidator
{
    private array $errors = [];
    private array $warnings = [];

    public function validatePattern(string $pattern): bool
    {
        $this->errors = [];
        $this->warnings = [];

        // Check if pattern is a valid regex
        if (!$this->isValidRegex($pattern)) {
            $this->errors[] = "Invalid regular expression: $pattern";
            return false;
        }

        // Check for common issues
        $this->checkForCommonIssues($pattern);

        return empty($this->errors);
    }

    private function isValidRegex(string $pattern): bool
    {
        return @preg_match($pattern, '') !== false;
    }

    private function checkForCommonIssues(string $pattern): void
    {
        // Check for overly broad patterns
        if ($pattern === '#.*#' || $pattern === '#.+#') {
            $this->errors[] = "Pattern is too broad and will match everything";
        }

        // Check for missing anchors
        if (strpos($pattern, '^') === false && strpos($pattern, '$') === false) {
            $this->warnings[] = "Pattern lacks anchors (^ or $), may match unintended text";
        }

        // Check for unescaped dots
        if (preg_match('/[^\\\\]\./', $pattern)) {
            $this->warnings[] = "Unescaped dot (.) found, will match any character";
        }

        // Check for greedy quantifiers
        if (preg_match('/\.\*(?!\?)/', $pattern) || preg_match('/\.\+(?!\?)/', $pattern)) {
            $this->warnings[] = "Greedy quantifier found, consider using lazy quantifier (.*? or .+?)";
        }

        // Check for unnecessary escaping
        if (preg_match('/\\\\[a-zA-Z0-9]/', $pattern)) {
            $this->warnings[] = "Possibly unnecessary escaping found";
        }
    }

    public function validateFile(string $filename): bool
    {
        if (!file_exists($filename)) {
            $this->errors[] = "File not found: $filename";
            return false;
        }

        $content = file_get_contents($filename);
        $patterns = [];

        // Extract patterns from NEON file
        if (preg_match_all("/message:\s*'([^']+)'/", $content, $matches)) {
            $patterns = array_merge($patterns, $matches[1]);
        }
        if (preg_match_all('/message:\s*"([^"]+)"/', $content, $matches)) {
            $patterns = array_merge($patterns, $matches[1]);
        }

        $allValid = true;
        foreach ($patterns as $index => $pattern) {
            if (!$this->validatePattern($pattern)) {
                echo "Pattern " . ($index + 1) . " is invalid: $pattern\n";
                $allValid = false;
            }
        }

        return $allValid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function printReport(): void
    {
        if (!empty($this->errors)) {
            echo "\n❌ ERRORS:\n";
            foreach ($this->errors as $error) {
                echo "   - $error\n";
            }
        }

        if (!empty($this->warnings)) {
            echo "\n⚠️  WARNINGS:\n";
            foreach ($this->warnings as $warning) {
                echo "   - $warning\n";
            }
        }

        if (empty($this->errors) && empty($this->warnings)) {
            echo "✅ Pattern is valid and well-formed!\n";
        }
    }
}

// Main execution
$validator = new PatternValidator();

if ($argv[1] === '--file') {
    if (!isset($argv[2])) {
        echo "Error: Filename required after --file\n";
        exit(1);
    }
    
    echo "Validating file: {$argv[2]}\n";
    echo str_repeat('=', 50) . "\n";
    
    if ($validator->validateFile($argv[2])) {
        echo "\n✅ All patterns in file are valid!\n";
    } else {
        $validator->printReport();
        exit(1);
    }
} else {
    $pattern = $argv[1];
    echo "Validating pattern: $pattern\n";
    echo str_repeat('=', 50) . "\n";
    
    $validator->validatePattern($pattern);
    $validator->printReport();
    
    if (!empty($validator->getErrors())) {
        exit(1);
    }
}