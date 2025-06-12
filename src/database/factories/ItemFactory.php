<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ItemCondition;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word(),
            'brand' => $this->faker->company(),
            'price' => $this->faker->numberBetween(1000, 10000),
            'description' => $this->faker->sentence(),
            'image_filename' => 'default.jpg',
            'seller_id' => \App\Models\User::factory(),
            'item_condition_id' => ItemCondition::inRandomOrder()->first()->id,
        ];
    }
}
