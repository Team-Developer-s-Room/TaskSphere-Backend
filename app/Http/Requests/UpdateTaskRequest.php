<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string', 'max:16777215'],
            'status' => ['nullable', 'string', 'in:pending,completed'],
            'start_time' => ['nullable', 'date', 'before_or_equal:end_time', 'required_with:end_time'],
            'end_time' => ['nullable', 'date', 'after_or_equal:start_time'],
            'start_date' => ['nullable', 'date', 'before_or_equal:end_date', 'required_with:end_date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
