<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Like;
use App\Models\ItemCondition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\ItemConditionsSeeder;

class LikeTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(ItemConditionsSeeder::class);
    }

    // いいねアイコンを押下することによって、いいねした商品として登録することができる。
    public function test_user_can_like_an_item()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create([
            'item_condition_id' => ItemCondition::first()->id,
        ]);

        $this->actingAs($user);

        // 商品詳細ページを表示
        $this->get("/item/{$item->id}")
        ->assertStatus(200)
        ->assertSee('form class="like-form"', false); // フォームが存在するか確認

        // フォームを使って「いいね」押下（通常のPOSTリクエスト）
        $response = $this->post(route('like', $item->id));

        // リダイレクト確認
        $response->assertStatus(302);

        // データベースに「いいね」登録されていること
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        // 再度詳細ページで「いいね数」が1で表示されていること
        $htmlResponse = $this->get("/item/{$item->id}");
        $htmlResponse->assertStatus(200)
            ->assertSee('<div class="content-like-num" id="like-count-' . $item->id . '">1</div>', false);
    }

    // 追加済みのアイコンは色が変化する
    public function test_like_icon_changes_when_liked()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create([
            'item_condition_id' => ItemCondition::first()->id,
        ]);

        // いいね済みにする
        Like::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $this->actingAs($user);

        // 商品詳細ページにアクセス
        $response = $this->get("/item/{$item->id}");

        $response->assertStatus(200);

        // 星アイコンがONになっていることを確認
        $response->assertSee('star-on.png');
    }

    // 再度いいねアイコンを押下することによって、いいねを解除することができる。
    public function test_user_can_unlike_an_item_via_form()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create([
            'item_condition_id' => ItemCondition::first()->id,
        ]);

        // 初期状態：いいね済みにしておく
        Like::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $this->actingAs($user);

        // 商品詳細ページを開く
        $this->get("/item/{$item->id}")
            ->assertStatus(200)
            ->assertSee('form class="like-form"', false); // フォーム確認

        // フォームをPOSTして「いいね解除」を実行
        $response = $this->post(route('like', $item->id));

        // 元の詳細ページに戻る
        $response->assertStatus(302);

        // データベースにいいねが削除されたことを確認
        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        // 商品詳細ページで「いいね数：0」が表示されているか確認
        $htmlResponse = $this->get("/item/{$item->id}");
        $htmlResponse->assertStatus(200)
            ->assertSee('<div class="content-like-num" id="like-count-' . $item->id . '">0</div>', false);
    }
}
