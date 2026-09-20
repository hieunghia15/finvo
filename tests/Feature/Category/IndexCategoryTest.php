<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class IndexCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_categories(): void
    {
        $response = $this->get('/categories');

        $response->assertRedirect('/login');
    }

    public function test_it_sends_only_whitelisted_fields(): void
    {
        $user = User::factory()->create();
        Category::factory()->income()->for($user)->create(['name' => 'Lương']);

        $response = $this->actingAs($user)->get('/categories');

        // The scoped closure fails on any key it does not assert, e.g. user_id
        // or the timestamps.
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            // The second argument turns off the "page file exists" check: the
            // React page is a separate task, so only the name is settled here.
            ->component('Categories/Index', false)
            ->has('categories', 1, fn (AssertableJson $category) => $category
                ->has('id')
                ->where('name', 'Lương')
                ->where('type', 'income')
                ->where('status', 'active')
                ->where('transactions_count', 0)));
    }

    public function test_it_echoes_the_filters_back(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/categories?type=income&include_archived=1');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filters.type', 'income')
            ->where('filters.include_archived', true));
    }

    public function test_filters_default_to_empty(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/categories');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filters.type', null)
            ->where('filters.include_archived', false));
    }

    public function test_archived_categories_are_hidden_by_default(): void
    {
        $user = User::factory()->create();
        Category::factory()->expense()->for($user)->create(['name' => 'Ăn uống']);
        Category::factory()->expense()->inactive()->for($user)->create(['name' => 'Di chuyển']);
        Category::factory()->expense()->archived()->for($user)->create(['name' => 'Giải trí']);

        $response = $this->actingAs($user)->get('/categories');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('categories', 2)
            ->where('categories.0.name', 'Ăn uống')
            ->where('categories.1.name', 'Di chuyển'));
    }

    public function test_include_archived_shows_every_status_not_only_archived(): void
    {
        $user = User::factory()->create();
        Category::factory()->expense()->for($user)->create(['name' => 'Ăn uống']);
        Category::factory()->expense()->inactive()->for($user)->create(['name' => 'Di chuyển']);
        Category::factory()->expense()->archived()->for($user)->create(['name' => 'Giải trí']);

        $response = $this->actingAs($user)->get('/categories?include_archived=1');

        $response->assertInertia(fn (AssertableInertia $page) => $page->has('categories', 3));
    }

    public function test_it_can_be_narrowed_to_one_type(): void
    {
        $user = User::factory()->create();
        Category::factory()->income()->for($user)->create(['name' => 'Lương']);
        Category::factory()->expense()->for($user)->create(['name' => 'Ăn uống']);

        $response = $this->actingAs($user)->get('/categories?type=income');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('categories', 1)
            ->where('categories.0.name', 'Lương'));
    }

    public function test_income_comes_before_expense_and_names_are_sorted_within_a_type(): void
    {
        $user = User::factory()->create();
        // Inserted out of order on purpose: ordering by the type column alone
        // would put expense first, since 'expense' < 'income' alphabetically.
        Category::factory()->expense()->for($user)->create(['name' => 'Di chuyển']);
        Category::factory()->income()->for($user)->create(['name' => 'Thưởng']);
        Category::factory()->expense()->for($user)->create(['name' => 'Ăn uống']);
        Category::factory()->income()->for($user)->create(['name' => 'Lương']);

        $response = $this->actingAs($user)->get('/categories');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.0.name', 'Lương')
            ->where('categories.1.name', 'Thưởng')
            ->where('categories.2.name', 'Ăn uống')
            ->where('categories.3.name', 'Di chuyển'));
    }

    public function test_it_counts_transactions_including_for_archived_categories(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->expense()->archived()->for($user)->create(['name' => 'Ăn uống']);
        Transaction::factory()->expense()->count(3)->for($user)->for($category)->create();

        $response = $this->actingAs($user)->get('/categories?include_archived=1');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.0.transactions_count', 3));
    }

    public function test_it_only_lists_the_current_users_categories(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Category::factory()->income()->for($user)->create(['name' => 'Lương']);
        Category::factory()->income()->for($other)->create(['name' => 'Thưởng']);

        $response = $this->actingAs($user)->get('/categories');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('categories', 1)
            ->where('categories.0.name', 'Lương'));
    }

    public function test_an_unknown_type_filter_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/categories')->get('/categories?type=transfer');

        $response->assertSessionHasErrors('type');
    }
}
