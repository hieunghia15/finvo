<?php

namespace App\Http\Requests\Auth;

use App\Rules\ValidEmail;
use App\Rules\ValidPassword;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Lowercase the email so uniqueness is case-insensitive. Whitespace is
     * already trimmed by the global TrimStrings middleware.
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255', 'unique:users,email', new ValidEmail],
            'password' => ['required', 'string', 'confirmed', new ValidPassword],
        ];
    }

    /**
     * Get the attributes used to create the new user.
     *
     * @return array{name: string, email: string, password: string}
     */
    public function userData(): array
    {
        return $this->only('name', 'email', 'password');
    }
}
