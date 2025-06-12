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
        return [
            [
                'name' => '腕時計',
                'price' => 15000,
                'description' => 'スタイリッシュなデザインのメンズ腕時計',
                'img_url' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Armani+Mens+Clock.jpg',
                'condition' => '良好',
                'categories' => ['ファッション'],
            ],
            [
                'name' => 'HDD',
                'price' => 5000,
                'description' => '高速で信頼性の高いハードディスク',
                'img_url' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/HDD+Hard+Disk.jpg',
                'condition' => '目立った傷や汚れなし',
                'categories' => ['家電'],
            ],
            [
                'name' => '玉ねぎ3束',
                'price' => 300,
                'description' => '新鮮な玉ねぎ3束のセット',
                'img_url' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/iLoveIMG+d.jpg',
                'condition' => 'やや傷や汚れあり',
                'categories' => ['キッチン'],
            ],
            [
                'name' => '革靴',
                'price' => 4000,
                'description' => 'クラシックなデザインの革靴',
                'img_url' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Leather+Shoes+Product+Photo.jpg',
                'condition' => '状態が悪い',
                'categories' => ['ファッション', 'メンズ'],
            ],
            [
                'name' => 'ノートPC',
                'price' => 45000,
                'description' => '高性能なノートパソコン',
                'img_url' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Living+Room+Laptop.jpg',
                'condition' => '良好',
                'categories' => ['家電'],
            ],
            [
                'name' => 'マイク',
                'price' => 8000,
                'description' => '高音質のレコーディング用マイク',
                'img_url' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Music+Mic+4632231.jpg',
                'condition' => '目立った傷や汚れなし',
                'categories' => ['家電'],
            ],
            [
                'name' => 'ショルダーバッグ',
                'price' => 3500,
                'description' => 'おしゃれなショルダーバッグ',
                'img_url' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Purse+fashion+pocket.jpg',
                'condition' => 'やや傷や汚れあり',
                'categories' => ['ファッション', 'レディース'],
            ],
            [
                'name' => 'タンブラー',
                'price' => 500,
                'description' => '使いやすいタンブラー',
                'img_url' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Tumbler+souvenir.jpg',
                'condition' => '状態が悪い',
                'categories' => ['キッチン'],
            ],
            [
                'name' => 'コーヒーミル',
                'price' => 4000,
                'description' => '手動のコーヒーミル',
                'img_url' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Waitress+with+Coffee+Grinder.jpg',
                'condition' => '良好',
                'categories' => ['キッチン'],
            ],
            [
                'name' => 'メイクセット',
                'price' => 2500,
                'description' => '便利なメイクアップセット',
                'img_url' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/%E5%A4%96%E5%87%BA%E3%83%A1%E3%82%A4%E3%82%AF%E3%82%A2%E3%83%83%E3%83%95%E3%82%9A%E3%82%BB%E3%83%83%E3%83%88.jpg',
                'condition' => '目立った傷や汚れなし',
                'categories' => ['コスメ', 'レディース'],
            ],
        ];
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