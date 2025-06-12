<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\ItemConditionsSeeder;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(ItemConditionsSeeder::class);
    }

    // ログイン済みのユーザーはコメントを送信できる
    public function test_logged_in_user_can_submit_comment()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $this->actingAs($user);

        $commentText = 'これはテストコメントです。';

        $initialCount = $item->comments()->count();

        $response = $this->postJson(route('comment', $item->id), [
            'comment' => $commentText,
        ]);

        $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'comment' => [
                'text' => $commentText,
                'user_name' => $user->name,
                'user_image' => $user->image_filename,
            ],
        ]);

        // コメント一覧などコメント数表示のHTMLを取得して確認
        $htmlResponse = $this->get("/item/{$item->id}");
        $htmlResponse->assertStatus(200)
            ->assertSee('<div class="content-comment-num" id="comment-icon-count">' . ($initialCount + 1) . '</div>', false)
            ->assertSee('<span id="comment-count">' . ($initialCount + 1) . '</span>', false);

        // DB上のコメント数もちゃんと増えているか検証
        $this->assertEquals($initialCount + 1, $item->fresh()->comments()->count());
    }

    // ログイン前のユーザーはコメントを送信できない
    public function test_guest_cannot_submit_comment()
    {
        $item = Item::factory()->create();

        $commentText = 'ゲストユーザーのコメント';

        $response = $this->postJson(route('comment', $item->id), [
            'comment' => $commentText,
        ]);

        // 401 Unauthorized
        $response->assertStatus(401);

        $this->assertDatabaseMissing('comments', [
            'comment' => $commentText,
        ]);
    }

    // コメントが入力されていない場合、バリデーションメッセージが表示される
    public function test_comment_validation_required()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $this->actingAs($user);

        $response = $this->postJson(route('comment', $item->id), [
            'comment' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['comment']);
    }

    // コメントが255字以上の場合、バリデーションメッセージが表示される
    public function test_comment_validation_max_length()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $this->actingAs($user);

        $longComment = str_repeat('あ', 256); // 256文字のコメント

        $response = $this->postJson(route('comment', $item->id), [
            'comment' => $longComment,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['comment']);
    }
}
