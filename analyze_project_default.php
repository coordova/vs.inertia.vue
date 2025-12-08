#!/usr/bin/env php
<?php

/**
 * Filesystem Structure and Codebase Analyzer
 * 
 * Advanced filesystem analyzer with intelligent filtering
 * based on .gitignore, .avoid, and .onlyfiles patterns.
 * 
 * @author Senior Software Engineer
 * @version 2.1
 * @license MIT
 */

class FilesystemStructureAnalyzer
{
    private string $rootPath;
    private array $gitignorePatterns = [];
    private array $avoidPatterns = [];
    private array $onlyfilesPatterns = [];
    private array $defaultExclusions = [
        // Dependencies and build tools
        'node_modules/',
        'vendor/',
        'bower_components/',
        
        // Cache and temporary files
        '.cache/',
        'cache/',
        'tmp/',
        'temp/',
        'logs/',
        'storage/logs/',
        'bootstrap/cache/',
        'storage/framework/cache/',
        
        // Version control
        '.git/',
        '.svn/',
        '.hg/',
        
        // IDE and editor files
        '.vscode/',
        '.idea/',
        '*.swp',
        '*.swo',
        '.DS_Store',
        'Thumbs.db',
        
        // Lock files
        'package-lock.json',
        'yarn.lock',
        'pnpm-lock.yaml',
        'composer.lock',
        
        // Build and distribution
        'dist/',
        'build/',
        'public/build/',
        'assets/build/',
        
        // OS files
        '.AppleDouble/',
        '.Trashes',
        '.Spotlight-V100',
        '.fseventsd',
        
        // Environment files
        '.env',
        '.env.local',
        '.env.*.local',
        
        // Temporary and log files
        '*.log',
        '*.tmp',
        '*.temp',
        '*.cache',
        '*.lock',
    ];

    public function __construct()
    {
        $this->setDefaultExclusions();
    }

    private function setDefaultExclusions(): void
    {
        // Combine default exclusions with additional common patterns
        $this->defaultExclusions = array_merge($this->defaultExclusions, [
            // Laravel specific
            'storage/',
            'bootstrap/cache/',
            'public/storage/',
            
            // Node.js specific
            'node_modules/',
            '.nuxt/',
            '.next/',
            '.vercel/',
            
            // PHP specific
            'vendor/',
            
            // General temp/cache
            'cache/',
            'tmp/',
            'temp/',
            '*.tmp',
            '*.temp',
            '*.cache',
        ]);
    }

    public function run(?string $path = null): void
    {
        $this->rootPath = $path ?? getcwd();
        
        // Validate the path
        if (!$this->isValidPath($this->rootPath)) {
            echo "Error: Invalid directory path: {$this->rootPath}\n";
            exit(1);
        }
        
        $this->rootPath = realpath($this->rootPath);
        
        // Load configuration files
        $this->loadGitignore();
        $this->loadAvoid();
        $this->loadOnlyfiles();
        
        // Ask user for operation
        $option = $this->getUserOption();
        
        switch ($option) {
            case 1:
                $this->analyzeFileStructure();
                break;
            case 2:
                $this->analyzeCodebase();
                break;
            case 3:
                $this->analyzeFileStructure();
                $this->analyzeCodebase();
                break;
            default:
                echo "Invalid option selected.\n";
                exit(1);
        }
    }

    private function getUserOption(): int
    {
        echo "Select an option:\n";
        echo "1. Create filesystem structure file\n";
        echo "2. Create codebase file (all project code in one file)\n";
        echo "3. Create both files\n";
        echo "Enter option (1-3): ";
        
        $handle = fopen("php://stdin", "r");
        $input = trim(fgets($handle));
        fclose($handle);
        
        $option = (int)$input;
        
        if (!in_array($option, [1, 2, 3])) {
            echo "Invalid option. Please select 1, 2, or 3.\n";
            return $this->getUserOption();
        }
        
        return $option;
    }

    private function isValidPath(string $path): bool
    {
        if (!is_dir($path)) {
            return false;
        }
        
        if (!is_readable($path)) {
            return false;
        }
        
        // Prevent path traversal
        $realPath = realpath($path);
        if ($realPath === false) {
            return false;
        }
        
        return true;
    }

    private function loadGitignore(): void
    {
        $gitignorePath = $this->rootPath . DIRECTORY_SEPARATOR . '.gitignore';
        if (file_exists($gitignorePath) && is_readable($gitignorePath)) {
            $this->parseIgnoreFile($gitignorePath, $this->gitignorePatterns);
        }
    }

    private function loadAvoid(): void
    {
        $avoidPath = $this->rootPath . DIRECTORY_SEPARATOR . '.avoid';
        if (file_exists($avoidPath) && is_readable($avoidPath)) {
            $this->parseIgnoreFile($avoidPath, $this->avoidPatterns);
        }
    }

    private function loadOnlyfiles(): void
    {
        $onlyfilesPath = $this->rootPath . DIRECTORY_SEPARATOR . '.onlyfiles';
        if (file_exists($onlyfilesPath) && is_readable($onlyfilesPath)) {
            $lines = file($onlyfilesPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line && !str_starts_with($line, '#')) {
                    $this->onlyfilesPatterns[] = $line;
                }
            }
        }
    }

    private function parseIgnoreFile(string $filePath, array &$patterns): void
    {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments and empty lines
            if (!$line || str_starts_with($line, '#')) {
                continue;
            }
            
            // Add pattern
            $patterns[] = $line;
        }
    }

    private function analyzeFileStructure(): void
    {
        echo "Generating filesystem structure...\n";
        
        $structure = $this->generateStructure();
        
        // Create output filename
        $projectName = basename($this->rootPath);
        $timestamp = date('Ymd-His');
        $filename = "oox-{$projectName}-{$timestamp}-filesystem.txt";
        $outputPath = $this->rootPath . DIRECTORY_SEPARATOR . $filename;
        
        // Write the structure to file
        try {
            file_put_contents($outputPath, $structure);
            echo "File structure saved to: {$outputPath}\n";
        } catch (Exception $e) {
            echo "Error writing to file: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    private function generateStructure(): string
    {
        $structure = basename($this->rootPath) . "\n";
        $items = $this->scanDirectory($this->rootPath, 1);
        $structure .= $items;
        
        return $structure;
    }

    private function scanDirectory(string $dir, int $depth, string $basePath = ''): string
    {
        $output = '';
        $items = array_diff(scandir($dir), ['.', '..']);
        
        // Separate files and directories
        $files = [];
        $dirs = [];
        
        foreach ($items as $item) {
            $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
            
            // Calculate relative path from root
            $relativePath = $basePath . ($basePath ? DIRECTORY_SEPARATOR : '') . $item;
            $relativePath = str_replace('\\', '/', $relativePath); // Normalize separators
            
            if (is_dir($fullPath)) {
                // Check if directory should be excluded
                if ($this->shouldExclude($relativePath . '/', true)) {
                    continue; // Skip entire directory
                }
                
                $dirs[] = $item;
            } else {
                // Check if file should be excluded
                if ($this->shouldExclude($relativePath, false)) {
                    continue; // Skip file
                }
                
                // Check if file matches onlyfiles patterns
                if ($this->shouldIncludeFile($item)) {
                    $files[] = $item;
                }
            }
        }
        
        // Process directories first
        foreach ($dirs as $dirName) {
            $fullPath = $dir . DIRECTORY_SEPARATOR . $dirName;
            $relativePath = $basePath . ($basePath ? DIRECTORY_SEPARATOR : '') . $dirName;
            $relativePath = str_replace('\\', '/', $relativePath); // Normalize separators
            
            // Add directory with proper indentation
            $output .= str_repeat('    ', $depth) . $dirName . "\n";
            
            // Recursively scan subdirectory
            $subItems = $this->scanDirectory($fullPath, $depth + 1, $relativePath);
            $output .= $subItems;
        }
        
        // Process files
        foreach ($files as $fileName) {
            $output .= str_repeat('    ', $depth) . $fileName . "\n";
        }
        
        return $output;
    }

    private function analyzeCodebase(): void
    {
        echo "Generating codebase file...\n";
        
        $codebase = $this->generateCodebase();
        
        // Create output filename
        $projectName = basename($this->rootPath);
        $timestamp = date('Ymd-His');
        $filename = "oox-{$projectName}-{$timestamp}-codebase.txt";
        $outputPath = $this->rootPath . DIRECTORY_SEPARATOR . $filename;
        
        // Write the codebase to file
        try {
            file_put_contents($outputPath, $codebase);
            echo "Codebase saved to: {$outputPath}\n";
        } catch (Exception $e) {
            echo "Error writing to file: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    private function generateCodebase(): string
    {
        $codebase = '';
        $files = $this->getAllValidFiles();
        
        foreach ($files as $file) {
            $relativePath = str_replace($this->rootPath . DIRECTORY_SEPARATOR, '', $file);
            $relativePath = str_replace('\\', '/', $relativePath); // Normalize separators
            
            // Add header
            $codebase .= "/*-------------------------------------------------------------*/\n";
            $codebase .= "/* {$relativePath} */\n";
            $codebase .= "/*-------------------------------------------------------------*/\n";
            
            // Read and add file content
            try {
                $content = file_get_contents($file);
                
                // Ensure proper encoding
                $content = mb_convert_encoding($content, 'UTF-8', 'auto');
                
                $codebase .= $content;
            } catch (Exception $e) {
                echo "Warning: Could not read file {$file}: " . $e->getMessage() . "\n";
                $codebase .= "/* ERROR: Could not read file content */\n";
            }
            
            // Add a newline at the end of each file's content
            $codebase .= "\n";
        }
        
        return $codebase;
    }

    private function getAllValidFiles(?string $dir = null, array &$files = []): array
    {
        if ($dir === null) {
            $dir = $this->rootPath;
            $files = [];
        }
        
        $items = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($items as $item) {
            $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
            
            // Calculate relative path from root
            $relativePath = str_replace($this->rootPath . DIRECTORY_SEPARATOR, '', $fullPath);
            $relativePath = str_replace('\\', '/', $relativePath); // Normalize separators
            
            if (is_dir($fullPath)) {
                // Check if directory should be excluded
                if ($this->shouldExclude($relativePath . '/', true)) {
                    continue; // Skip entire directory
                }
                
                // Recursively scan subdirectory
                $this->getAllValidFiles($fullPath, $files);
            } else {
                // Check if file should be excluded
                if ($this->shouldExclude($relativePath, false)) {
                    continue; // Skip file
                }
                
                // Check if file matches onlyfiles patterns
                if ($this->shouldIncludeFile($item)) {
                    $files[] = $fullPath;
                }
            }
        }
        
        return $files;
    }

    private function shouldExclude(string $relativePath, bool $isDir): bool
    {
        // Check against default exclusions
        foreach ($this->defaultExclusions as $pattern) {
            if ($this->matchGitignorePattern($relativePath, $pattern, $isDir)) {
                return true;
            }
        }
        
        // Check against .gitignore patterns
        foreach ($this->gitignorePatterns as $pattern) {
            if ($this->matchGitignorePattern($relativePath, $pattern, $isDir)) {
                return true;
            }
        }
        
        // Check against .avoid patterns
        foreach ($this->avoidPatterns as $pattern) {
            if ($this->matchGitignorePattern($relativePath, $pattern, $isDir)) {
                return true;
            }
        }
        
        return false;
    }

    private function shouldIncludeFile(string $filename): bool
    {
        // If no .onlyfiles patterns defined, include all files
        if (empty($this->onlyfilesPatterns)) {
            return true;
        }
        
        // Check if file matches any of the .onlyfiles patterns
        foreach ($this->onlyfilesPatterns as $pattern) {
            if ($this->matchGitignorePattern($filename, $pattern, false)) {
                return true;
            }
        }
        
        return false;
    }

    private function matchGitignorePattern(string $path, string $pattern, bool $isDir): bool
    {
        // Normalize path separators
        $path = str_replace('\\', '/', $path);
        
        // Handle directory-specific patterns (ending with /)
        $patternIsDir = str_ends_with($pattern, '/');
        if ($patternIsDir && !$isDir) {
            return false; // Directory patterns don't match files
        }
        
        // Clean up pattern
        $cleanPattern = rtrim($pattern, '/');
        
        // Determine if pattern is absolute (starts with /)
        $isAbsolute = str_starts_with($cleanPattern, '/');
        $cleanPattern = ltrim($cleanPattern, '/');
        
        // Split into segments for matching
        $pathSegments = explode('/', $path);
        $patternSegments = explode('/', $cleanPattern);
        
        // If pattern is absolute, it must match from the beginning
        if ($isAbsolute) {
            // Absolute patterns match the entire path
            return $this->matchSegment($path, $cleanPattern, $isDir);
        } else {
            // Relative patterns can match anywhere in the path
            // Check if the pattern matches at any level
            
            // Try matching the full pattern against the whole path
            if ($this->matchSegment($path, $cleanPattern, $isDir)) {
                return true;
            }
            
            // For patterns like "storage/debugbar/", check if path starts with pattern
            if ($patternIsDir && str_starts_with($path, $cleanPattern . '/')) {
                return true;
            }
            
            // For patterns like "*.log", check individual segments
            if (strpos($cleanPattern, '*') !== false) {
                foreach ($pathSegments as $segment) {
                    if ($this->simpleWildcardMatch($segment, $cleanPattern)) {
                        return true;
                    }
                }
            }
            
            // Check if any trailing part of the path matches the pattern
            $pathLen = count($pathSegments);
            $patternLen = count($patternSegments);
            
            for ($i = 0; $i <= $pathLen - $patternLen; $i++) {
                $subPath = implode('/', array_slice($pathSegments, $i, $patternLen));
                if ($this->matchSegment($subPath, $cleanPattern, $isDir)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    private function matchSegment(string $path, string $pattern, bool $isDir): bool
    {
        // Handle directory markers
        $pathForMatching = $path;
        if ($isDir && !str_ends_with($pathForMatching, '/')) {
            $pathForMatching .= '/';
        }
        
        // If pattern ends with /, we're matching a directory
        if (str_ends_with($pattern, '/')) {
            $pattern = rtrim($pattern, '/');
            // Path must start with the pattern followed by / or be exactly the pattern
            if ($path === $pattern || str_starts_with($path, $pattern . '/')) {
                return true;
            }
        } else {
            // Direct match or wildcard match
            if ($this->simpleWildcardMatch(basename($pathForMatching), $pattern)) {
                return true;
            }
            
            // For paths like "storage/debugbar" vs pattern "debugbar"
            if (basename($pathForMatching) === $pattern) {
                return true;
            }
            
            // If path starts with pattern and pattern doesn't contain wildcards
            if (strpos($pattern, '*') === false && strpos($pattern, '?') === false) {
                if ($path === $pattern || str_starts_with($path, $pattern . '/') || str_starts_with($path, $pattern . '\\')) {
                    return true;
                }
            }
        }
        
        // Use simple wildcard matching
        return $this->simpleWildcardMatch($pathForMatching, $pattern);
    }

    private function simpleWildcardMatch(string $text, string $pattern): bool
    {
        // Escape regex special chars except our wildcards
        $escapedPattern = preg_quote($pattern, '/');
        
        // Convert our wildcards back
        $regexPattern = str_replace(['\*', '\?'], ['.*', '.'], $escapedPattern);
        
        // Add start and end anchors
        $regex = '/^' . $regexPattern . '$/';
        
        return (bool) preg_match($regex, $text);
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    $analyzer = new FilesystemStructureAnalyzer();
    
    // Get directory from command line argument or use current directory
    $directory = $argc > 1 ? $argv[1] : null;
    
    $analyzer->run($directory);
} else {
    echo "This script can only be run from the command line.\n";
    exit(1);
}