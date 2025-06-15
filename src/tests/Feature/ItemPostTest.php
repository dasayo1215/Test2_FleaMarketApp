<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\ItemCondition;
use App\Models\Category;
use Database\Seeders\ItemConditionsSeeder;
use Database\Seeders\CategoriesSeeder;

class ItemPostTest extends TestCase
{
    use RefreshDatabase;

    // 商品出品画面にて必要な情報が保存できること（カテゴリ、商品の状態、商品名、商品の説明、販売価格）
    public function test_item_is_saved_correctly_with_required_information()
    {
        $this->seed(ItemConditionsSeeder::class);
        $this->seed(CategoriesSeeder::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        // ここで条件とカテゴリを取得
        $condition = ItemCondition::first();
        $category = Category::first();

        Storage::fake('public');

        // 空のファイルを作成
        $fakeImage = UploadedFile::fake()->create('test.jpg', 100);

        // 先にStorageにファイルを置く
        Storage::disk('public')->putFileAs('tmp', $fakeImage, $fakeImage->hashName());

        $response = $this->post(route('store'), [
            'name' => 'Test Item',
            'brand' => 'Test Brand',
            'description' => 'Test description text',
            'price' => '1,234',
            'item_condition_id' => $condition->id,
            'category_id' => [$category->id],
            'sell_uploaded_image_path' => 'tmp/' . $fakeImage->hashName(),
        ]);

        $response->assertRedirect('/mypage');

        $this->assertDatabaseHas('items', [
            'name' => 'Test Item',
            'brand' => 'Test Brand',
            'description' => 'Test description text',
            'price' => 1234,
            'item_condition_id' => $condition->id,
            'seller_id' => $user->id,
        ]);

        $item = Item::where('name', 'Test Item')->first();
        $this->assertNotNull($item);
        $this->assertTrue($item->categories->contains($category->id));
        $this->assertNotEmpty($item->image_filename);

        Storage::disk('public')->assertExists('items/' . $item->image_filename);
    }
}
