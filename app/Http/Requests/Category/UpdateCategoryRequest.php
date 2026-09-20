<?php

namespace App\Http\Requests\Category;

use App\Enums\TransactionType;
use App\Http\Requests\Concerns\ResolvesCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCategoryRequest extends FormRequest
{
    use ResolvesCategory;

    /**
     * Determine if the user is authorized to make this request.
     *
     * Ownership is enforced by resolving the category through the current
     * user in rules(), which answers 404 instead of 403.
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
     * The status is not editable here; it has its own endpoint, which is
     * what lets an archived category be restored even though its details
     * are locked.
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
                    ->where('type', $this->input('type'))
                    ->ignore($this->category()->id),
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

    /**
     * Guards that need the stored category, not just the payload.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $category = $this->category();

                // An archived category is read-only until it is restored.
                if (!$category->status->isEditable()) {
                    $validator->errors()->add(
                        'status',
                        'An archived category must be restored before it can be edited.',
                    );

                    return;
                }

                $type = $this->enum('type', TransactionType::class);

                // Null means the enum rule already rejected it; an unchanged
                // type stays legal however many transactions exist, so only a
                // real switch is worth a database round trip.
                if ($type === null || $type === $category->type) {
                    return;
                }

                if ($category->transactions()->exists()) {
                    $validator->errors()->add(
                        'type',
                        'This category already has transactions, so its type can no longer be changed.',
                    );
                }
            },
        ];
    }

    /**
     * The validated name, normalized by prepareForValidation().
     */
    public function name(): string
    {
        return $this->string('name')->toString();
    }

    /**
     * The validated type.
     */
    public function type(): TransactionType
    {
        return $this->enum('type', TransactionType::class);
    }
}
