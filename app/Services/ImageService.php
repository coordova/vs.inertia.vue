<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageService
{
    protected ImageManager $imageManager;

    public function __construct()
    {
        // Puedes usar GD, Imagick o Libvips según tus necesidades
        $this->imageManager = new ImageManager(new Driver);
    }

    /**
     * Redimensiona y guarda una imagen en almacenamiento público.
     *
     * @return string Ruta relativa final de la imagen (dentro de /public)
     */
    public function resizeAndSave(
        UploadedFile $file,
        ?int $width,
        ?int $height,
        string $savePath
    ): string {
        // Lee imagen desde el archivo tmp
        $image = $this->imageManager->read($file->getRealPath());

        // Redimensiona según los parámetros enviados
        if ($width || $height) {
            $image->scale(width: $width, height: $height);
        }

        // Guarda la imagen procesada en el disco 'public'
        $image->toJpeg(90)->save(Storage::disk('public')->path($savePath));

        return $savePath;
    }

    /**
     * Inserta otra imagen (watermark/logo) en la imagen principal y guarda el resultado.
     *
     * @param  string  $insertPath  Ruta absoluta o relativa del watermark (puedes usar Storage::path)
     * @param  string  $savePath  Ruta donde guardar la imagen final en 'public'
     * @param  array  $placement  Opciones de posición: [x, y, position]. Ejemplo: ['bottom-right']
     */
    public function insertImageAndSave(
        UploadedFile $file,
        string $insertPath,
        string $savePath,
        array $placement = ['bottom-right']
    ): string {
        $image = $this->imageManager->read($file->getRealPath());

        $image->place($insertPath, ...$placement);

        $image->toJpeg(90)->save(Storage::disk('public')->path($savePath));

        return $savePath;
    }

    /**
     * Crea una imagen base (canvas) y una thumbnail, con aspect ratio, y guarda ambas.
     *
     * @param  UploadedFile  $file  Imagen original subida
     * @param  string  $filenameBase  Nombre base sin extension ni directorio
     * @param  int  $canvasWidth  Width del canvas final (default: 600)
     * @param  int  $canvasHeight  Height del canvas final (default: 600)
     * @param  int  $thumbWidth  Width thumbnail (default: 180)
     * @param  int  $thumbHeight  Height thumbnail (default: 180)
     * @param  string  $mainDir  Directorio destino canvas
     * @param  string  $thumbDir  Directorio destino thumbnail
     * @param  string  $bgColor  Color fondo canvas (hex o nombre, default: blanco)
     * @return array ['main' => <ruta>, 'thumb' => <ruta>]
     */
    public function makeCanvasWithThumb(
        UploadedFile $file,
        string $filenameBase,
        int $canvasWidth = 600,
        int $canvasHeight = 600,
        int $thumbWidth = 180,
        int $thumbHeight = 180,
        string $mainDir = 'characters',
        string $thumbDir = 'characters/thumbs',
        string $bgColor = '#fff',
        string $extension = 'jpg',
        int $quality = 90
    ): array {
        $mainDir = rtrim($mainDir, '/').'/';
        $thumbDir = rtrim($thumbDir, '/').'/';
        $original = $this->imageManager->read($file->getRealPath());

        // Main image (canvas)
        $mainCanvas = $original->contain($canvasWidth, $canvasHeight, $bgColor);
        $mainPath = $mainDir.$filenameBase.'.'.$extension;
        $mainCanvas->toJpeg($quality)->save(Storage::disk('public')->path($mainPath));

        // Thumbnail
        $thumbCanvas = $original->contain($thumbWidth, $thumbHeight, $bgColor);
        // $thumbPath = $thumbDir.$filenameBase.'_thumb.'.$extension;
        $thumbPath = $thumbDir.$filenameBase.'.'.$extension;
        $thumbCanvas->toJpeg($quality)->save(Storage::disk('public')->path($thumbPath));

        return [
            'main' => $filenameBase.'.'.$extension,
            // 'main' => $mainPath,
            // 'thumb' => $thumbPath,
        ];
    }

    /**
     * Crea una imagen base (canvas) y una thumbnail usando como fondo
     * una versión ampliada y desenfocada de la misma imagen original.
     *
     * Útil para evitar fondos sólidos (blanco/negro) cuando las proporciones
     * de la imagen no coinciden con el canvas, imitando el efecto de muchas apps
     * modernas (background blur del propio contenido).
     *
     * @param  UploadedFile  $file          Imagen original subida
     * @param  string        $filenameBase  Nombre base sin extensión
     * @param  int           $canvasWidth   Ancho del canvas final (ej: 600)
     * @param  int           $canvasHeight  Alto del canvas final (ej: 600)
     * @param  int           $thumbWidth    Ancho del thumbnail (ej: 180)
     * @param  int           $thumbHeight   Alto del thumbnail (ej: 180)
     * @param  string        $mainDir       Directorio destino canvas (sin / final)
     * @param  string        $thumbDir      Directorio destino thumbnail (sin / final)
     * @param  string        $extension     Extensión de salida (jpg, webp, etc.)
     * @param  int           $quality       Calidad de salida (para JPEG/WebP)
     * @param  int           $blurAmount    Intensidad del blur para el fondo (0–100)
     * @return array{main: string, thumb: string}
     */
    public function makeCanvasWithThumbBlurredBackgroundBoth(
        UploadedFile $file,
        string $filenameBase,
        int $canvasWidth = 600,
        int $canvasHeight = 600,
        int $thumbWidth = 180,
        int $thumbHeight = 180,
        string $mainDir = 'characters',
        string $thumbDir = 'characters/thumbs',
        string $extension = 'jpg',
        int $quality = 90,
        int $blurAmount = 25
    ): array {
        // Normalizamos rutas para asegurar que terminan con '/'
        $mainDir  = rtrim($mainDir, '/').'/';
        $thumbDir = rtrim($thumbDir, '/').'/';

        // Leemos la imagen original desde el archivo temporal
        $original = $this->imageManager->read($file->getRealPath());

        // ---------------------------------------------------------------------
        // 1) IMAGEN PRINCIPAL (canvas con fondo desenfocado + original centrada)
        // ---------------------------------------------------------------------

        // Clonamos la imagen original para usarla como base del fondo
        $background = clone $original;

        // Calculamos un factor de escala para que el fondo "cubra" todo el canvas,
        // similar a background-size: cover en CSS.
        $origWidth  = $background->width();
        $origHeight = $background->height();

        // Evitamos división por cero
        if ($origWidth === 0 || $origHeight === 0) {
            throw new \RuntimeException('Invalid image dimensions.');
        }

        // Factor para cubrir el canvas completo
        $scale = max(
            $canvasWidth / $origWidth,
            $canvasHeight / $origHeight
        );

        $bgWidth  = (int) ceil($origWidth * $scale);
        $bgHeight = (int) ceil($origHeight * $scale);

        // Redimensionamos el "background" para que sea más grande que el canvas
        // y lo recortamos centrado a las dimensiones exactas del canvas.
        $background
            ->scale($bgWidth, $bgHeight)                // escalamos
            ->cover($canvasWidth, $canvasHeight);       // recortamos centrado al canvas

        // Aplicamos un blur fuerte al fondo.
        // Con GD, valores muy altos pueden ser costosos; 20–40 suele ser razonable.
        $background->blur($blurAmount);

        // Ahora colocamos la imagen original "contenida" sobre el fondo desenfocado.
        // Usamos contain para mantener el aspect ratio y que quepa dentro del canvas.
        $foreground = clone $original;
        $foreground->contain($canvasWidth, $canvasHeight);

        // Calculamos coordenadas para centrar el foreground sobre el canvas
        $fgWidth  = $foreground->width();
        $fgHeight = $foreground->height();
        $fgX      = (int) floor(($canvasWidth  - $fgWidth) / 2);
        $fgY      = (int) floor(($canvasHeight - $fgHeight) / 2);

        // Insertamos el foreground centrado en el fondo desenfocado
        $background->place($foreground, 'top-left', $fgX, $fgY);

        // Guardamos la imagen principal en el disco 'public'
        $mainPath = $mainDir.$filenameBase.'.'.$extension;
        $background->toJpeg($quality)->save(Storage::disk('public')->path($mainPath));

        // ---------------------------------------------------------------------
        // 2) THUMBNAIL (mismo concepto, pero en dimensiones de thumbnail)
        // ---------------------------------------------------------------------

        // Repetimos el proceso para el thumbnail, partiendo de la original
        $thumbBackground = clone $original;

        $scaleThumb = max(
            $thumbWidth / $origWidth,
            $thumbHeight / $origHeight
        );

        $bgThumbWidth  = (int) ceil($origWidth * $scaleThumb);
        $bgThumbHeight = (int) ceil($origHeight * $scaleThumb);

        $thumbBackground
            ->scale($bgThumbWidth, $bgThumbHeight)
            ->cover($thumbWidth, $thumbHeight)
            ->blur($blurAmount);

        $thumbForeground = clone $original;
        $thumbForeground->contain($thumbWidth, $thumbHeight);

        $fgThumbWidth  = $thumbForeground->width();
        $fgThumbHeight = $thumbForeground->height();
        $fgThumbX      = (int) floor(($thumbWidth  - $fgThumbWidth) / 2);
        $fgThumbY      = (int) floor(($thumbHeight - $fgThumbHeight) / 2);

        $thumbBackground->place($thumbForeground, 'top-left', $fgThumbX, $fgThumbY);

        $thumbPath = $thumbDir.$filenameBase.'_thumb.'.$extension;
        $thumbBackground->toJpeg($quality)->save(Storage::disk('public')->path($thumbPath));

        // ---------------------------------------------------------------------
        // 3) Devolvemos las rutas relativas dentro del disco 'public'
        // ---------------------------------------------------------------------
        return [
            'main'  => $mainPath,
            'thumb' => $thumbPath,
        ];
    }

    /**
     * Crea una imagen base (canvas) usando como fondo una versión
     * ampliada y desenfocada de la misma imagen, y una thumbnail
     * clásica con fondo sólido.
     *
     * @return array{main: string, thumb: string}
     */
    public function makeCanvasWithThumbBlurredBackground(
        UploadedFile $file,
        string $filenameBase,
        int $canvasWidth = 600,
        int $canvasHeight = 600,
        int $thumbWidth = 180,
        int $thumbHeight = 180,
        string $mainDir = 'characters',
        string $thumbDir = 'characters/thumbs',
        string $bgColor = '#fff',
        string $extension = 'jpg',
        int $quality = 90,
        int $blurAmount = 25
    ): array {
        $mainDir  = rtrim($mainDir, '/').'/';
        $thumbDir = rtrim($thumbDir, '/').'/';

        $original = $this->imageManager->read($file->getRealPath());

        // ---------------------------------------------------------------------
        // 1) IMAGEN PRINCIPAL: fondo blur + foreground centrado
        // ---------------------------------------------------------------------

        $background = clone $original;

        $origWidth  = $background->width();
        $origHeight = $background->height();

        if ($origWidth === 0 || $origHeight === 0) {
            throw new \RuntimeException('Invalid image dimensions.');
        }

        // Factor para "cover"
        $scale = max(
            $canvasWidth / $origWidth,
            $canvasHeight / $origHeight
        );

        $bgWidth  = (int) ceil($origWidth * $scale);
        $bgHeight = (int) ceil($origHeight * $scale);

        $background
            ->scale($bgWidth, $bgHeight)          // agrandamos
            ->cover($canvasWidth, $canvasHeight)  // recortamos centrado al canvas
            ->blur($blurAmount);                  // aplicamos blur al fondo

        // Foreground contenido dentro del canvas
        $foreground = clone $original;
        $foreground->contain($canvasWidth, $canvasHeight);

        $fgWidth  = $foreground->width();
        $fgHeight = $foreground->height();
        $fgX      = (int) floor(($canvasWidth  - $fgWidth) / 2);
        $fgY      = (int) floor(($canvasHeight - $fgHeight) / 2);

        $background->place($foreground, 'top-left', $fgX, $fgY);

        $mainPath = $mainDir.$filenameBase.'.'.$extension;
        $background->toJpeg($quality)->save(Storage::disk('public')->path($mainPath));

        // ---------------------------------------------------------------------
        // 2) THUMBNAIL: versión simple tipo makeCanvasWithThumb
        // ---------------------------------------------------------------------

        // Para el thumb, el blur apenas aporta valor y sí añade coste,
        // así que usamos un canvas "normal" con fondo sólido.
        $thumbCanvas = (clone $original)->contain($thumbWidth, $thumbHeight, $bgColor);
        $thumbPath = $thumbDir.$filenameBase.'_thumb.'.$extension;
        $thumbCanvas->toJpeg($quality)->save(Storage::disk('public')->path($thumbPath));

        return [
            'main' => $filenameBase.'.'.$extension,
            // 'main'  => $mainPath,
            // 'thumb' => $thumbPath,
        ];
    }


    /**
     * Crea un canvas transparente PNG y su thumbnail.
     * Mantiene relación de aspecto y rellena el sobrante con transparencia.
     *
     * @param  string  $filenameBase  nombre sin extensión
     * @param  int  $canvasWidth  ancho del canvas final
     * @param  int  $canvasHeight  alto del canvas final
     * @param  int  $thumbWidth  ancho del thumbnail
     * @param  int  $thumbHeight  alto del thumbnail
     * @param  string  $mainDir  carpeta destino (sin / final)
     * @param  string  $thumbDir  carpeta destino thumb (sin / final)
     * @param  int  $quality  compresión PNG (0-9, 0 sin compresión)
     * @return array ['main' => ruta, 'thumb' => ruta]
     */
    public function makeCanvasWithThumbTransparent(
        UploadedFile $file,
        string $filenameBase,
        int $canvasWidth = 600,
        int $canvasHeight = 600,
        int $thumbWidth = 180,
        int $thumbHeight = 180,
        string $mainDir = 'characters',
        string $thumbDir = 'characters/thumbs',
        int $quality = 9
    ): array {
        $mainDir = rtrim($mainDir, '/').'/';
        $thumbDir = rtrim($thumbDir, '/').'/';

        $original = $this->imageManager->read($file->getRealPath());

        // Canvas principal transparente
        $mainCanvas = $original->contain($canvasWidth, $canvasHeight, background: 'transparent');
        $mainPath = $mainDir.$filenameBase.'.png';
        $mainCanvas->toPng($quality)->save(Storage::disk('public')->path($mainPath));

        // Thumbnail transparente
        $thumbCanvas = $original->contain($thumbWidth, $thumbHeight, background: 'transparent');
        $thumbPath = $thumbDir.$filenameBase.'_thumb.png';
        $thumbCanvas->toPng($quality)->save(Storage::disk('public')->path($thumbPath));

        return [
            'main' => $mainPath,
            'thumb' => $thumbPath,
        ];
    }
}
