<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGovernmentSsoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isOwner() && $this->user()->canAccessTenant((string) tenant()->id);
    }

    public function rules(): array
    {
        $path = ['required', 'string', 'max:200', 'regex:~\A/(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+\z~'];

        return [
            'web_enabled' => ['sometimes', 'boolean'],
            'customer_enabled' => ['sometimes', 'boolean'],
            'pkce' => ['sometimes', 'boolean'],
            'token_format' => ['required', 'in:form,multipart'],
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:2048'],
            'label' => ['required', 'string', 'max:100'],
            'authorize_path' => $path,
            'token_path' => $path,
            'userinfo_path' => $path,
            'logout_path' => $path,
        ];
    }

    public function messages(): array
    {
        return ['regex' => 'Enter a USW endpoint path beginning with a single slash, such as /oauth2/token.'];
    }
}
