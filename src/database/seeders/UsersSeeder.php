<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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
        /**
         * 開発用シーディング：1000人のダミーユーザーを生成します。
         * 負荷テストやUIスケール検証などに使えます。
         */
        User::factory()->count(1000)->create();
    }
}
