<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;
use Database\Seeders\ItemConditionsSeeder;
use Database\Seeders\PaymentMethodsSeeder;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    // 必要な情報が取得できる（プロフィール画像、ユーザー名、出品した商品一覧、購入した商品一覧）
    public function test_profile_displays_correct_user_info_and_items_in_both_tabs()
    {
        $this->seed(ItemConditionsSeeder::class);
        $this->seed(PaymentMethodsSeeder::class);

        // プロフィール画像ファイル名を直接指定してユーザー作成
        $user = User::factory()->create([
            'name' => 'テストユーザー',
            'image_filename' => 'profile.jpg',
        ]);
        $this->actingAs($user);

        // 出品商品を作成
        $sellingItems = Item::factory()->count(2)->create([
            'seller_id' => $user->id,
        ]);

        // 別の出品者と購入商品を作成
        $seller = User::factory()->create();
        $purchasedItems = Item::factory()->count(2)->create([
            'seller_id' => $seller->id,
        ]);

        foreach ($purchasedItems as $item) {
            Purchase::factory()->create([
                'item_id' => $item->id,
                'buyer_id' => $user->id,
                'completed_at' => now(),
            ]);
        }

        // 出品タブチェック
        $sellResponse = $this->get('/mypage?page=sell');
        $sellResponse->assertStatus(200);
        $sellResponse->assertSee('テストユーザー');
        $sellResponse->assertSee('storage/users/' . $user->image_filename);

        foreach ($sellingItems as $item) {
            $sellResponse->assertSee($item->name);
            $sellResponse->assertSee('storage/items/' . $item->image_filename);
        }

        // 購入タブチェック
        $buyResponse = $this->get('/mypage?page=buy');
        $buyResponse->assertStatus(200);
        $buyResponse->assertSee('テストユーザー');
        $buyResponse->assertSee('storage/users/' . $user->image_filename);

        foreach ($purchasedItems as $item) {
            $buyResponse->assertSee($item->name);
            $buyResponse->assertSee('storage/items/' . $item->image_filename);
            $buyResponse->assertSee('Sold');
        }
    }

    // 変更項目が初期値として過去設定されていること（プロフィール画像、ユーザー名、郵便番号、住所）
    public function test_edit_profile_page_displays_user_initial_values_correctly()
    {
        // ユーザーを作成
        $user = User::factory()->create([
            'name' => 'テスト',
            'image_filename' => 'profile.jpg',
            'postal_code' => '123-4567',
            'address' => '東京都渋谷区1-2-3',
            'building' => 'サンプルビル101'
        ]);

        // ログイン
        $this->actingAs($user);

        // プロフィール編集ページを開く
        $response = $this->get('/mypage/profile');

        $response->assertStatus(200);

        // 各項目の初期値が正しく含まれているか確認
        $response->assertSee('value="テスト"', false);
        $response->assertSee('value="123-4567"', false);
        $response->assertSee('value="東京都渋谷区1-2-3"', false);
        $response->assertSee('value="サンプルビル101"', false);

        // プロフィール画像パスが正しく出力されているか
        $response->assertSee('storage/users/profile.jpg', false);
    }

}
