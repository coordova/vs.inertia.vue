<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CharacterIndexResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        $picture = $this->picture; // Ej: 'characters/juan-12345678.jpg'
        $thumbUrl = null;
        

        /* if ($picture) {
            // Extraer path relativo y extension
            $info = pathinfo($picture);
            // $info['dirname'] = 'characters', $info['filename'] = 'juan-12345678', $info['extension'] = 'jpg'
    
            // Construir ruta del thumb: 'characters/thumbs/juan-12345678_thumb.jpg'
            $thumbRelPath = $info['dirname'].'/thumbs/'.$info['filename'].'_thumb.'.$info['extension'];
            $thumbUrl = Storage::url($thumbRelPath);
        } */

        return [
            'id' => $this->id,
            'fullname' => $this->fullname,
            'nickname' => $this->nickname,
            'gender' => $this->gender,
            // 'thumbnail_url' => $thumbUrl,
            'thumbnail_url' => $this->picture ? Storage::url('characters/thumbs/'.$this->picture) : null,
            'status' => $this->status,
            'created_at_formatted' => $this->created_at->translatedFormat('Y-m-d H:i:s'),
        ];
    }
}
