<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCharacterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Ej: return auth()->user()->type === 1; // Solo admins
        return true; // Ajustar según lógica de autorización
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $characterId = $this->route('character'); // Obtiene el ID del parámetro de la ruta

        return [
            'fullname' => ['required', 'string', 'max:255', Rule::unique('characters', 'fullname')->ignore($characterId)],
            'nickname' => 'nullable|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('characters', 'slug')->ignore($characterId)],
            'bio' => 'nullable|string',
            'dob' => 'nullable|date',
            'gender' => 'nullable|integer|in:0,1,2,3',
            'nationality' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'picture' => 'required|string|max:255',
            'status' => 'required|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ];
    }
}
