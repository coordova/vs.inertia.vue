<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSurveyRequest extends FormRequest
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
        $surveyId = $this->route('survey');

        return [
            'category_id' => 'required|exists:categories,id',
            'title' => ['required', 'string', 'max:255', Rule::unique('surveys', 'title')->ignore($surveyId)],
            'slug' => ['required', 'string', 'max:255', Rule::unique('surveys', 'slug')->ignore($surveyId)],
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:255',
            'type' => 'required|integer|in:0,1',
            'status' => 'required|boolean',
            'reverse' => ['required', 'boolean'],
            'date_start' => 'required|date|before_or_equal:date_end',
            'date_end' => 'required|date|after_or_equal:date_start',
            'selection_strategy' => 'required|string|max:255',
            'max_votes_per_user' => 'nullable|integer|min:0',
            'allow_ties' => 'required|boolean',
            'tie_weight' => 'required|numeric|min:0|max:1',
            'is_featured' => 'required|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'counter' => 'nullable|integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ];
    }
}
