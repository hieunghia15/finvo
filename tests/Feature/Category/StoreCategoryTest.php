<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/categories')->post('/categories', [
            'name' => 'Ăn uống',
            'type' => 'expense',
        ]);

        $response->assertRedirect('/categories');
        $response->assertSessionHas('status', 'Category created.');
        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Ăn uống',
            'type' => 'expense',
            'status' => 'active',
        ]);
    }

    public function test_a_status_in_the_payload_is_ignored(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/categories')->post('/categories', [
            'name' => 'Ăn uống',
            'type' => 'expense',
            'status' => 'archived',
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Ăn uống',
            'status' => 'active',
        ]);
    }

    public function test_name_whitespace_is_trimmed_and_collapsed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/categories')->post('/categories', [
            'name' => "  Ăn   uống \t ngoài  ",
            'type' => 'expense',
        ]);

        $this->assertDatabaseHas('categories', ['name' => 'Ăn uống ngoài']);
    }

    public function test_the_same_name_and_type_is_rejected(): void
    {
        $user = User::factory()->create();
        Category::factory()->expense()->for($user)->create(['name' => 'Ăn uống']);

        $response = $this->actingAs($user)->from('/categories')->post('/categories', [
            'name' => 'Ăn uống',
            'type' => 'expense',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, Category::where('user_id', $user->id)->count());
    }

    public function test_the_same_name_with_another_type_is_allowed(): void
    {
        $user = User::factory()->create();
        Category::factory()->expense()->for($user)->create(['name' => 'Thưởng']);

        $response = $this->actingAs($user)->from('/categories')->post('/categories', [
            'name' => 'Thưởng',
            'type' => 'income',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(2, Category::where('user_id', $user->id)->count());
    }

    public function test_it_collides_with_an_archived_category(): void
    {
        $user = User::factory()->create();
        Category::factory()->expense()->archived()->for($user)->create(['name' => 'Ăn uống']);

        $response = $this->actingAs($user)->from('/categories')->post('/categories', [
            'name' => 'Ăn uống',
            'type' => 'expense',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_the_name_comparison_ignores_case_and_diacritics(): void
    {
        // utf8mb4_unicode_ci: "luong" and "Lương" are the same string to MySQL,
        // so the unique index would reject the insert anyway.
        $user = User::factory()->create();
        Category::factory()->income()->for($user)->create(['name' => 'Lương']);

        $response = $this->actingAs($user)->from('/categories')->post('/categories', [
            'name' => 'luong',
            'type' => 'income',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_another_users_category_does_not_collide(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Category::factory()->expense()->for($other)->create(['name' => 'Ăn uống']);

        $response = $this->actingAs($user)->from('/categories')->post('/categories', [
            'name' => 'Ăn uống',
            'type' => 'expense',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Ăn uống',
        ]);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'missing name' => [['type' => 'expense'], 'name'],
            'empty name' => [['name' => '', 'type' => 'expense'], 'name'],
            'whitespace only name' => [['name' => '   ', 'type' => 'expense'], 'name'],
            'name too long' => [['name' => str_repeat('a', 101), 'type' => 'expense'], 'name'],
            'name not a string' => [['name' => ['Ăn uống'], 'type' => 'expense'], 'name'],
            'missing type' => [['name' => 'Ăn uống'], 'type'],
            'unknown type' => [['name' => 'Ăn uống', 'type' => 'transfer'], 'type'],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidPayloads')]
    public function test_it_rejects_invalid_payloads(array $payload, string $field): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/categories')->post('/categories', $payload);

        $response->assertRedirect('/categories');
        $response->assertSessionHasErrors($field);
        $this->assertSame(0, Category::where('user_id', $user->id)->count());
    }

    public function test_guest_cannot_create_a_category(): void
    {
        $response = $this->post('/categories', ['name' => 'Ăn uống', 'type' => 'expense']);

        $response->assertRedirect('/login');
    }
}
