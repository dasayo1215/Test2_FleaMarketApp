<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ItemCondition;
use App\Models\Item;
use App\Models\Category;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ItemsSeeder extends Seeder
{
    public function run()
    {
        $user = User::factory()->create();  // ユーザー1人作成
        $items = $this->getItems();         // アイテム配列取得

        foreach ($items as $itemData) {
            $this->seedItem($itemData, $user); // 各アイテムの登録処理
        }
    }

    private function getItems(): array
    {
        return require database_path('data/items.php');
    }

    private function seedItem(array $itemData, User $user): void
    {
        $condition = ItemCondition::firstOrCreate(['name' => $itemData['condition']]);

        // Itemの仮保存（image_filename だけ先に仮に入れておく）
        $item = new Item();
        $item->name = $itemData['name'];
        $item->brand = 'ノーブランド';
        $item->description = $itemData['description'];
        $item->price = $itemData['price'];
        $item->item_condition_id = $condition->id;
        $item->seller_id = $user->id;
        $item->image_filename = ''; // NOT NULL 対策
        $item->save();

        // 保存後のIDを元に画像ファイル名を生成
        $filename = $item->id . '_' . now()->format('YmdHis') . '.jpg';

        try {
            $imageContents = Http::get($itemData['img_url'])->body();
            Storage::disk('public')->put('items/' . $filename, $imageContents);
        } catch (\Exception $e) {
            Log::warning('画像の取得に失敗しました: ' . $e->getMessage());
        }

        // 画像ファイル名を再保存
        $item->image_filename = $filename;
        $item->save();

        // カテゴリを関連付け
        $categories = Category::whereIn('name', $itemData['categories'])->get();
        $item->categories()->attach($categories);
    }
}