<?php

/**
 * Sistema de Análisis de Estructura de Directorios en PHP Puro - Versión Definitiva
 * Analiza la estructura de un directorio, aplica filtros basados en archivos de configuración
 * y genera un archivo de texto con el árbol de directorios.
 */

// --- Funciones Principales ---

/**
 * Obtiene la ruta del directorio a analizar.
 * @return string
 */
function getInputDirectory(): string
{
    global $argv;
    $dir = $argv[1] ?? getcwd(); // Usa el argumento o el directorio actual
    return rtrim($dir, DIRECTORY_SEPARATOR);
}

/**
 * Lee los archivos de configuración .gitignore, .avoid, .onlyfiles.
 * @param string $dir Raíz del proyecto.
 * @return array
 */
function readIgnoreFiles(string $dir): array
{
    $gitignoreContent = file_exists($dir . '/.gitignore') ? file_get_contents($dir . '/.gitignore') : '';
    $avoidContent = file_exists($dir . '/.avoid') ? file_get_contents($dir . '/.avoid') : '';
    $onlyfilesContent = file_exists($dir . '/.onlyfiles') ? file_get_contents($dir . '/.onlyfiles') : '';

    return [
        'gitignore' => parseGitignore($gitignoreContent),
        'avoid' => array_filter(array_map('trim', explode("\n", $avoidContent))),
        'onlyfiles' => array_filter(array_map('trim', explode("\n", $onlyfilesContent)))
    ];
}

/**
 * Interpreta el contenido de .gitignore y devuelve un array de patrones.
 * @param string $content Contenido del archivo .gitignore.
 * @return array
 */
function parseGitignore(string $content): array
{
    $lines = array_filter(array_map('trim', explode("\n", $content)));
    $patterns = [];
    foreach ($lines as $line) {
        if (empty($line) || strpos($line, '#') === 0) continue; // Saltar líneas vacías y comentarios
        $patterns[] = $line;
    }
    return $patterns;
}

/**
 * Convierte un patrón glob de .gitignore en una expresión regular segura.
 * @param string $pattern Patrón glob de .gitignore.
 * @return string Expresión regular compilada.
 */
function compileGitignorePatternToRegex(string $pattern): string
{
    // Escapar caracteres que son especiales tanto en glob como en regex
    $pattern = preg_quote($pattern, '/');
    // Reemplazar los wildcards específicos de glob
    // **/ -> (?:.*\/)? (cualquier cosa con / opcional al final)
    // * -> [^/]* (cualquier cosa excepto /)
    // ? -> [^/]? (un carácter cualquiera excepto /)
    $pattern = str_replace(
        ['\*\*/', '\*', '\?'],
        ['(?:.*\/)?', '[^\/]*', '[^\/]'],
        $pattern
    );
    // Manejar negación si está presente (esto se evalúa en la función principal)
    // Ajustar el patrón para coincidir con la ruta relativa completa
    return '/^' . $pattern . '\/?$/'; // Añadir \/? para que coincida con directorios
}

/**
 * Verifica si una ruta (archivo o directorio) coincide con un patrón de .gitignore o .avoid.
 * @param string $relativePath Ruta relativa desde la raíz del proyecto.
 * @param array $patterns Patrones de ignorar.
 * @return bool
 */
function matchesIgnorePattern(string $relativePath, array $patterns): bool
{
    $basename = basename($relativePath);

    foreach ($patterns as $pattern) {
        $pattern = trim($pattern);
        if (empty($pattern)) continue;

        $negate = false;
        if (substr($pattern, 0, 1) === '!') {
            $negate = true;
            $pattern = substr($pattern, 1);
        }

        // Compilar el patrón a regex (esto se podría optimizar precompilando)
        $regex = compileGitignorePatternToRegex($pattern);

        $match = false;
        // Intentar hacer match con la ruta relativa completa
        if (@preg_match($regex, $relativePath)) {
             $match = true;
        } elseif (@preg_match($regex, $basename)) { // O con el nombre base
             $match = true;
        }

        if ($negate) {
            if ($match) return false; // Regla de exclusión negada -> incluir
        } else {
            if ($match) return true; // Regla de exclusión estándar -> excluir
        }
    }
    return false;
}

/**
 * Verifica si un archivo debe incluirse según las extensiones en .onlyfiles.
 * @param string $filename Nombre del archivo.
 * @param array $onlyExtensions Extensiones permitidas.
 * @return bool
 */
function shouldIncludeFile(string $filename, array $onlyExtensions): bool
{
    if (empty($onlyExtensions)) return true; // Si no hay restricciones, incluir todo

    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, $onlyExtensions);
}


/**
 * Construye la representación en árbol de la estructura de directorios.
 * @param string $dir Directorio raíz a analizar.
 * @param array $ignorePatterns Patrones de .gitignore.
 * @param array $avoidPatterns Patrones de .avoid.
 * @param array $onlyExtensions Extensiones de .onlyfiles.
 * @return string
 */
function buildTreeStructure(string $dir, array $ignorePatterns, array $avoidPatterns, array $onlyExtensions): string
{
    $tree = [];
    $allPatterns = array_merge($ignorePatterns, $avoidPatterns);

    try {
        $rdi = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS);
        
        // Definimos la función de filtro como una closure que captura las variables necesarias
        $filter = function (SplFileInfo $current, string $key, RecursiveDirectoryIterator $iterator) use ($dir, $allPatterns, $onlyExtensions) {
            
            $realPath = $current->getRealPath();
            // Asegurarse de que la ruta real esté dentro del directorio raíz para calcular la relativa correctamente
            if (strpos($realPath, $dir . DIRECTORY_SEPARATOR) !== 0 && $realPath !== $dir) {
                 // Esto puede suceder con enlaces simbólicos que apuntan fuera del árbol
                 // Si no está dentro, lo excluimos.
                 return false;
            }
            $relativePath = substr($realPath, strlen($dir) + 1); // Ruta relativa desde la raíz

            // Verificar si la ruta actual coincide con un patrón de ignorar
            if (matchesIgnorePattern($relativePath, $allPatterns)) {
                return false; // Excluir archivo/directorio
            }

            // Si es archivo, verificar extensión
            if ($current->isFile()) {
                return shouldIncludeFile($current->getFilename(), $onlyExtensions);
            }

            // Si es directorio, se acepta para seguir iterando, a menos que ya esté excluido arriba
            return true;
        };

        $iterator = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator($rdi, $filter),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $fileInfo) {
            // Si llega aquí, ha pasado todos los filtros
            $realPath = $fileInfo->getRealPath();
            // Recalcular la ruta relativa para la impresión
            $relativePath = substr($realPath, strlen($dir) + 1);
            $depth = $iterator->getDepth();
            $indent = str_repeat('    ', $depth);
            $name = $fileInfo->getFilename();

            $tree[] = $indent . $name;
        }
    } catch (UnexpectedValueException $e) {
         // Captura errores como directorios no legibles
         fwrite(STDERR, "Error al recorrer el directorio: " . $e->getMessage() . "\n");
         exit(1);
     } catch (Exception $e) {
         fwrite(STDERR, "Error inesperado al recorrer el directorio: " . $e->getMessage() . "\n");
         exit(1);
     }

    return implode("\n", $tree);
}

/**
 * Escribe la estructura de árbol en el archivo de salida.
 * @param string $tree Estructura generada.
 * @param string $projectRoot Raíz del directorio analizado.
 * @param string $projectName Nombre del directorio.
 */
function writeOutputFile(string $tree, string $projectRoot, string $projectName): void
{
    $filename = $projectRoot . "/ooxai_" . $projectName . "_filesystem.txt";
    if (file_put_contents($filename, $tree) === false) {
        fwrite(STDERR, "Error: No se pudo escribir el archivo de salida: $filename\n");
        exit(1);
    }
    echo "Archivo generado exitosamente: $filename\n";
}

// --- Flujo Principal ---

try {
    $inputDir = getInputDirectory();

    if (!is_dir($inputDir)) {
        fwrite(STDERR, "Error: La ruta '$inputDir' no es un directorio válido.\n");
        exit(1);
    }

    if (!is_readable($inputDir)) {
        fwrite(STDERR, "Error: No se puede leer el directorio '$inputDir'. Revisa los permisos.\n");
        exit(1);
    }

    $projectName = basename($inputDir);
    echo "Analizando directorio: $inputDir (Nombre del proyecto: $projectName)\n";

    $configs = readIgnoreFiles($inputDir);
    $tree = buildTreeStructure($inputDir, $configs['gitignore'], $configs['avoid'], $configs['onlyfiles']);
    writeOutputFile($tree, $inputDir, $projectName);

} catch (Exception $e) {
    fwrite(STDERR, "Error inesperado: " . $e->getMessage() . "\n");
    exit(1);
}