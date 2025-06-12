<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $names = [
            "ファッション",
            "家電",
            "インテリア",
            "レディース",
            "メンズ",
            "コスメ",
            "本",
            "ゲーム",
            "スポーツ",
            "キッチン",
            "ハンドメイド",
            "アクセサリー",
            "おもちゃ",
            "ベビー・キッズ"
        ];

        foreach ($names as $name) {
            DB::table('categories')->insert([
                'name' => $name,
            ]);
        }
    }
}
