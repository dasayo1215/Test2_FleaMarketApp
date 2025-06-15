<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 開発用シーディング：100人のダミーユーザーを生成します。
        User::factory()->count(100)->create();
    }
}
