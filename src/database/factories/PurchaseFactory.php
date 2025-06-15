<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\User;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    public function definition()
    {
        return [
            'item_id' => Item::factory(),
            'buyer_id' => User::factory(),
            'purchase_price' => $this->faker->numberBetween(1000, 10000),
            'payment_method_id' => 1, // 適当なID、またはnullでもOK
            'postal_code' => $this->faker->postcode(),
            'address' => $this->faker->address(),
            'building' => $this->faker->secondaryAddress(),
            'completed_at' => now(), // 「Sold」表示
            'paid_at' => now(),
        ];
    }

    public function notCompleted()
    {
        return $this->state([
            'completed_at' => null,
        ]);
    }
}
