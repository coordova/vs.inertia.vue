<?php

namespace App\Http\Requests;
use Illuminate\Support\Str;
use Illuminate\Foundation\Http\FormRequest;

class StoreSurveyRequest extends FormRequest
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
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255|unique:surveys,title',
            'slug' => 'required|string|max:255|unique:surveys,slug',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:255',
            'type' => 'required|integer|in:0,1', // 0=pública, 1=privada
            'status' => 'required|boolean',
            'reverse' => ['required', 'boolean'],
            'date_start' => 'required|date|before_or_equal:date_end',
            'date_end' => 'required|date|after_or_equal:date_start',
            'selection_strategy' => 'required|string|max:255',
            'max_votes_per_user' => 'nullable|integer|min:0',
            // 'allow_ties' => 'required|boolean',
            // 'tie_weight' => 'required|numeric|min:0|max:1',
            'is_featured' => 'required|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'counter' => 'nullable|integer|min:0',
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
            'slug' => $this->slug ?? Str::slug($this->title),
        ]);
    }
}
