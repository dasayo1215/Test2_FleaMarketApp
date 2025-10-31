<?php

namespace App\Http\Controllers;

use App\Models\TradeRoom;
use App\Models\TradeMessage;
use Illuminate\Http\Request;

class TradeMessageController extends Controller
{
    public function store(Request $request, $roomId)
    {
        // ※後で変える！！！！
        $request->validate([
            'message' => ['required','string','max:2000'],
        ]);

        $room = TradeRoom::with('purchase.item')->findOrFail($roomId);

        // 参加者検証（出品者 or 購入者のみ）
        $user = $request->user();
        if (!in_array($user->id, [$room->purchase->buyer_id, $room->purchase->item->seller_id])) {
            abort(403, 'Not allowed.');
        }

        $tm = TradeMessage::create([
            'trade_room_id' => $room->id,
            'sender_id'     => $user->id,
            'message'       => $request->message,
        ]);

        return response()->json([
            'success' => true,
            'message' => [
                'id'         => $tm->id,
                'sender_id'  => $tm->sender_id,
                'text'       => $tm->message,
                'created_at' => $tm->created_at->toDateTimeString(),
                'user_name'  => $user->name,
                'user_image' => $user->image_filename,
            ],
        ]);
    }
}
