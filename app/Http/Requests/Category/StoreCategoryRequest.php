<?php

namespace App\Http\Requests\Category;

use App\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Collapse runs of whitespace inside the name. Leading and trailing
     * whitespace is already trimmed by the global TrimStrings middleware.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => Str::squish($this->input('name'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The status is deliberately absent: a new category is always active,
     * and it is moved to another status through its own endpoint.
     *
     * Uniqueness is scoped to the owner and the type, matching the
     * UNIQUE(user_id, name, type) index. The utf8mb4_unicode_ci collation
     * makes the comparison ignore case and diacritics, so "luong" collides
     * with an existing "Lương".
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories')
                    ->where('user_id', $this->user()->id)
                    ->where('type', $this->input('type')),
            ],
            'type' => ['required', Rule::enum(TransactionType::class)],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'You already have a category with this name and type.',
        ];
    }
}
