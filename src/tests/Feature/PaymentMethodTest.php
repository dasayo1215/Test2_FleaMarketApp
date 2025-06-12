<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\PaymentMethod;
use Database\Seeders\ItemConditionsSeeder;
use Database\Seeders\PaymentMethodsSeeder;
use Symfony\Component\DomCrawler\Crawler;

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_payment_method_is_reflected_in_purchase_form()
    {
        $this->seed(ItemConditionsSeeder::class);
        $this->seed(PaymentMethodsSeeder::class);

        $user = User::factory()->create();
        $item = Item::factory()->create();

        $this->actingAs($user);

        $this->post("/purchase/{$item->id}/payment-method", [
            'payment_method' => 2, // カード支払い
        ])->assertJson(['success' => true]);

        $response = $this->get("/purchase/{$item->id}");
        $response->assertStatus(200);

        // DOM Crawler を使って HTML を構造的に解析
        $crawler = new Crawler($response->getContent());

        // 小計画面の「カード支払い」を検証
        $this->assertSame(
            'カード支払い',
            $crawler->filter('.purchase-method-display')->text()
        );

        // セレクトボックスで選択されている option のテキストを検証
        $this->assertSame(
            'カード支払い',
            $crawler->filter('#payment-method option[selected]')->text()
        );
    }
}
