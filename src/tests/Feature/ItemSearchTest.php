<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Item;
use App\Models\User;
use App\Models\Like;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\ItemConditionsSeeder;

class ItemSearchTest extends TestCase
{
    use RefreshDatabase;

    // 「商品名」で部分一致検索ができる
    public function test_user_can_search_items_by_partial_name()
    {
        $this->seed(ItemConditionsSeeder::class);

        Item::factory()->create(['name' => '高性能ノートPC']);
        Item::factory()->create(['name' => 'ゲーミングPC']);
        Item::factory()->create(['name' => '冷蔵庫']); // 検索に含まれない

        $response = $this->get('/?keyword=PC');

        $response->assertStatus(200);
        $response->assertSeeText('高性能ノートPC');
        $response->assertSeeText('ゲーミングPC');
        $response->assertDontSeeText('冷蔵庫');
    }

    // 検索状態がマイリストでも保持されている
    public function test_search_keyword_is_applied_in_mylist_page()
    {
        $this->seed(ItemConditionsSeeder::class);

        $user = User::factory()->create();
        $item1 = Item::factory()->create(['name' => 'ノートPC']);
        $item2 = Item::factory()->create(['name' => 'マイク']);
        $item3 = Item::factory()->create(['name' => '冷蔵庫']);

        // userがノートPCとマイクにいいねしておく（マイリスト対象）
        Like::create(['user_id' => $user->id, 'item_id' => $item1->id]);
        Like::create(['user_id' => $user->id, 'item_id' => $item2->id]);

        // ステップ1: 検索状態でトップページ表示（検索状態の初期化）
        $response = $this->actingAs($user)->get('/?keyword=PC');
        $response->assertStatus(200);
        $response->assertSeeText('ノートPC');
        $response->assertDontSeeText('マイク');
        $response->assertDontSeeText('冷蔵庫');

        // ステップ2: 通常の一覧ページで検索
        $response = $this->actingAs($user)->get('/?keyword=PC');
        $response->assertStatus(200);
        $response->assertSeeText('ノートPC');
        $response->assertDontSeeText('マイク');
        $response->assertDontSeeText('冷蔵庫');
        $response->assertSee('value="PC"', false); // フォームのinputにvalueが保持されているか

        // ステップ3: マイリストページへ遷移（クエリパラメータでkeyword=PCを引き継ぐ）
        $response = $this->actingAs($user)->get('/?page=mylist&keyword=PC');
        $response->assertStatus(200);
        $response->assertSeeText('ノートPC');
        $response->assertDontSeeText('マイク');
        $response->assertDontSeeText('冷蔵庫');
        $response->assertSee('value="PC"', false); // マイリスト画面でもフォームにキーワードが表示されているか
    }
}
