<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\LocaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * The suite runs in English (phpunit.xml); these tests pick Vietnamese
 * explicitly through the cookie, the way a browser does.
 */
class LocaleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  TestResponse<Response>  $response
     *
     * @return array<string, mixed>
     */
    private function pageProps(TestResponse $response): array
    {
        return $response->viewData('page')['props'];
    }

    // =========================================================================
    // Choosing the locale
    // =========================================================================

    public function test_without_a_cookie_the_configured_default_locale_is_used(): void
    {
        $this->app->setLocale('vi');

        $response = $this->get('/login');

        $response->assertInertia(fn (AssertableInertia $page) => $page->where('locale', 'vi'));
        $this->assertSame('Đã cập nhật tài khoản.', $this->pageProps($response)['translations']['Account updated.']);
    }

    public function test_the_cookie_selects_english_with_an_empty_dictionary(): void
    {
        $this->app->setLocale('vi');

        $response = $this->withCookie(LocaleService::COOKIE, 'en')->get('/login');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('locale', 'en')
            ->where('translations', []));
    }

    public function test_the_cookie_selects_vietnamese_with_its_dictionary(): void
    {
        $response = $this->withCookie(LocaleService::COOKIE, 'vi')->get('/login');

        $response->assertInertia(fn (AssertableInertia $page) => $page->where('locale', 'vi'));
        $this->assertSame(
            json_decode((string) file_get_contents(lang_path('vi.json')), true),
            $this->pageProps($response)['translations'],
        );
    }

    public function test_an_unsupported_cookie_value_falls_back_to_the_default(): void
    {
        $response = $this->withCookie(LocaleService::COOKIE, 'fr')->get('/login');

        $response->assertInertia(fn (AssertableInertia $page) => $page->where('locale', 'en'));
    }

    public function test_the_html_lang_attribute_follows_the_cookie(): void
    {
        $this->withCookie(LocaleService::COOKIE, 'vi')->get('/login')->assertSee('<html lang="vi">', false);
        $this->withCookie(LocaleService::COOKIE, 'en')->get('/login')->assertSee('<html lang="en">', false);
    }

    // =========================================================================
    // PUT /locale
    // =========================================================================

    public function test_a_guest_can_switch_language(): void
    {
        $response = $this->from('/login')->put('/locale', ['locale' => 'en']);

        $response->assertRedirect('/login');
        $response->assertCookie(LocaleService::COOKIE, 'en');
    }

    public function test_a_logged_in_user_can_switch_language_and_stays_logged_in(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/categories?type=income')->put('/locale', ['locale' => 'vi']);

        $response->assertRedirect('/categories?type=income');
        $response->assertCookie(LocaleService::COOKIE, 'vi');
        $this->assertAuthenticatedAs($user);
    }

    public function test_the_cookie_lasts_one_year(): void
    {
        $response = $this->from('/login')->put('/locale', ['locale' => 'vi']);

        $cookie = $response->getCookie(LocaleService::COOKIE, false);
        $this->assertNotNull($cookie);
        $this->assertEqualsWithDelta(now()->addYear()->getTimestamp(), $cookie->getExpiresTime(), 86_400);
        $this->assertTrue($cookie->isHttpOnly());
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function invalidLocalePayloads(): array
    {
        return [
            'unsupported' => [['locale' => 'fr']],
            'missing' => [[]],
            'not a string' => [['locale' => ['vi']]],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidLocalePayloads')]
    public function test_switching_to_an_invalid_language_is_rejected(array $payload): void
    {
        $response = $this->from('/login')->put('/locale', $payload);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('locale');
        $response->assertCookieMissing(LocaleService::COOKIE);
    }

    public function test_the_language_survives_logout(): void
    {
        $user = User::factory()->create();

        $logout = $this->actingAs($user)->withCookie(LocaleService::COOKIE, 'vi')->post('/logout');

        $logout->assertCookieMissing(LocaleService::COOKIE);
        $this->get('/login')->assertInertia(fn (AssertableInertia $page) => $page->where('locale', 'vi'));
    }

    // =========================================================================
    // Dictionary as a once prop
    // =========================================================================

    /**
     * @return array<string, string>
     */
    private function inertiaHeaders(string $exceptOnceProps): array
    {
        $version = app(HandleInertiaRequests::class)->version(Request::create('/login'));

        return [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) $version,
            'X-Inertia-Except-Once-Props' => $exceptOnceProps,
        ];
    }

    public function test_the_dictionary_is_not_resent_when_the_client_already_has_it(): void
    {
        $response = $this->withCookie(LocaleService::COOKIE, 'vi')
            ->withHeaders($this->inertiaHeaders('translations.vi'))
            ->get('/login');

        $response->assertOk();
        $this->assertSame('vi', $response->json('props.locale'));
        $this->assertArrayNotHasKey('translations', $response->json('props'));
    }

    public function test_the_dictionary_is_sent_again_after_switching_language(): void
    {
        $response = $this->withCookie(LocaleService::COOKIE, 'en')
            ->withHeaders($this->inertiaHeaders('translations.vi'))
            ->get('/login');

        $response->assertOk();
        $this->assertSame('en', $response->json('props.locale'));
        $this->assertSame([], $response->json('props.translations'));
    }

    // =========================================================================
    // Translated backend messages
    // =========================================================================

    public function test_flash_messages_are_translated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->withCookie(LocaleService::COOKIE, 'vi')
            ->patch('/account', ['name' => 'Nguyễn Văn A']);

        $response->assertSessionHas('status', 'Đã cập nhật tài khoản.');
    }

    public function test_laravel_validation_messages_use_vietnamese_attribute_names(): void
    {
        $response = $this->withCookie(LocaleService::COOKIE, 'vi')->from('/register')->post('/register', []);

        $response->assertSessionHasErrors([
            'name' => 'Trường tên là bắt buộc.',
            'password' => 'Trường mật khẩu là bắt buộc.',
        ]);
    }

    public function test_custom_rule_messages_are_translated(): void
    {
        $response = $this->withCookie(LocaleService::COOKIE, 'vi')->from('/register')->post('/register', [
            'name' => 'Nguyễn Văn A',
            'email' => 'not-an-email',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'Trường email phải là một địa chỉ email hợp lệ.',
            'password' => 'Mật khẩu phải dài 6–32 ký tự và có ít nhất một chữ hoa, một chữ thường, một chữ số và một ký tự đặc biệt.',
        ]);
    }

    public function test_failed_login_message_is_translated(): void
    {
        User::factory()->create(['email' => 'john@example.com', 'password' => 'Passw0rd!']);

        $response = $this->withCookie(LocaleService::COOKIE, 'vi')->from('/login')->post('/login', [
            'email' => 'john@example.com',
            'password' => 'Wrong-Pass1',
        ]);

        $response->assertSessionHasErrors(['email' => 'Email hoặc mật khẩu không đúng.']);
    }

    public function test_rate_limit_messages_are_translated(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->withCookie(LocaleService::COOKIE, 'vi')->from('/register')->post('/register', []);
        }

        $response = $this->withCookie(LocaleService::COOKIE, 'vi')->from('/register')->post('/register', []);

        $response->assertSessionHasErrors([
            'email' => 'Bạn đã thử đăng ký quá nhiều lần. Vui lòng thử lại sau 60 giây.',
        ]);
    }

    public function test_category_messages_are_translated(): void
    {
        $user = User::factory()->create();
        Category::factory()->expense()->for($user)->create(['name' => 'Cafe']);
        $inUse = Category::factory()->expense()->for($user)->create();
        Transaction::factory()->expense()->for($user)->for($inUse)->create();

        $duplicate = $this->actingAs($user)->withCookie(LocaleService::COOKIE, 'vi')
            ->from('/categories')->post('/categories', ['name' => 'cafe', 'type' => 'expense']);
        $duplicate->assertSessionHasErrors(['name' => 'Bạn đã có một danh mục cùng tên và cùng loại.']);

        $delete = $this->actingAs($user)->withCookie(LocaleService::COOKIE, 'vi')
            ->from('/categories')->delete("/categories/{$inUse->id}");
        $delete->assertSessionHas('error', 'Danh mục này vẫn còn giao dịch nên không thể xóa.');
    }

    // =========================================================================
    // Default data follows the registration language
    // =========================================================================

    /**
     * @return array<string, string>
     */
    private function registrationData(): array
    {
        return [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ];
    }

    public function test_registering_in_vietnamese_creates_vietnamese_defaults(): void
    {
        $this->withCookie(LocaleService::COOKIE, 'vi')->post('/register', $this->registrationData())->assertRedirect('/login');

        $user = User::where('email', 'john@example.com')->firstOrFail();

        $this->assertSame('Ngân hàng', $user->wallets()->sole()->name);
        $this->assertEqualsCanonicalizing(
            ['Lương', 'Thưởng', 'Thu nhập khác', 'Ăn uống', 'Di chuyển', 'Mua sắm', 'Nhà ở', 'Hóa đơn', 'Giải trí', 'Sức khỏe', 'Chi phí khác'],
            $user->categories()->pluck('name')->all(),
        );
    }

    public function test_registering_in_english_creates_english_defaults(): void
    {
        $this->withCookie(LocaleService::COOKIE, 'en')->post('/register', $this->registrationData())->assertRedirect('/login');

        $user = User::where('email', 'john@example.com')->firstOrFail();

        $this->assertSame('Bank', $user->wallets()->sole()->name);
        $this->assertEqualsCanonicalizing(
            ['Salary', 'Bonus', 'Other income', 'Food & Drinks', 'Transportation', 'Shopping', 'Housing', 'Bills', 'Entertainment', 'Health', 'Other expenses'],
            $user->categories()->pluck('name')->all(),
        );
    }

    public function test_switching_language_later_does_not_rename_existing_defaults(): void
    {
        $this->withCookie(LocaleService::COOKIE, 'en')->post('/register', $this->registrationData());
        $user = User::where('email', 'john@example.com')->firstOrFail();

        $this->actingAs($user)->withCookie(LocaleService::COOKIE, 'vi')->get('/categories')->assertOk();

        $this->assertSame('Bank', $user->wallets()->sole()->name);
        $this->assertTrue($user->categories()->where('name', 'Salary')->exists());
    }
}
