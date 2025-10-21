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
                // Validar que la combinación exista y pertenezca a la encuesta (se puede reforzar en el controlador)
                'exists:combinatorics,id',
            ],
            'winner_id' => [
                'required_without:tie', // Requerido si 'tie' no está presente
                'nullable', // Permite null si 'tie' está presente
                'integer',
                'exists:characters,id', // Solo se valida si no es null
                // 'different:loser_id', // Opcional: Asegurar que sean diferentes
            ],
            'loser_id' => [
                'required_without:tie', // Requerido si 'tie' no está presente
                'nullable', // Permite null si 'tie' está presente
                'integer',
                'exists:characters,id', // Solo se valida si no es null
                // 'different:winner_id', // Opcional: Asegurar que sean diferentes
            ],
            'tie' => [
                'required_without_all:winner_id,loser_id', // Requerido si ni winner_id ni loser_id están presentes
                'nullable', // Permite null si winner/loser están presentes
                'boolean',
                // 'prohibited_if:winner_id,*', // Prohibir si winner_id está presente
                // 'prohibited_if:loser_id,*', // Prohibir si loser_id está presente
            ],
        ];
    }

    /**
     * Configure the validator instance.
     * Permite personalizar mensajes o reglas condicionales complejas.
     */
    public function withValidator($validator)
    {
        $validator->sometimes('winner_id', 'different:loser_id', function ($input) {
            return !empty($input->winner_id) && !empty($input->loser_id);
        });

        $validator->sometimes('loser_id', 'different:winner_id', function ($input) {
            return !empty($input->winner_id) && !empty($input->loser_id);
        });
    }
}