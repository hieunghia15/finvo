<?php

namespace App\Http\Requests\Category;

use App\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCategoryRequest extends FormRequest
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
     * The list is neither paginated nor searchable: a user has a few dozen
     * categories, so these two filters are the whole query surface.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::enum(TransactionType::class)],
            'include_archived' => ['nullable', 'boolean'],
        ];
    }

    /**
     * The type filter, or null when the list is not narrowed to one type.
     */
    public function type(): ?TransactionType
    {
        return $this->enum('type', TransactionType::class);
    }

    /**
     * Whether archived categories join the list.
     *
     * When on, the list shows every status rather than only the archived
     * ones: the question this answers is "where did my category go?".
     */
    public function includeArchived(): bool
    {
        return $this->boolean('include_archived');
    }
}
