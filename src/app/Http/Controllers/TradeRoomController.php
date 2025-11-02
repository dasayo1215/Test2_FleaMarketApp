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
        $user = Auth::user();
        $room = $this->findRoomOrFail($roomId);

        [$buyer, $seller, $item] = [$room->purchase->user, $room->purchase->item->user, $room->purchase->item];
        if ($user->id !== $buyer->id && $user->id !== $seller->id) abort(403);

        $myRole  = $user->id === $buyer->id ? 'buyer' : 'seller';
        $partner = $myRole === 'buyer' ? $seller : $buyer;

        $this->markAsRead($room, $myRole);

        $messages    = $this->fetchMessages($room);
        $otherTrades = $this->fetchOtherTrades($user, $roomId);
        [$shouldPromptReview, $waitingForSellerReview] =
            $this->computeReviewFlags($room, $buyer->id, $seller->id, $myRole);

        return view('trades.show', [
            'roomId'                 => $room->id,
            'item'                   => $item,
            'me'                     => $user,
            'partner'                => $partner,
            'myRole'                 => $myRole,
            'messages'               => $messages,
            'otherTrades'            => $otherTrades,
            'shouldPromptReview'     => $shouldPromptReview,
            'waitingForSellerReview' => $waitingForSellerReview,
        ]);
    }

    // 取引内容取得
    private function findRoomOrFail(int $roomId): TradeRoom
    {
        return TradeRoom::with([
            'purchase.user:id,name,image_filename',
            'purchase.item:id,name,price,seller_id,image_filename',
            'purchase.item.user:id,name,image_filename',
        ])->findOrFail($roomId);
    }

    // 既読処理
    private function markAsRead(TradeRoom $room, string $myRole): void
    {
        $col = $myRole === 'buyer' ? 'buyer_last_read_at' : 'seller_last_read_at';
        $room->forceFill([$col => now()])->save();
    }

    // メッセージを取得
    private function fetchMessages(TradeRoom $room)
    {
        return $room->messages()
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
    }

    // 他の取引を取得
    private function fetchOtherTrades($user, int $roomId)
    {
        return TradeRoom::with([
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
            ->has('purchase.reviews', '<', 2)
            ->orderByDesc(DB::raw("COALESCE(last_msg_at, '1970-01-01 00:00:00')"))
            ->get()
            ->map(fn($r) => [
                'room_id'   => $r->id,
                'item_name' => $r->purchase->item->name,
            ]);
    }

    // 評価状況確認
    private function computeReviewFlags(TradeRoom $room, int $buyerId, int $sellerId, string $myRole): array
    {
        $purchaseId    = $room->purchase->id;
        $buyerReviewed = Review::where('purchase_id', $purchaseId)
            ->where('ratee_id', $sellerId)->exists();
        $sellerReviewed = Review::where('purchase_id', $purchaseId)
            ->where('ratee_id', $buyerId)->exists();

        $shouldPromptReview     = ($myRole === 'seller') && $buyerReviewed && !$sellerReviewed;
        $waitingForSellerReview = ($myRole === 'buyer')  && $buyerReviewed && !$sellerReviewed;

        return [$shouldPromptReview, $waitingForSellerReview];
    }
}
