<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'amount'      => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'date'        => ['nullable', 'date'],
        ];
    }

    public function validatedWithDefaults(): array
    {
        $data = $this->validated();
        $data['date'] ??= now()->toDateString();

        return $data;
    }
}
