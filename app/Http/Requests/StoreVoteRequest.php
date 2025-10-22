<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // La autorización ya se maneja en el middleware del controlador/ruta
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'combinatoric_id' => [
                'required',
                'integer',
                'exists:combinatorics,id',
            ],
            // Reglas básicas, sin lógica compleja de "required_without"
            'winner_id' => [
                'nullable', // Puede ser null
                'integer',
                'exists:characters,id', // Solo se valida si no es null
            ],
            'loser_id' => [
                'nullable', // Puede ser null
                'integer',
                'exists:characters,id', // Solo se valida si no es null
            ],
            'tie' => [
                'nullable', // Puede ser null
                'boolean',
            ],
        ];
    }

    /**
     * Configure the validator instance.
     * Permite personalizar mensajes o reglas condicionales complejas.
     */
    /* public function withValidator($validator)
    {
        $validator->sometimes('winner_id', 'different:loser_id', function ($input) {
            return !empty($input->winner_id) && !empty($input->loser_id);
        });

        $validator->sometimes('loser_id', 'different:winner_id', function ($input) {
            return !empty($input->winner_id) && !empty($input->loser_id);
        });
    } */
}