<?php

namespace App\Http\Controllers;

use App\Models\TradeRoom;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Review;

class TradeRoomController extends Controller
{
    public function show($roomId)
    {
        $user = \Auth::user();

        $room = TradeRoom::with([
            'purchase.user:id,name,image_filename',              // 購入者
            'purchase.item:id,name,price,seller_id,image_filename', // 商品
            'purchase.item.user:id,name,image_filename',         // 出品者
        ])->findOrFail($roomId);

        $buyer  = $room->purchase->user;
        $seller = $room->purchase->item->user;
        $item   = $room->purchase->item;

        if ($user->id !== $buyer->id && $user->id !== $seller->id) {
            abort(403);
        }

        // 自分のロールを判定
        $myRole  = $user->id === $buyer->id ? 'buyer' : 'seller';
        $partner = $myRole === 'buyer' ? $seller : $buyer;

        // 既読更新
        $col = $myRole === 'buyer' ? 'buyer_last_read_at' : 'seller_last_read_at';
        $room->forceFill([$col => now()])->save();

        // メッセージ一覧取得
        $messages = $room->messages()
            ->with('sender:id,name,image_filename')
            ->oldest()
            ->get()
            ->map(function ($m) {
                $imageUrl = $m->image_filename
                    ? Storage::disk('public')->url('trade_messages/' . $m->image_filename)
                    : null;

                return [
                    'id'         => $m->id,
                    'sender_id'  => $m->sender_id,
                    'text'       => $m->message,
                    'created_at' => optional($m->created_at)->toIso8601String(),
                    'user_name'  => optional($m->sender)->name,
                    'user_image' => optional($m->sender)->image_filename,
                    'image_url'  => $imageUrl,
                ];
            });

    // 取引中の他ルーム一覧（最新メッセージ順）
    $otherTrades = TradeRoom::with([
            'purchase:id,buyer_id,item_id,paid_at',
            'purchase.item:id,name,seller_id',
            'purchase.item.user:id,name',
        ])
        ->withMax('messages as last_msg_at', 'created_at')
        ->where('id', '!=', $roomId)
        ->whereHas('purchase', function ($q) use ($user) {
            $q->whereNotNull('paid_at')
            ->where(function ($qq) use ($user) {
                $qq->where('buyer_id', $user->id)
                    ->orWhereHas('item', fn($qi) => $qi->where('seller_id', $user->id));
            });
        })
        ->has('purchase.reviews', '<', 2) // ★ この行を追加：両者レビュー完了(=2件)は除外
        ->orderByDesc(\DB::raw('COALESCE(last_msg_at, updated_at)'))
        ->get()
        ->map(fn($r) => [
            'room_id'   => $r->id,
            'item_name' => $r->purchase->item->name,
        ]);

        // レビュー状況を算出
        $buyerReviewed = Review::where('purchase_id', $room->purchase->id)
            ->where('ratee_id', $seller->id) // 購入者→販売者を評価済みか
            ->exists();
        $sellerReviewed = Review::where('purchase_id', $room->purchase->id)
            ->where('ratee_id', $buyer->id) // 販売者→購入者を評価済みか
            ->exists();

        // 販売者で、購入者が先に評価済み & 自分は未評価なら自動表示
        $shouldPromptReview = ($myRole === 'seller') && $buyerReviewed && !$sellerReviewed;

        // 購入者側での置換判定
        $waitingForSellerReview = ($myRole === 'buyer') && $buyerReviewed && !$sellerReviewed;

        return view('trades.show', [
            'roomId'    => $room->id,
            'item'      => $item,
            'me'        => $user,
            'partner'   => $partner,
            'myRole'    => $myRole,
            'messages'  => $messages,
            'otherTrades'=> $otherTrades,
            'shouldPromptReview' => $shouldPromptReview,
            'waitingForSellerReview'  => $waitingForSellerReview,
        ]);
    }
}
