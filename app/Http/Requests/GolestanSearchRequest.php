<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GolestanSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() && (string) $this->user()->tenant_id === (string) tenant()?->id;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('student_number'))) {
            $this->merge(['student_number' => strtr(trim($this->input('student_number')), array_combine(
                preg_split('//u', '۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩', -1, PREG_SPLIT_NO_EMPTY),
                str_split('01234567890123456789'),
            ))]);
        }
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'student_number' => ['required', 'string', 'regex:/\A[0-9]{1,20}\z/'],
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:200'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'student_number.required' => 'Enter a student number.',
            'student_number.regex' => 'The student number must contain 1 to 20 digits.',
            'username.required' => 'Enter your Golestan web-service username.',
            'password.required' => 'Enter your Golestan web-service password.',
        ];
    }
}
