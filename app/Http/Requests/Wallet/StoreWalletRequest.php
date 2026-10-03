<?php

namespace App\Http\Requests\Wallet;

use App\Enums\WalletType;
use App\Models\Currency;
use App\Rules\MoneyPrecision;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreWalletRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the name and the currency code.
     *
     * Leading and trailing whitespace is already trimmed by the global
     * TrimStrings middleware, so the name only needs its inner runs
     * collapsed. The code is upper-cased because the utf8mb4_unicode_ci
     * collation makes both the exists rule and the foreign key accept "vnd":
     * without this the wallet would store "vnd" and the frontend could not
     * find its currency by code.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => Str::squish($this->input('name'))]);
        }

        if (is_string($this->input('currency_code'))) {
            $this->merge(['currency_code' => Str::upper($this->input('currency_code'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The status is deliberately absent: a new wallet is always active, and
     * it is moved to another status through its own endpoint.
     *
     * Uniqueness is scoped to the owner, matching the UNIQUE(user_id, name)
     * index. The utf8mb4_unicode_ci collation makes the comparison ignore
     * case and diacritics, so "tien mat" collides with an existing "Tiền Mặt".
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $code = $this->input('currency_code');
        // find() given an array would return a collection, hence the guard.
        $currency = is_string($code) ? Currency::find($code) : null;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('wallets')->where('user_id', $this->user()->id),
            ],
            'type' => ['required', Rule::enum(WalletType::class)],
            'currency_code' => [
                'required',
                'string',
                Rule::exists('currencies', 'code')->where('is_active', true),
            ],
            'initial_balance' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999999',
                // Without a currency the error already sits on currency_code.
                ...($currency ? [new MoneyPrecision($currency)] : []),
            ],
            'description' => ['nullable', 'string', 'max:255'],
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
            'name.unique' => __('You already have a wallet with this name.'),
            'currency_code.exists' => __('The selected currency is not available.'),
        ];
    }
}
