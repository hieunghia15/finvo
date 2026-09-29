<?php

namespace Database\Seeders;

use App\Enums\EntityStatus;
use App\Enums\WalletType;
use App\Models\User;
use App\Models\Wallet;
use App\Services\UserOnboardingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sample wallets and transactions for the admin account. Local environment only.
 */
class DemoDataSeeder extends Seeder
{
    public function __construct(private readonly UserOnboardingService $onboarding) {}

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // The demo data is Vietnamese whatever APP_LOCALE says; the category
        // lookups below use the Vietnamese names.
        app()->setLocale('vi');

        $user = User::where('email', AdminUserSeeder::EMAIL)->firstOrFail();

        if ($user->transactions()->exists()) {
            $this->command->info('Demo data already present, skipping.');

            return;
        }

        DB::transaction(function () use ($user) {
            $this->onboarding->createDefaults($user);

            $bank = $user->wallets()->where('name', __(UserOnboardingService::DEFAULT_WALLET_NAME))->firstOrFail();
            $bank->update(['initial_balance' => 5_000_000]);

            $cash = $user->wallets()->create([
                'name' => 'Tiền mặt',
                'type' => WalletType::Cash,
                'currency_code' => 'VND',
                'initial_balance' => 2_000_000,
            ]);

            $momo = $user->wallets()->create([
                'name' => 'MoMo',
                'type' => WalletType::EWallet,
                'currency_code' => 'VND',
                'initial_balance' => 500_000,
            ]);

            $paypal = $user->wallets()->create([
                'name' => 'Paypal',
                'type' => WalletType::EWallet,
                'currency_code' => 'USD',
                'initial_balance' => 100,
            ]);

            $user->wallets()->create([
                'name' => 'Ví cũ',
                'type' => WalletType::Other,
                'currency_code' => 'VND',
                'status' => EntityStatus::Archived,
            ]);

            $categories = $user->categories()->get()->keyBy('name');

            $add = function (Wallet $wallet, string $categoryName, int|float $amount, Carbon $date, ?string $note = null) use ($user, $categories) {
                // Future dates are not allowed for transactions.
                if ($date->isFuture()) {
                    return;
                }

                $category = $categories[$categoryName];

                $user->transactions()->create([
                    'wallet_id' => $wallet->id,
                    'category_id' => $category->id,
                    'type' => $category->type,
                    'amount' => $amount,
                    'transaction_date' => $date->toDateString(),
                    'note' => $note,
                ]);
            };

            // Three months of recurring income and expenses.
            foreach ([2, 1, 0] as $monthsAgo) {
                $month = now()->startOfMonth()->subMonthsNoOverflow($monthsAgo);
                $day = fn (int $day) => $month->copy()->day($day);

                $add($bank, 'Lương', 15_000_000, $day(5), 'Lương tháng '.$month->month);
                $add($bank, 'Nhà ở', 4_000_000, $day(6), 'Tiền nhà');
                $add($bank, 'Hóa đơn', 850_000, $day(10), 'Điện nước internet');
                $add($cash, 'Ăn uống', 45_000, $day(1), 'cafe');
                $add($cash, 'Ăn uống', 120_000, $day(3), 'Ăn trưa');
                $add($momo, 'Di chuyển', 65_000, $day(4), 'Grab');
                $add($momo, 'Mua sắm', 350_000, $day(8), 'Shopee');
                $add($cash, 'Giải trí', 200_000, $day(12), 'Xem phim');
                $add($cash, 'Sức khỏe', 150_000, $day(15), null);
                $add($paypal, 'Thu nhập khác', 25.50, $day(9), 'Freelance');
                $add($paypal, 'Mua sắm', 12.99, $day(11), 'App subscription');
            }

            $add($bank, 'Thưởng', 3_000_000, now()->startOfDay(), 'Thưởng dự án');
        });
    }
}
