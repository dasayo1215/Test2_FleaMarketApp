<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\ItemConditionsSeeder;
use Database\Seeders\PaymentMethodsSeeder;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(ItemConditionsSeeder::class);
        $this->seed(PaymentMethodsSeeder::class);
    }

    // 「購入する」ボタンを押下すると購入が完了する
    public function test_user_can_complete_purchase()
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $item = Item::factory()->create([
            'seller_id' => $seller->id,
        ]);

        $this->actingAs($buyer);

        // 商品購入画面を開く（GETリクエスト）
        $response = $this->get(route('purchase.show', $item->id));
        $response->assertStatus(200);

        // 購入ボタン押下（POST）
        $postData = [
            'payment_method' => 1,
            'postal_code' => '123-4567',
            'address' => '東京都新宿区テスト',
            'building' => 'テストビル 101',
        ];
        $purchaseResponse = $this->post("/purchase/{$item->id}", $postData);

        // DBに購入情報が登録されているかチェック（completed_atがnullじゃない）
        $this->assertDatabaseHas('purchases', [
            'buyer_id' => $buyer->id,
            'item_id' => $item->id,
        ]);

        $purchase = Purchase::where('buyer_id', $buyer->id)
            ->where('item_id', $item->id)
            ->first();

        $this->assertNotNull($purchase->completed_at, '購入が完了しているはずです');
    }

    // 購入した商品は商品一覧画面にて「Sold」と表示される
    public function test_user_can_purchase_item_and_it_shows_as_sold()
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $item = Item::factory()->create([
            'seller_id' => $seller->id,
        ]);

        $this->actingAs($buyer);

        // 商品購入画面をGETで開く
        $response = $this->get(route('purchase.show', $item->id));
        $response->assertStatus(200);

        // 支払い方法を選択
        $paymentMethodId = 1;

        // 「購入する」ボタン押下（POST）
        $postData = [
            'payment_method' => $paymentMethodId,
            'postal_code' => '123-4567',
            'address' => '東京都新宿区テスト',
            'building' => 'テストビル 101',
        ];

        $purchaseResponse = $this->post("/purchase/{$item->id}", $postData);
        $purchaseResponse->assertRedirect(); // 念のためリダイレクトチェック

        // 購入情報をDBから再取得して completed_at を確認
        $purchase = Purchase::where('item_id', $item->id)
            ->where('buyer_id', $buyer->id)
            ->first();

        $this->assertNotNull($purchase, 'Purchase record should exist.');
        $this->assertNotNull($purchase->completed_at, 'completed_at should be set after purchase.');

        // トップページで「Sold」表示を確認
        $listResponse = $this->get('/');
        $listResponse->assertStatus(200);
        $listResponse->assertSee('Sold');
        $listResponse->assertSee($item->name);

        // DB上も購入が記録されていることを確認
        $this->assertDatabaseHas('purchases', [
            'item_id' => $item->id,
            'buyer_id' => $buyer->id,
            'payment_method_id' => $paymentMethodId,
        ]);
    }


    // 「プロフィール/購入した商品一覧」に追加されている
    public function test_purchased_item_appears_in_user_profile_purchase_list()
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $item = Item::factory()->create([
            'seller_id' => $seller->id,
        ]);

        $this->actingAs($buyer);

        // 商品購入画面を開く(GET)
        $response = $this->get(route('purchase.show', $item->id));
        $response->assertStatus(200);

        // 支払い方法を選択し、購入処理をPOST
        $postData = [
            'payment_method' => 1,
            'postal_code' => '123-4567',
            'address' => '東京都新宿区テスト',
            'building' => 'テストビル 101',
        ];
        $purchaseResponse = $this->post("/purchase/{$item->id}", $postData);

        // プロフィールの購入履歴画面を表示
        $profileResponse = $this->get('/mypage?page=buy');
        $profileResponse->assertStatus(200);

        // 購入した商品名が表示されているか確認
        $profileResponse->assertSee($item->name);
    }
}
