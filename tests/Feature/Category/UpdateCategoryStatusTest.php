<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateCategoryStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_category_can_be_archived(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->expense()->for($user)->create();

        $response = $this->actingAs($user)->from('/categories')
            ->patch("/categories/{$category->id}/status", ['status' => 'archived']);

        $response->assertRedirect('/categories');
        $response->assertSessionHas('status', 'Category status updated.');
        $this->assertSame('archived', $category->fresh()->status->value);
    }

    public function test_an_archived_category_can_be_restored(): void
    {
        // The details of an archived category are locked, but its status is
        // not: that is the whole reason this endpoint is separate.
        $user = User::factory()->create();
        $category = Category::factory()->expense()->archived()->for($user)->create();

        $response = $this->actingAs($user)->from('/categories')
            ->patch("/categories/{$category->id}/status", ['status' => 'active']);

        $response->assertSessionHasNoErrors();
        $this->assertSame('active', $category->fresh()->status->value);
    }

    public function test_an_archived_category_can_move_straight_to_inactive(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->expense()->archived()->for($user)->create();

        $response = $this->actingAs($user)->from('/categories')
            ->patch("/categories/{$category->id}/status", ['status' => 'inactive']);

        $response->assertSessionHasNoErrors();
        $this->assertSame('inactive', $category->fresh()->status->value);
    }

    public function test_a_used_category_can_still_change_status(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->expense()->for($user)->create();
        Transaction::factory()->expense()->for($user)->for($category)->create();

        $response = $this->actingAs($user)->from('/categories')
            ->patch("/categories/{$category->id}/status", ['status' => 'archived']);

        $response->assertSessionHasNoErrors();
        $this->assertSame('archived', $category->fresh()->status->value);
    }

    public function test_an_unknown_status_is_rejected(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->expense()->for($user)->create();

        $response = $this->actingAs($user)->from('/categories')
            ->patch("/categories/{$category->id}/status", ['status' => 'deleted']);

        $response->assertSessionHasErrors('status');
        $this->assertSame('active', $category->fresh()->status->value);
    }

    public function test_a_missing_status_is_rejected(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->expense()->for($user)->create();

        $response = $this->actingAs($user)->from('/categories')
            ->patch("/categories/{$category->id}/status", []);

        $response->assertSessionHasErrors('status');
    }

    public function test_another_users_category_is_not_found(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::factory()->expense()->for($other)->create();

        $response = $this->actingAs($user)
            ->patch("/categories/{$category->id}/status", ['status' => 'archived']);

        $response->assertNotFound();
        $this->assertSame('active', $category->fresh()->status->value);
    }

    public function test_guest_cannot_change_a_status(): void
    {
        $category = Category::factory()->expense()->create();

        $response = $this->patch("/categories/{$category->id}/status", ['status' => 'archived']);

        $response->assertRedirect('/login');
    }
}
