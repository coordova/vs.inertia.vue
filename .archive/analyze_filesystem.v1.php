#!/usr/bin/env php
<?php

/**
 * analyze_filesystem.php
 * 
 * Analizador de estructura de directorios con soporte para filtros .gitignore, .avoid y .onlyfiles.
 * 
 * @author Senior Software Engineer
 * @version 1.1 (corregido)
 */

class FileSystemAnalyzer 
{
    private string $rootPath;
    private array $ignorePatterns = [];
    private array $avoidPatterns = [];
    private array $onlyExtensions = [];
    private array $fileStructure = [];

    public function __construct(string $path = null) 
    {
        $this->rootPath = $path ? realpath($path) : getcwd();

        if (!$this->rootPath || !is_dir($this->rootPath) || !is_readable($this->rootPath)) {
            $this->error("Ruta inválida o no legible: " . ($path ?? 'directorio actual'));
        }

        $this->loadIgnoreFiles();
    }

    private function error(string $message): void 
    {
        fwrite(STDERR, "ERROR: $message\n");
        exit(1);
    }

    private function loadIgnoreFiles(): void 
    {
        $gitignorePath = $this->rootPath . '/.gitignore';
        if (file_exists($gitignorePath) && is_readable($gitignorePath)) {
            $this->ignorePatterns = $this->parseIgnoreFile($gitignorePath);
        }

        $avoidPath = $this->rootPath . '/.avoid';
        if (file_exists($avoidPath) && is_readable($avoidPath)) {
            $this->avoidPatterns = $this->parseIgnoreFile($avoidPath);
        }

        $onlyfilesPath = $this->rootPath . '/.onlyfiles';
        if (file_exists($onlyfilesPath) && is_readable($onlyfilesPath)) {
            $this->onlyExtensions = $this->parseOnlyFiles($onlyfilesPath);
        }
    }

    private function parseIgnoreFile(string $filePath): array 
    {
        $patterns = [];
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) continue; // Ignorar comentarios y vacíos
            
            $patterns[] = $line;
        }
        
        return $patterns;
    }

    private function parseOnlyFiles(string $filePath): array 
    {
        $extensions = [];
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) continue;
            
            if (preg_match('/^\*(\.[^.]+)$/', $line, $matches)) {
                $extensions[] = strtolower(ltrim($matches[1], '.'));
            } elseif (preg_match('/^(\.[^.]+)$/', $line, $matches)) {
                $extensions[] = strtolower(ltrim($matches[1], '.'));
            }
        }
        
        return $extensions;
    }

    public function analyze(): void 
    {
        $this->fileStructure = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->rootPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $processedPaths = [];
        foreach ($iterator as $fileInfo) {
            $realPath = $fileInfo->getRealPath();
            
            // Prevenir bucles infinitos con enlaces simbólicos
            if (isset($processedPaths[$realPath])) continue;
            $processedPaths[$realPath] = true;

            $relativePath = substr($realPath, strlen($this->rootPath) + 1);

            if ($this->shouldIgnorePath($relativePath, $fileInfo->isDir())) continue;
            if ($fileInfo->isFile() && !empty($this->onlyExtensions) && !$this->shouldIncludeFile($fileInfo->getExtension())) continue;

            $this->addToStructure($relativePath, $fileInfo->isDir());
        }
    }

    private function shouldIgnorePath(string $relativePath, bool $isDir): bool 
    {
        $allPatterns = array_merge($this->ignorePatterns, $this->avoidPatterns);

        foreach ($allPatterns as $pattern) {
            if ($this->matchGitignorePattern($pattern, $relativePath, $isDir)) {
                return true;
            }
        }
        return false;
    }

    private function matchGitignorePattern(string $pattern, string $path, bool $isDir): bool 
    {
        $pattern = trim($pattern);
        $negate = false;
        if (substr($pattern, 0, 1) === '!') {
            $negate = true;
            $pattern = substr($pattern, 1);
        }

        // Soportar rutas absolutas (desde la raíz)
        $absolute = false;
        if (substr($pattern, 0, 1) === '/') {
            $absolute = true;
            $pattern = substr($pattern, 1);
        }

        $regexPattern = $this->convertGitignoreToRegex($pattern, $absolute);

        $match = (bool) preg_match($regexPattern, $path);

        // Si es directorio, también debe coincidir con el patrón de directorio
        if ($isDir && substr($pattern, -1) === '/') {
            if (substr($path, -1) !== '/') {
                $path .= '/';
            }
            $match = (bool) preg_match($regexPattern, $path);
        }

        return $negate ? !$match : $match;
    }

    private function convertGitignoreToRegex(string $pattern, bool $absolute): string 
    {
        // Escapar caracteres especiales de regex, excepto los usados en patrones tipo glob
        $pattern = preg_quote($pattern, '#'); // Cambié el delimitador de / a #
        $pattern = str_replace('\*\*', '.*', $pattern);
        $pattern = str_replace('\*', '[^/]*', $pattern);
        $pattern = str_replace('\?', '[^/]', $pattern);

        $start = $absolute ? '^' : '(?:^|/)'; // Cambié | por alternancia en agrupación no capturadora
        $end = ($absolute && substr($pattern, -1) !== '/') ? '(?:/|$)' : '(?:/.*)?$';

        return "#{$start}{$pattern}{$end}#";
    }

    private function shouldIncludeFile(string $extension): bool 
    {
        return in_array(strtolower($extension), $this->onlyExtensions);
    }

    private function addToStructure(string $relativePath, bool $isDir): void 
    {
        $parts = explode('/', $relativePath);
        $current = &$this->fileStructure;

        for ($i = 0; $i < count($parts); $i++) {
            $part = $parts[$i];
            $isLast = ($i === count($parts) - 1);

            if (!isset($current[$part])) {
                $current[$part] = $isLast && !$isDir ? null : [];
            }

            if (!$isLast || $isDir) {
                $current = &$current[$part];
            }
        }
    }

    public function generateTree(): string 
    {
        $output = '';
        $this->renderTree($this->fileStructure, 0, '', $output);
        return $output;
    }

    private function renderTree(array $structure, int $depth, string $prefix, string &$output): void 
    {
        $indent = str_repeat('    ', $depth);
        $index = 0;
        $total = count($structure);

        foreach ($structure as $name => $subtree) {
            $isLast = (++$index === $total);
            $connector = $isLast ? '└── ' : '├── ';
            $output .= $indent . $connector . $name . "\n";

            if (is_array($subtree)) {
                $newPrefix = $isLast ? $prefix . '    ' : $prefix . '│   ';
                $this->renderTree($subtree, $depth + 1, $newPrefix, $output);
            }
        }
    }

    public function saveToFile(): void 
    {
        $projectName = basename($this->rootPath);
        $timestamp = date('YmdHis');
        $filename = "oox-{$projectName}-{$timestamp}-filesystem.txt";
        $filepath = $this->rootPath . '/' . $filename;

        $content = $this->generateTree();
        if (file_put_contents($filepath, $content) === false) {
            $this->error("No se pudo escribir el archivo: $filepath");
        }

        echo "Archivo generado: $filename\n";
    }
}

// --- Ejecución del Script ---

if (php_sapi_name() !== 'cli') {
    die("Este script debe ejecutarse desde la línea de comandos.\n");
}

$path = $argc > 1 ? $argv[1] : null;
try {
    $analyzer = new FileSystemAnalyzer($path);
    $analyzer->analyze();
    $analyzer->saveToFile();
} catch (Exception $e) {
    fwrite(STDERR, "Excepción no capturada: " . $e->getMessage() . "\n");
    exit(1);
}