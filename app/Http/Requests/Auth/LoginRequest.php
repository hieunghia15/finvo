<?php

namespace App\Http\Requests\Auth;

use App\Rules\ValidEmail;
use App\Rules\ValidPassword;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Lowercase the email so it matches how registration stores it,
     * regardless of the database collation.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower($this->input('email'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', new ValidEmail],
            'password' => ['required', 'string', new ValidPassword],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get the credentials to attempt authentication with.
     *
     * @return array{email: string, password: string}
     */
    public function credentials(): array
    {
        return $this->only('email', 'password');
    }
}
