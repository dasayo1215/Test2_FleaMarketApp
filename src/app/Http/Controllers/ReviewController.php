<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TradeRoom;
use App\Models\Review;
use App\Models\User;
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

        $buyerId  = (int) $purchase->buyer_id;
        $sellerId = (int) $purchase->item->seller_id;

        if ($user->id !== $buyerId && $user->id !== $sellerId) {
            abort(403);
        }

        $rateeId = ($user->id === $buyerId) ? $sellerId : $buyerId;

        $score = (int) $request->input('score');
        if ($score < 1 || $score > 5) {
            abort(422, 'score must be 1..5');
        }

        Review::updateOrCreate(
            ['purchase_id' => $purchase->id, 'ratee_id' => $rateeId],
            ['score' => $score]
        );

        if ($user->id === $buyerId) {
            $buyer  = User::find($buyerId);
            $seller = User::find($sellerId);

            if ($buyer && $seller) {
                $notification = new TradeBuyerCompletedNotification($purchase, $buyer, $seller, $roomId);
                $seller->notify($notification);
            }
        }

        return redirect()->route('index', ['flash' => 'review_done']);
    }
}
