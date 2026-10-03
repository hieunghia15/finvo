<?php

namespace App\Http\Requests\Wallet;

use App\Enums\EntityStatus;
use App\Http\Requests\Concerns\ResolvesWallet;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Status changes are deliberately separate from editing a wallet's details.
 *
 * Archived wallets cannot be edited but must stay restorable, and the two
 * rules only coexist without a special case when they are different endpoints.
 * There is no guard here: every transition is allowed in both directions,
 * transactions on the wallet do not restrict it, and neither does it being
 * the user's last active wallet.
 */
class UpdateWalletStatusRequest extends FormRequest
{
    use ResolvesWallet;

    /**
     * Determine if the user is authorized to make this request.
     *
     * Ownership is enforced by resolving the wallet through the current
     * user, which answers 404 instead of 403.
     */
    public function authorize(): bool
    {
        $this->wallet();

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
}
