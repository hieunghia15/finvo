<?php

namespace App\Http\Requests\Wallet;

use App\Enums\WalletType;
use App\Http\Requests\Concerns\ResolvesWallet;
use App\Models\Currency;
use App\Rules\MoneyPrecision;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateWalletRequest extends FormRequest
{
    use ResolvesWallet;

    /**
     * Determine if the user is authorized to make this request.
     *
     * Ownership is enforced by resolving the wallet through the current
     * user in rules(), which answers 404 instead of 403.
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
     * collapsed. The code is upper-cased because the collation accepts "vnd"
     * as an existing currency, and the guard in after() compares it to the
     * stored code with !==.
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
     * The form is always sent in full. The status is not editable here; it
     * has its own endpoint, which is what lets an archived wallet be
     * restored even though its details are locked.
     *
     * A wallet may keep a currency that has since been deactivated, so the
     * is_active check only applies when the currency is being changed.
     * The balance precision follows the currency being sent, not the stored
     * one.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $wallet = $this->wallet();
        $code = $this->input('currency_code');
        // find() given an array would return a collection, hence the guard.
        $currency = is_string($code) ? Currency::find($code) : null;

        $currencyExists = Rule::exists('currencies', 'code');

        if ($code !== $wallet->currency_code) {
            $currencyExists->where('is_active', true);
        }

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('wallets')
                    ->where('user_id', $this->user()->id)
                    ->ignore($wallet->id),
            ],
            'type' => ['required', Rule::enum(WalletType::class)],
            'currency_code' => ['required', 'string', $currencyExists],
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

    /**
     * Guards that need the stored wallet, not just the payload.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $wallet = $this->wallet();

                // An archived wallet is read-only until it is restored.
                if (!$wallet->status->isEditable()) {
                    $validator->errors()->add(
                        'status',
                        __('An archived wallet must be restored before it can be edited.'),
                    );

                    return;
                }

                // Only a real change is worth a database round trip, and the
                // comparison is by value: a wallet with transactions can still
                // be sent back with its own currency and balance. Fields that
                // already failed are skipped, since bccomp() throws on a
                // non-numeric string. The balance also waits for a valid
                // currency: without one MoneyPrecision is not attached, so
                // "1e3" would pass every rule and reach bccomp().
                $currencyChanged = !$validator->errors()->has('currency_code')
                    && $this->input('currency_code') !== $wallet->currency_code;
                $balanceChanged = !$validator->errors()->hasAny(['currency_code', 'initial_balance'])
                    && bccomp((string) $this->input('initial_balance'), $wallet->initial_balance, 4) !== 0;

                if (!$currencyChanged && !$balanceChanged) {
                    return;
                }

                if (!$wallet->transactions()->exists()) {
                    return;
                }

                if ($currencyChanged) {
                    $validator->errors()->add(
                        'currency_code',
                        __('This wallet already has transactions, so its currency can no longer be changed.'),
                    );
                }

                if ($balanceChanged) {
                    $validator->errors()->add(
                        'initial_balance',
                        __('This wallet already has transactions, so its initial balance can no longer be changed.'),
                    );
                }
            },
        ];
    }
}
