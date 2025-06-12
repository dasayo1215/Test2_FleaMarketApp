<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\ItemConditionsSeeder;
use Database\Seeders\PaymentMethodsSeeder;

class DeliveryAddressTest extends TestCase
{
    use RefreshDatabase;

    // 送付先住所変更画面にて登録した住所が商品購入画面に反映されている
    public function test_updated_delivery_address_is_reflected_in_purchase_page()
    {
        $this->seed(ItemConditionsSeeder::class);

        // ユーザー作成＆ログイン
        $user = User::factory()->create();
        $this->actingAs($user);

        // 商品作成
        $item = Item::factory()->create();

        // 商品購入画面を開いて仮購入レコードを作成
        $this->get("/purchase/{$item->id}");

        // 送付先住所変更画面からPOST（新しい住所を登録）
        $newData = [
            'postal_code' => '123-4567',
            'address' => '東京都渋谷区渋谷1-2-3',
            'building' => '渋谷ビル101',
            'name' => 'ダミー名', // hidden input
        ];

        $this->post("/purchase/address/{$item->id}", $newData)
            ->assertRedirect("/purchase/{$item->id}");

        // 購入画面を再度表示し、新住所が反映されていることを確認
        $response = $this->get("/purchase/{$item->id}");

        $response->assertStatus(200);
        $response->assertSee('〒 123-4567');
        $response->assertSee('東京都渋谷区渋谷1-2-3');
        $response->assertSee('渋谷ビル101');
    }

    // 購入した商品に送付先住所が紐づいて登録される
    public function test_delivery_address_is_correctly_associated_with_purchase()
    {
        $this->seed([
            ItemConditionsSeeder::class,
            PaymentMethodsSeeder::class,
        ]);

        // ユーザー作成＆ログイン
        $user = User::factory()->create();
        $this->actingAs($user);

        // 商品と支払い方法作成
        $item = Item::factory()->create();
        $paymentMethod = PaymentMethod::first(); // 適当な支払い方法

        // 商品購入画面アクセス（仮購入レコード作成）
        $this->get("/purchase/{$item->id}");

        // 送付先住所を変更
        $newData = [
            'postal_code' => '123-4567',
            'address' => '東京都渋谷区渋谷1-2-3',
            'building' => '渋谷ビル101',
            'name' => 'テストユーザー',
        ];
        $this->post("/purchase/address/{$item->id}", $newData)
            ->assertRedirect("/purchase/{$item->id}");

        // POSTで購入処理
        $purchaseResponse = $this->post("/purchase/{$item->id}", [
            'postal_code' => $newData['postal_code'],
            'address' => $newData['address'],
            'building' => $newData['building'],
            'payment_method' => $paymentMethod->id,
        ]);

        // DBに保存されたか確認
        $this->assertDatabaseHas('purchases', [
            'buyer_id' => $user->id,
            'item_id' => $item->id,
            'postal_code' => $newData['postal_code'],
            'address' => $newData['address'],
            'building' => $newData['building'],
            'payment_method_id' => $paymentMethod->id,
        ]);
    }
}
