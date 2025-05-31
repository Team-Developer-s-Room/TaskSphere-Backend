<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'admin_id' => ['required', 'exists:users,nano_id'],
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:5120'],
            'description' => ['nullable', 'max:16777215'],
            // 'status' => ['string', 'in:upcoming,in-progress,completed'],
            'start_date' => ['required', 'date', 'before_or_equal:end_date', 'required_with:end_date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }
}
