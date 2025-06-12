<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemConditionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $names = [
            "良好",
            "目立った傷や汚れなし",
            "やや傷や汚れあり",
            "状態が悪い"
        ];

        foreach ($names as $name) {
            DB::table('item_conditions')->insert([
                'name' => $name,
            ]);
        }
    }
}
