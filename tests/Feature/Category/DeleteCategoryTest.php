<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unused_category_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->expense()->for($user)->create();

        $response = $this->actingAs($user)->from('/categories')->delete("/categories/{$category->id}");

        $response->assertRedirect('/categories');
        $response->assertSessionHas('status', 'Category deleted.');
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_a_category_with_transactions_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->expense()->for($user)->create();
        Transaction::factory()->expense()->for($user)->for($category)->create();

        $response = $this->actingAs($user)->from('/categories')->delete("/categories/{$category->id}");

        // Not a field error: it comes back as a flashed message instead.
        $response->assertRedirect('/categories');
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('error', 'This category still has transactions and cannot be deleted.');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_an_archived_category_without_transactions_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->expense()->archived()->for($user)->create();

        $response = $this->actingAs($user)->from('/categories')->delete("/categories/{$category->id}");

        $response->assertSessionHas('status', 'Category deleted.');
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_the_redirect_keeps_the_active_filters(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->expense()->archived()->for($user)->create();

        $response = $this->actingAs($user)
            ->from('/categories?type=expense&include_archived=1')
            ->delete("/categories/{$category->id}");

        $response->assertRedirect('/categories?type=expense&include_archived=1');
    }

    public function test_another_users_category_is_not_found(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::factory()->expense()->for($other)->create();

        $response = $this->actingAs($user)->delete("/categories/{$category->id}");

        $response->assertNotFound();
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_guest_cannot_delete_a_category(): void
    {
        $category = Category::factory()->expense()->create();

        $response = $this->delete("/categories/{$category->id}");

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
