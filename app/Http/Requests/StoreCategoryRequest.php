<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreCategoryRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:categories,name',
            'slug' => 'required|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:255', // Puede ser una URL o path
            'color' => 'nullable|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|boolean',
            'is_featured' => 'required|boolean',
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
            'slug' => $this->slug ?? Str::slug($this->name),
        ]);
    }
}
