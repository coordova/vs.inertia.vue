<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface; // Importar la interfaz de imagen para claridad

/**
 * Servicio para el procesamiento de imágenes.
 * Proporciona métodos para escalar, recortar, aplicar efectos y guardar imágenes.
 * Utiliza Intervention Image para las operaciones de procesamiento.
 * 
 * NOTA: Este servicio utiliza la técnica de 'contain' para escalar imágenes,
 * ajustándolas dentro de un canvas del tamaño especificado, manteniendo la proporción.
 * El espacio sobrante se rellena con un color de fondo o es transparente según el formato.
 * 
 * Ejemplos de uso:
 * 
 * // 1. Generar solo la imagen principal en JPEG
 * $result = $imageService->makeMainAndThumbnailImages(
 *     $uploadedFile,
 *     'mi_imagen_base',
 *     600, 600, // Tamaño canvas principal
 *     'jpeg',   // Formato
 *     90,       // Calidad
 *     '#ffffff', // Color de fondo para JPEG
 *     'custom_dir' // Directorio personalizado
 * );
 * // Resultado: ['main' => 'custom_dir/mi_imagen_base.jpg', 'thumb' => null]
 * 
 * // 2. Generar imagen principal y thumbnail en PNG con fondo transparente
 * $result = $imageService->makeMainAndThumbnailImages(
 *     $uploadedFile,
 *     'otra_img',
 *     800, 600, // Tamaño canvas principal
 *     'png',    // Formato principal
 *     8,        // Calidad PNG
 *     'transparent', // Color de fondo (se ignora para PNG)
 *     'imagenes',    // Directorio principal
 *     200, 150,      // Tamaño canvas thumbnail
 *     'png',         // Formato thumbnail
 *     6,             // Calidad thumbnail
 *     'transparent'  // Color de fondo thumbnail (se ignora para PNG)
 *     'imagenes/thumbs' // Directorio thumbnail
 * );
 * // Resultado: ['main' => 'imagenes/otra_img.png', 'thumb' => 'imagenes/thumbs/otra_img.png']
 * 
 * // 3. Generar solo thumbnail en JPEG con rutas por defecto
 * $result = $imageService->makeMainAndThumbnailImages(
 *     $uploadedFile,
 *     'solo_thumb',
 *     600, 600, // Tamaño canvas principal (se ignora porque mainDirectory es null)
 *     'jpeg',   // Formato principal (se ignora porque mainDirectory es null)
 *     90,       // Calidad principal (se ignora porque mainDirectory es null)
 *     '#ffffff', // Color de fondo principal (se ignora porque mainDirectory es null)
 *     null,      // No guardar imagen principal
 *     180, 180,  // Tamaño canvas thumbnail
 *     'jpeg',    // Formato thumbnail
 *     85,        // Calidad thumbnail
 *     '#000000', // Color de fondo thumbnail
 *     'characters/thumbs' // Directorio thumbnail (ruta por defecto)
 * );
 * // Resultado: ['main' => null, 'thumb' => 'characters/thumbs/solo_thumb.jpg']
 * 
 * // 4. Generar imagen principal en PNG con fondo transparente, sin thumbnail
 * $result = $imageService->makeMainAndThumbnailImages(
 *     $uploadedFile,
 *     'img_sin_fondo',
 *     400, 400, // Tamaño canvas principal
 *     'png',    // Formato principal
 *     9,        // Calidad PNG
 *     'transparent' // Color de fondo (se ignora para PNG, pero se puede usar '#00000000')
 *     'characters'   // Directorio principal (ruta por defecto)
 *     // thumbWidth y thumbHeight son null, no se genera thumbnail
 * );
 * // Resultado: ['main' => 'characters/img_sin_fondo.png', 'thumb' => null]
 */
class ImageService
{
    protected ImageManager $imageManager;

    public function __construct()
    {
        // Inicializa el ImageManager con el driver GD
        $this->imageManager = new ImageManager(new Driver);
    }

    /**
     * Crea una imagen principal y opcionalmente una miniatura (thumbnail).
     * Ambas imágenes se escalan para ajustarse dentro de sus respectivos canvas,
     * manteniendo la proporción original (contain).
     * Se puede especificar el formato de salida (jpeg, png, webp) y la calidad.
     * 
     * IMPORTANTE: Los directorios por defecto son:
     * - Imagen Principal: 'characters/'
     * - Thumbnail: 'characters/thumbs/'
     *
     * @param UploadedFile $file Imagen original subida.
     * @param string $filenameBase Nombre base para los archivos generados (sin extensión ni directorio).
     * @param int $mainWidth Ancho del canvas para la imagen principal.
     * @param int $mainHeight Alto del canvas para la imagen principal.
     * @param string $mainFormat Formato de salida para la imagen principal ('jpeg', 'png', 'webp', etc.). (default: 'jpeg')
     * @param int $mainQuality Calidad de compresión para la imagen principal (0-100 para jpeg/webp, 0-9 para png). (default: 90)
     * @param string $mainBgColor Color de fondo para la imagen principal si el formato no admite transparencia (ej: '#ffffff'). (default: '#ffffff')
     * @param string|null $mainDirectory Directorio para guardar la imagen principal (dentro de 'public'). Si es null, no se guarda. (default: 'characters')
     * @param int|null $thumbWidth Ancho del canvas para la miniatura. Si es null, no se genera thumbnail. (default: null)
     * @param int|null $thumbHeight Alto del canvas para la miniatura. Si es null, no se genera thumbnail. (default: null)
     * @param string|null $thumbFormat Formato de salida para la miniatura ('jpeg', 'png', 'webp', etc.). Si es null, usa el mismo formato que la imagen principal. (default: null)
     * @param int|null $thumbQuality Calidad de compresión para la miniatura (0-100 para jpeg/webp, 0-9 para png). Si es null, usa la misma calidad que la imagen principal. (default: null)
     * @param string|null $thumbBgColor Color de fondo para la miniatura si el formato no admite transparencia (ej: '#ffffff'). Si es null, usa el mismo color que la imagen principal. (default: null)
     * @param string|null $thumbDirectory Directorio para guardar la miniatura (dentro de 'public'). Si es null, no se guarda. (default: 'characters/thumbs')
     * @return array ['main' => string|null, 'thumb' => string|null] Nombres de archivo relativos guardados o null si no se generó/guardó.
     */
    public function makeMainAndThumbnailImages(
        UploadedFile $file,
        string $filenameBase,
        int $mainWidth,
        int $mainHeight,
        string $mainFormat = 'jpeg',
        int $mainQuality = 90,
        string $mainBgColor = '#ffffff',
        ?string $mainDirectory = 'characters', // <-- Corregido: Valor por defecto explícito y cambiado a 'characters'
        ?int $thumbWidth = null,
        ?int $thumbHeight = null,
        ?string $thumbFormat = null, // <-- Corregido: Ahora explícitamente ?string
        ?int $thumbQuality = null,   // <-- Corregido: Ahora explícitamente ?int
        ?string $thumbBgColor = null, // <-- Corregido: Ahora explícitamente ?string
        ?string $thumbDirectory = 'characters/thumbs' // <-- Corregido: Valor por defecto explícito y cambiado a 'characters/thumbs'
    ): array {
        // Asegurar que thumbFormat, thumbQuality y thumbBgColor tengan un valor si no fueron proporcionados
        $thumbFormat = $thumbFormat ?? $mainFormat;
        $thumbQuality = $thumbQuality ?? $mainQuality;
        $thumbBgColor = $thumbBgColor ?? $mainBgColor;

        // Leer la imagen original
        $originalImage = $this->imageManager->read($file->getRealPath());

        // --- Procesar Imagen Principal ---
        $mainFilename = null;
        if ($mainDirectory !== null) { // <-- Condición corregida
            $mainFilename = $this->makeImageWithContain(
                clone $originalImage, // Clonar para no afectar la original
                $filenameBase,
                $mainWidth,
                $mainHeight,
                $mainFormat,
                $mainQuality,
                $mainBgColor,
                $mainDirectory
            );
        }

        // --- Procesar Miniatura ---
        $thumbFilename = null;
        if ($thumbWidth !== null && $thumbHeight !== null && $thumbDirectory !== null) { // <-- Condición corregida
            $thumbFilename = $this->makeImageWithContain(
                clone $originalImage, // Clonar de nuevo
                $filenameBase,
                $thumbWidth,
                $thumbHeight,
                $thumbFormat,
                $thumbQuality,
                $thumbBgColor,
                $thumbDirectory
            );
        }

        // Devolver los nombres de archivo relativos generados o null
        return [
            'main' => $mainFilename,
            'thumb' => $thumbFilename,
        ];
    }

    /**
     * Crea una imagen escalada para ajustarse dentro de un canvas específico,
     * manteniendo la proporción original (contain).
     * El espacio sobrante se rellena con un color de fondo o es transparente si el formato lo permite.
     * Guarda la imagen resultante en el disco 'public'.
     *
     * @param ImageInterface $image Imagen original (Intervention Image object) a procesar.
     * @param string $filenameBase Nombre base para el archivo generado (sin extensión ni directorio).
     * @param int $canvasWidth Ancho del canvas final.
     * @param int $canvasHeight Alto del canvas final.
     * @param string $format Formato de salida ('jpeg', 'png', 'webp', etc.). (default: 'jpeg')
     * @param int $quality Calidad de compresión (0-100 para jpeg/webp, 0-9 para png). (default: 90)
     * @param string $bgColor Color de fondo si el formato no admite transparencia (ej: '#ffffff'). (default: '#ffffff')
     * @param string $directory Directorio donde guardar la imagen (dentro de 'public').
     * @return string Nombre del archivo (sin directorio) guardado (ej: 'image.jpg').
     */
    /**
     * Crea una imagen escalada para ajustarse dentro de un canvas específico,
     * manteniendo la proporción original (tipo "contain").
     * El espacio sobrante se rellena con un color de fondo o es transparente
     * si el formato lo permite.
     *
     * NO recorta la imagen: siempre se ve completa.
     */
    private function makeImageWithContain(
        ImageInterface $image,
        string $filenameBase,
        int $canvasWidth,
        int $canvasHeight,
        string $format = 'jpeg',
        int $quality = 90,
        string $bgColor = '#ffffff',
        string $directory
    ): string {
        // Normalizar directorio
        $directory = rtrim($directory, '/') . '/';

        // Detectar si el formato soporta transparencia
        $supportsTransparency = in_array(strtolower($format), ['png', 'webp', 'gif'], true);

        // Determinar color de fondo real:
        // - Si el formato soporta transparencia y se pidió 'transparent', usamos literal 'transparent'.
        // - En otros casos, usamos el color pasado (ej: '#ffffff').
        $finalBgColor = $bgColor;
        if ($supportsTransparency && strtolower($bgColor) === 'transparent') {
            $finalBgColor = 'transparent';
        }

        // Aquí es donde se corrige el comportamiento:
        // En lugar de cover (que recorta), usamos pad,
        // que escala la imagen para que quepa (contain) y rellena con fondo.
        //
        // Firma v3:
        // public Image::pad(int $width, int $height, $background = 'ffffff', string $position = 'center'): ImageInterface
        // [web:73]
        $imageToProcess = $image->pad(
            $canvasWidth,
            $canvasHeight,
            $finalBgColor, // color o 'transparent'
            'center'       // centramos la imagen en el canvas
        );

        // Construir nombre de archivo con extensión correcta según formato
        $filenameWithExt = $filenameBase . '.' . $this->getExtensionFromFormat($format);
        $fullPathWithinPublic = $directory . $filenameWithExt;

        // Guardar usando el helper centralizado
        $this->saveImageByExtension($imageToProcess, $fullPathWithinPublic, $format, $quality);

        // Devolvemos solo el nombre de archivo (sin directorio)
        return $filenameWithExt;
    }


    private function makeImageWithContain___(
        ImageInterface $image,
        string $filenameBase,
        int $canvasWidth,
        int $canvasHeight,
        string $format = 'jpeg',
        int $quality = 90,
        string $bgColor = '#ffffff',
        string $directory
    ): string {
        // Asegurar que el directorio termine con una barra
        $directory = rtrim($directory, '/') . '/';

        // --- CORRECCIÓN: Usar el método contain() de Intervention Image ---
        // Este método escala la imagen para que quepa completamente dentro del canvas,
        // manteniendo la proporción original.
        // Luego, rellena el espacio restante con el color de fondo especificado.
        // Si el formato es PNG o WEBP, el color de fondo puede ser 'transparent'.

        // Determinar el color de fondo final
        $finalBgColor = $bgColor;
        $supportsTransparency = in_array(strtolower($format), ['png', 'webp', 'gif']);
        if ($supportsTransparency && strtolower($bgColor) === 'transparent') {
            // Si el formato lo permite y se solicita transparencia, usamos 'transparent'
            $finalBgColor = 'transparent';
        }

        // Aplicar el método contain directamente a la imagen
        $image->cover($canvasWidth, $canvasHeight, $finalBgColor); // <-- CORREGIDO: Usar cover con color de fondo
        
        // Opción alternativa (y quizás más fiel a 'contain'): Escalar y luego crear canvas
        // $image->resize($canvasWidth, $canvasHeight, function ($constraint) {
        //     $constraint->aspectRatio();
        //     $constraint->upsize();
        // });
        // $finalCanvas = $this->imageManager->create($canvasWidth, $canvasHeight);
        // if ($supportsTransparency && strtolower($bgColor) === 'transparent') {
        //     $finalCanvas->fill('transparent');
        // } else {
        //     $finalCanvas->fill($finalBgColor);
        // }
        // $finalCanvas->place($image, 'center', 'center'); // <-- Usar place con posiciones relativas o calcular offset
        // $imageToSave = $finalCanvas;
        // ---
        // La opción con $image->cover(...) es más directa y similar al código anterior.
        $imageToProcess = $image; // La imagen ya procesada por 'cover'
        // --- FIN CORRECCIÓN ---

        // --- CONSTRUIR NOMBRE Y GUARDAR ---
        // Construir el nombre de archivo final con extensión
        $filenameWithExt = $filenameBase . '.' . $this->getExtensionFromFormat($format);
        $fullPathWithinPublic = $directory . $filenameWithExt;

        // Guardar la imagen final en el disco 'public' usando el helper privado
        $this->saveImageByExtension($imageToProcess, $fullPathWithinPublic, $format, $quality);

        // --- DEVOLVER SOLO EL NOMBRE DEL ARCHIVO ---
        // Devolver SOLO el nombre del archivo (sin directorio)
        return $filenameWithExt; // <-- CORREGIDO: Devuelve solo el nombre
        // --- FIN DEVOLVER ---
    }



    /**
     * Helper para guardar una imagen en el disco 'public' según su extensión y calidad.
     * Maneja diferentes formatos de salida (JPEG, PNG, WEBP, GIF).
     *
     * @param ImageInterface $image La imagen a guardar.
     * @param string $savePath Ruta relativa dentro del disco 'public'.
     * @param string $format Formato del archivo ('jpeg', 'png', 'webp', 'gif', etc.).
     * @param int $quality Calidad de compresión (0-100 para JPEG/WEBP, 0-9 para PNG).
     * @return void
     */
    private function saveImageByExtension(ImageInterface $image, string $savePath, string $format, int $quality): void
    {
        $fullPath = Storage::disk('public')->path($savePath);

        switch (strtolower($format)) {
            case 'jpg':
            case 'jpeg':
                $image->toJpeg($quality)->save($fullPath);
                break;
            case 'png':
                $image->toPng()->save($fullPath);
                // $image->encode(new \Intervention\Image\Encoders\PngEncoder(compression: $quality))->save($fullPath);
                break;
            case 'webp':
                $image->toWebp($quality)->save($fullPath);
                break;
            case 'gif':
                $image->toGif()->save($fullPath);
                break;
            default:
                // Opcional: Lanzar una excepción si la extensión no es soportada
                // throw new \InvalidArgumentException("Unsupported image format: {$format}");
                // Por ahora, asumimos jpg como fallback
                $image->toJpeg($quality)->save($fullPath);
                break;
        }
    }

    /**
     * Helper para obtener la extensión de archivo estándar a partir del nombre del formato.
     * Esto es útil para construir el nombre del archivo final.
     *
     * @param string $format Nombre del formato (e.g., 'jpeg', 'png', 'webp').
     * @return string Extensión de archivo (e.g., 'jpg', 'png', 'webp').
     */
    private function getExtensionFromFormat(string $format): string
    {
        // Mapear formatos comunes al nombre de extensión real
        $formatMap = [
            'jpeg' => 'jpg',
            'jpg' => 'jpg',
            'png' => 'png',
            'webp' => 'webp',
            'gif' => 'gif',
            // Añadir más si es necesario
        ];

        return $formatMap[strtolower($format)] ?? 'jpg'; // Fallback a 'jpg' si el formato no está mapeado
    }
}