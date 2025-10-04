<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreCharacterRequest extends FormRequest
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
        return [
            'fullname' => 'required|string|max:255|unique:characters,fullname',
            'nickname' => 'nullable|string|max:255',
            'slug' => 'required|string|max:255|unique:characters,slug',
            'bio' => 'nullable|string',
            'dob' => 'nullable|date',
            'gender' => 'nullable|integer|in:0,1,2,3', // 0=otro, 1=masculino, 2=femenino, 3=no-binario
            'nationality' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'picture' => ['required', 'image', 'max:2048', 'mimes:jpeg,jpg,png,gif,svg'],
            'status' => 'required|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * Este método es perfecto para preparar datos antes de que se validen.
     * Genera automáticamente el slug si no se proporciona.
     */
    protected function prepareForValidation(): void
    {
        // Aquí podemos añadir el slug a los datos de la petición.
        // Lo haremos en el controlador para más claridad en este caso.
        $this->merge([
            'slug' => $this->slug ?? Str::slug($this->fullname),
        ]);
    }
}
