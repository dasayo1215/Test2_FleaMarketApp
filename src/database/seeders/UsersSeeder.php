<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'ユーザー1',
            'email' => 'user1@example.com',
            'password' => Hash::make('user1234'),
            'postal_code' => '1000001',
            'address' => '東京都千代田区千代田1-1',
            'building' => '皇居ビル101',
            'image_filename' => 'user1.png',
            'email_verified_at' => Carbon::now(),
        ]);

        User::create([
            'name' => 'ユーザー2',
            'email' => 'user2@example.com',
            'password' => Hash::make('user1234'),
            'postal_code' => '1500001',
            'address' => '東京都渋谷区神宮前1-1-1',
            'building' => '渋谷タワー502',
            'image_filename' => 'user2.png',
            'email_verified_at' => Carbon::now(),
        ]);

        User::create([
            'name' => 'ユーザー3',
            'email' => 'user3@example.com',
            'password' => Hash::make('user1234'),
            'postal_code' => '5300001',
            'address' => '大阪府大阪市北区梅田1-1-1',
            'building' => '梅田スカイビル801',
            'image_filename' => 'user3.png',
            'email_verified_at' => Carbon::now(),
        ]);
    }
}
