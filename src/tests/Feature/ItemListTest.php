<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\ItemsSeeder;
use Database\Seeders\ItemConditionsSeeder;
use Database\Seeders\PaymentMethodsSeeder;

class ItemListTest extends TestCase
{
    use RefreshDatabase;

    // 全商品を取得できる
    public function test_all_items_are_displayed()
    {
        $this->seed(ItemConditionsSeeder::class);
        $this->seed(ItemsSeeder::class);

        $response = $this->get('/');

        $response->assertStatus(200);

        // 各商品の名前がページに表示されているか
        Item::all()->each(function ($item) use ($response) {
            $response->assertSeeText($item->name);
        });
    }

    // 購入済み商品は「Sold」と表示される
    public function test_purchased_items_display_sold_label()
    {
        $this->seed(ItemConditionsSeeder::class);
        $this->seed(PaymentMethodsSeeder::class);
        $this->seed(ItemsSeeder::class);

        $paymentMethod = \App\Models\PaymentMethod::first();

        $purchase = \App\Models\Purchase::factory()->create([
            'completed_at' => now(),
            'paid_at' => now(),
            'payment_method_id' => $paymentMethod->id,
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSeeText('Sold');
    }

    // 自分が出品した商品は表示されない
    public function test_user_does_not_see_their_own_items()
    {
        $this->seed(ItemConditionsSeeder::class);
        $user = User::factory()->create();

        // 自分の商品（非表示にするべき）
        Item::factory()->create([
            'seller_id' => $user->id,
            'name' => '自分の商品',
        ]);

        // 他人の商品（表示されるべき）
        $otherItem = Item::factory()->create([
            'name' => '他人の商品',
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertDontSeeText('自分の商品');
        $response->assertSeeText('他人の商品');
    }
}
