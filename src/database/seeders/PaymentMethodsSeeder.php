<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('payment_methods')->insert([
            ['id' => 1, 'name' => 'コンビニ払い'],
            ['id' => 2, 'name' => 'カード支払い'],
        ]);
    }
}
