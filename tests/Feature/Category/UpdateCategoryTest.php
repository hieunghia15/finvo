<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_rename_a_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->expense()->for($user)->create(['name' => 'Ăn uống']);

        $response = $this->actingAs($user)->from('/categories')->patch("/categories/{$category->id}", [
            'name' => 'Ăn uống ngoài',
            'type' => 'expense',
        ]);

        $response->assertRedirect('/categories');
        $response->assertSessionHas('status', 'Category updated.');
        $this->assertSame('Ăn uống ngoài', $category->fresh()->name);
    }

    public function test_the_type_can_change_while_the_category_is_unused(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->expense()->for($user)->create(['name' => 'Thưởng']);

        $response = $this->actingAs($user)->from('/categories')->patch("/categories/{$category->id}", [
            'name' => 'Thưởng',
            'type' => 'income',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('income', $category->fresh()->type->value);
    }

    public function test_the_type_cannot_change_once_the_category_has_transactions(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->expense()->for($user)->create(['name' => 'Ăn uống']);
        Transaction::factory()->expense()->for($user)->for($category)->create();

        $response = $this->actingAs($user)->from('/categories')->patch("/categories/{$category->id}", [
            'name' => 'Ăn uống',
            'type' => 'income',
        ]);

        $response->assertSessionHasErrors('type');
        $this->assertSame('expense', $category->fresh()->type->value);
    }

    public function test_a_used_category_can_still_be_renamed_when_the_type_stays_put(): void
    {
        // The guard has to compare the submitted type with the stored one.
        // Blocking on "has transactions" alone would break plain renames.
        $user = User::factory()->create();
        $category = Category::factory()->expense()->for($user)->create(['name' => 'Ăn uống']);
        Transaction::factory()->expense()->for($user)->for($category)->create();

        $response = $this->actingAs($user)->from('/categories')->patch("/categories/{$category->id}", [
            'name' => 'Ăn uống ngoài',
            'type' => 'expense',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('Ăn uống ngoài', $category->fresh()->name);
    }

    public function test_an_archived_category_cannot_be_edited(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->expense()->archived()->for($user)->create(['name' => 'Ăn uống']);

        $response = $this->actingAs($user)->from('/categories')->patch("/categories/{$category->id}", [
            'name' => 'Ăn uống ngoài',
            'type' => 'expense',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertSame('Ăn uống', $category->fresh()->name);
    }

    public function test_an_inactive_category_can_be_edited(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->expense()->inactive()->for($user)->create(['name' => 'Ăn uống']);

        $response = $this->actingAs($user)->from('/categories')->patch("/categories/{$category->id}", [
            'name' => 'Ăn uống ngoài',
            'type' => 'expense',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('Ăn uống ngoài', $category->fresh()->name);
    }

    public function test_saving_a_category_unchanged_is_allowed(): void
    {
        // The unique rule has to ignore the row being edited.
        $user = User::factory()->create();
        $category = Category::factory()->expense()->for($user)->create(['name' => 'Ăn uống']);

        $response = $this->actingAs($user)->from('/categories')->patch("/categories/{$category->id}", [
            'name' => 'Ăn uống',
            'type' => 'expense',
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_renaming_onto_another_category_of_the_same_type_is_rejected(): void
    {
        $user = User::factory()->create();
        Category::factory()->expense()->for($user)->create(['name' => 'Di chuyển']);
        $category = Category::factory()->expense()->for($user)->create(['name' => 'Ăn uống']);

        $response = $this->actingAs($user)->from('/categories')->patch("/categories/{$category->id}", [
            'name' => 'Di chuyển',
            'type' => 'expense',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame('Ăn uống', $category->fresh()->name);
    }

    public function test_another_users_category_is_not_found(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::factory()->expense()->for($other)->create(['name' => 'Ăn uống']);

        $response = $this->actingAs($user)->patch("/categories/{$category->id}", [
            'name' => 'Đổi tên',
            'type' => 'expense',
        ]);

        $response->assertNotFound();
        $this->assertSame('Ăn uống', $category->fresh()->name);
    }

    public function test_guest_cannot_update_a_category(): void
    {
        $category = Category::factory()->expense()->create(['name' => 'Ăn uống']);

        $response = $this->patch("/categories/{$category->id}", [
            'name' => 'Đổi tên',
            'type' => 'expense',
        ]);

        $response->assertRedirect('/login');
    }
}
