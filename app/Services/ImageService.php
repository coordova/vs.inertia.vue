<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*===================================
 * ImageService - usage examples
 *===================================*/
/* public function store(Request $request, ImageService $imageService)
{
    $img = $request->file('picture');
    $resizedPath = 'characters/' . uniqid() . '.jpg';

    // Redimensionar a 400px de ancho, manteniendo la proporción
    $imageService->resizeAndSave($img, 400, null, $resizedPath);

    // Para insertar watermark (p. ej. 'public/watermark.png')
    $finalPath = 'characters/' . uniqid('wm_') . '.jpg';
    $imageService->insertImageAndSave($img, storage_path('app/public/watermark.png'), $finalPath);

    // ...guardar $resizedPath o $finalPath en la BD según lo procesado
}
*/

/* public function store(Request $request, ImageService $imageService)
{
    $img = $request->file('picture');
    $filenameBase = uniqid(Str::slug($request->input('fullname')));

    $images = $imageService->makeCanvasWithThumb(
        $img,
        $filenameBase, // nombre base
        600,           // canvas width
        600,           // canvas height
        180,           // thumb width
        180,           // thumb height
        'characters/',     // destino main
        'characters/thumbs/'// destino thumb
    );

    // Guardar rutas en la BD, ejemplo:
    // $character->picture = $images['main'];
    // $character->picture_thumb = $images['thumb'];
} */


class ImageService
{
    protected ImageManager $imageManager;

    public function __construct()
    {
        // Puedes usar GD, Imagick o Libvips según tus necesidades
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * Redimensiona y guarda una imagen en almacenamiento público.
     *
     * @param UploadedFile $file
     * @param int|null $width
     * @param int|null $height
     * @param string $savePath
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
     * @param UploadedFile $file
     * @param string $insertPath Ruta absoluta o relativa del watermark (puedes usar Storage::path)
     * @param string $savePath  Ruta donde guardar la imagen final en 'public'
     * @param array $placement Opciones de posición: [x, y, position]. Ejemplo: ['bottom-right']
     * @return string
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
     * @param UploadedFile $file Imagen original subida
     * @param string $filenameBase Nombre base sin extension ni directorio
     * @param int $canvasWidth Width del canvas final (default: 600)
     * @param int $canvasHeight Height del canvas final (default: 600)
     * @param int $thumbWidth Width thumbnail (default: 180)
     * @param int $thumbHeight Height thumbnail (default: 180)
     * @param string $mainDir Directorio destino canvas
     * @param string $thumbDir Directorio destino thumbnail
     * @param string $bgColor Color fondo canvas (hex o nombre, default: blanco)
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
        string $thumbDir = 'characters_thumbs',
        string $bgColor = '#fff',
        string $extension = 'jpg',
        int $quality = 90
    ): array {
        $mainDir = rtrim($mainDir, '/') . '/';
        $thumbDir = rtrim($thumbDir, '/') . '/';
        $original = $this->imageManager->read($file->getRealPath());

        // Main image (canvas)
        $mainCanvas = $original->contain($canvasWidth, $canvasHeight, $bgColor);
        $mainPath = $mainDir . $filenameBase . '.' . $extension;
        $mainCanvas->toJpeg($quality)->save(Storage::disk('public')->path($mainPath));

        // Thumbnail
        $thumbCanvas = $original->contain($thumbWidth, $thumbHeight, $bgColor);
        // $thumbPath = $thumbDir . $filenameBase . '_thumb.' . $extension;
        $thumbPath = $thumbDir . $filenameBase . '.' . $extension;
        $thumbCanvas->toJpeg($quality)->save(Storage::disk('public')->path($thumbPath));

        return [
            'main' => $mainPath,
            'thumb' => $thumbPath,
        ];
    }
    
}
