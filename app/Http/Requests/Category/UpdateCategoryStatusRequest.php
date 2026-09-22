<?php

namespace App\Http\Requests\Category;

use App\Enums\EntityStatus;
use App\Http\Requests\Concerns\ResolvesCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Status changes are deliberately separate from editing a category's details.
 *
 * Archived categories cannot be edited but must stay restorable, and the two
 * rules only coexist without a special case when they are different endpoints.
 * There is no guard here: every transition is allowed in both directions, and
 * transactions on the category do not restrict it.
 */
class UpdateCategoryStatusRequest extends FormRequest
{
    use ResolvesCategory;

    /**
     * Determine if the user is authorized to make this request.
     *
     * Ownership is enforced by resolving the category through the current
     * user, which answers 404 instead of 403.
     */
    public function authorize(): bool
    {
        $this->category();

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(EntityStatus::class)],
        ];
    }

    /**
     * The validated status.
     */
    public function status(): EntityStatus
    {
        return $this->enum('status', EntityStatus::class);
    }
}
