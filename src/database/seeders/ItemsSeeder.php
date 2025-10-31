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
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ItemsSeeder extends Seeder
{
    public function run()
    {
        // ユーザー1,2をメールで取得（無ければ作成）
        $seller1 = User::firstOrCreate(
            ['email' => 'user1@example.com'],
            [
                'name' => 'ユーザー1',
                'password' => Hash::make('user1234'),
                'postal_code' => '1000001',
                'address' => '東京都千代田区千代田1-1',
                'building' => '皇居ビル101',
                'image_filename' => 'user1.png',
                'email_verified_at' => Carbon::now(),
            ]
        );

        $seller2 = User::firstOrCreate(
            ['email' => 'user2@example.com'],
            [
                'name' => 'ユーザー2',
                'password' => Hash::make('user1234'),
                'postal_code' => '1500001',
                'address' => '東京都渋谷区神宮前1-1-1',
                'building' => '渋谷タワー502',
                'image_filename' => 'user2.png',
                'email_verified_at' => Carbon::now(),
            ]
        );

        // items.php での user_id=1/2 を実IDへマッピング
        $idMap = [
            1 => $seller1->id,
            2 => $seller2->id,
        ];

        $items = $this->getItems();

        foreach ($items as $itemData) {
            $sellerId = $idMap[$itemData['user_id']];
            $this->seedItem($itemData, $sellerId);
        }
    }

    private function getItems(): array
    {
        return require database_path('data/items.php');
    }

    private function seedItem(array $itemData, int $sellerId): void
    {
        $condition = ItemCondition::firstOrCreate(['name' => $itemData['condition']]);

        // Itemの仮保存（image_filename だけ先に仮に入れておく）
        $item = new Item();
        $item->name = $itemData['name'];
        $item->brand = 'ノーブランド';
        $item->description = $itemData['description'];
        $item->price = $itemData['price'];
        $item->item_condition_id = $condition->id;
        $item->seller_id = $sellerId;
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