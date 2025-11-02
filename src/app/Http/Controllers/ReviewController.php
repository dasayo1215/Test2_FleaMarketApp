<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TradeRoom;
use App\Models\Review;
use App\Notifications\TradeBuyerCompletedNotification;

class ReviewController extends Controller
{
    public function store(Request $request, $roomId)
    {
        $user = Auth::user();

        $room = TradeRoom::with(['purchase', 'purchase.item', 'purchase.reviews'])->findOrFail($roomId);
        $purchase = $room->purchase;
        if (!$purchase || !$purchase->item) {
            abort(404);
        }

        // buyer / seller の取得
        $buyerId  = (int) $purchase->buyer_id;
        $sellerId = (int) $purchase->item->seller_id;

        // 当事者以外はNG
        if ($user->id !== $buyerId && $user->id !== $sellerId) {
            abort(403);
        }

        // 誰を評価するか（購入者→出品者 / 出品者→購入者）
        $rateeId = ($user->id === $buyerId) ? $sellerId : $buyerId;

        // 評価スコアの簡易バリデーション
        $score = (int) $request->input('score');
        if ($score < 1 || $score > 5) {
            abort(422, 'score must be 1..5');
        }

        // レビューを作成 or 更新
        Review::updateOrCreate(
            ['purchase_id' => $purchase->id, 'ratee_id' => $rateeId],
            ['score' => $score]
        );

        // 購入者がレビューしたとき出品者へ通知
        if ($user->id === $buyerId) {
            $buyer  = \App\Models\User::find($buyerId);
            $seller = \App\Models\User::find($sellerId);

            if ($buyer && $seller) {
                $notification = new \App\Notifications\TradeBuyerCompletedNotification($purchase, $buyer, $seller, $roomId);
                $seller->notify($notification);
            }
        }

        return redirect()->route('index', ['flash' => 'review_done']);
    }
}
