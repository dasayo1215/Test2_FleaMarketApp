<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Like;
use App\Models\Comment;
use App\Models\Category;
use App\Models\ItemCondition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\ItemConditionsSeeder;
use Database\Seeders\CategoriesSeeder;

class ItemDetailTest extends TestCase
{
    use RefreshDatabase;

    // 必要な情報が表示される（商品画像、商品名、ブランド名、価格、いいね数、コメント数、商品説明、商品情報（カテゴリ、商品の状態）、コメント数、コメントしたユーザー情報、コメント内容）
    public function test_item_detail_page_displays_required_information()
    {
        $user = User::factory()->create();
        $this->seed(ItemConditionsSeeder::class);
        $this->seed(CategoriesSeeder::class);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $condition = ItemCondition::first();

        $item = Item::factory()->create([
            'name' => 'ゲーミングノートPC',
            'price' => 150000,
            'description' => '高性能なゲーミングノートPCです。',
            'image_filename' => 'sample.jpg',
            'item_condition_id' => $condition->id,
            'brand' => 'ASUS',
        ]);

        $category = Category::where('name', '家電')->first();
        $item->categories()->attach($category->id);

        // Likeを3件登録
        Like::create(['user_id' => $user1->id, 'item_id' => $item->id]);
        Like::create(['user_id' => $user2->id, 'item_id' => $item->id]);
        Like::create(['user_id' => $user3->id, 'item_id' => $item->id]);

        // コメントを1件登録
        Comment::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'comment' => 'この商品気になります！',
        ]);

        $response = $this->get("/item/{$item->id}");

        $response->assertStatus(200);

        // 商品情報のテスト
        $response->assertSeeText('ゲーミングノートPC');
        $response->assertSeeText('ASUS');
        $response->assertSeeText('￥ ' . number_format($item->price) . '（税込）');
        $response->assertSeeText('高性能なゲーミングノートPCです。');
        $response->assertSeeText($condition->name);
        $response->assertSeeText('家電');

        // 画像のテスト
        $response->assertSee('<img class="image-square"', false); // img要素とクラスの確認
        $response->assertSee('storage/items/sample.jpg', false); // 画像パスの確認
        $response->assertSee('alt="ゲーミングノートPC"', false); // alt属性の確認

        // 数値の表示テスト（HTML要素込み）
        $response->assertSee('<div class="content-like-num" id="like-count-' . $item->id . '">3</div>', false);
        $response->assertSee('<div class="content-comment-num" id="comment-icon-count">1</div>', false);
        $response->assertSee('<span id="comment-count">1</span>', false);

        // コメント内容のテスト
        $response->assertSeeText($user->name);
        $response->assertSeeText('この商品気になります！');
    }

    // 複数選択されたカテゴリが表示されているか
    public function test_item_detail_page_displays_multiple_categories()
    {
        $this->seed(ItemConditionsSeeder::class);
        $this->seed(CategoriesSeeder::class);

        $item = Item::factory()->create();

        $category1 = Category::where('name', 'ファッション')->first();
        $category2 = Category::where('name', '家電')->first();

        $item->categories()->attach([$category1->id, $category2->id]);

        $response = $this->get("/item/{$item->id}");

        $response->assertStatus(200);
        $response->assertSeeText('ファッション');
        $response->assertSeeText('家電');
    }
}
