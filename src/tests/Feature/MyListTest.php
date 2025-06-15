<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Like;
use App\Models\PaymentMethod;
use Database\Seeders\ItemConditionsSeeder;
use Database\Seeders\PaymentMethodsSeeder;
use Database\Seeders\ItemsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyListTest extends TestCase
{
    use RefreshDatabase;

    // いいねした商品だけが表示される
    public function test_only_liked_items_are_displayed()
    {
        $this->seed(ItemConditionsSeeder::class);
        $this->seed(ItemsSeeder::class);

        $user = User::factory()->create();

        // いいねした商品
        $likedItem = Item::factory()->create(['name' => 'いいねした商品']);

        Like::create([
            'user_id' => $user->id,
            'item_id' => $likedItem->id,
        ]);

        // いいねしていない商品
        $notLikedItem = Item::factory()->create(['name' => 'いいねしていない商品']);

        $response = $this->actingAs($user)->get('/?page=mylist');
        $response->assertStatus(200);

        // いいねした商品は表示される
        $response->assertSeeText('いいねした商品');

        // いいねしていない商品は表示されない
        $response->assertDontSeeText('いいねしていない商品');
    }

    // 購入済み商品は「Sold」と表示される
    public function test_purchased_items_display_sold_label_in_mylist()
    {
        $this->seed(ItemConditionsSeeder::class);
        $this->seed(PaymentMethodsSeeder::class);
        $this->seed(ItemsSeeder::class);

        $user = User::factory()->create();

        $likedItem = Item::factory()->create();
        Like::create([
            'user_id' => $user->id,
            'item_id' => $likedItem->id,
        ]);

        // 購入済みにする
        Purchase::factory()->create([
            'item_id' => $likedItem->id,
            'buyer_id' => $user->id,
            'completed_at' => now(),
            'paid_at' => now(),
            'payment_method_id' => PaymentMethod::first()->id,
        ]);

        $response = $this->actingAs($user)->get('/?page=mylist');
        $response->assertStatus(200);
        $response->assertSeeText('Sold');
    }

    // 自分が出品した商品は表示されない
    public function test_user_does_not_see_own_items_in_mylist()
    {
        $this->seed(ItemConditionsSeeder::class);
        $user = User::factory()->create();

        // 自分の商品（非表示にするべき）
        $ownItem = Item::factory()->create([
            'seller_id' => $user->id,
            'name' => '自分の商品',
        ]);
        Like::create([
            'user_id' => $user->id,
            'item_id' => $ownItem->id,
        ]);

        // 他人の商品（表示されるべき）
        $otherItem = Item::factory()->create([
            'name' => '他人の商品',
        ]);
        Like::create([
            'user_id' => $user->id,
            'item_id' => $otherItem->id,
        ]);

        $response = $this->actingAs($user)->get('/?page=mylist');
        $response->assertStatus(200);

        $response->assertDontSeeText('自分の商品');
        $response->assertSeeText('他人の商品');
    }

    // 未認証の場合は何も表示されない
    public function test_nothing_displayed_when_not_authenticated()
    {
        $this->seed(ItemConditionsSeeder::class);
        $this->seed(ItemsSeeder::class);

        $response = $this->get('/?page=mylist');
        $response->assertStatus(200);

        // Seederで入れた商品の名前（例: 「腕時計」）が見えないことを確認
        $response->assertDontSeeText('腕時計');
        $response->assertDontSeeText('HDD');
        $response->assertDontSeeText('玉ねぎ3束');
        $response->assertDontSeeText('革靴');
        $response->assertDontSeeText('ノートPC');
        $response->assertDontSeeText('マイク');
        $response->assertDontSeeText('ショルダーバッグ');
        $response->assertDontSeeText('タンブラー');
        $response->assertDontSeeText('コーヒーミル');
        $response->assertDontSeeText('メイクセット');

        // 未認証状態の確認
        $response->assertSeeText('ログイン');
    }
}
