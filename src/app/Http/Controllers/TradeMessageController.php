<?php

namespace App\Http\Controllers;

use App\Models\TradeRoom;
use App\Models\TradeMessage;
use App\Http\Requests\StoreTradeMessageRequest;
use App\Http\Requests\UploadImageRequest;
use Illuminate\Support\Facades\Storage;

class TradeMessageController extends Controller
{
    public function store(StoreTradeMessageRequest $request, $roomId)
    {
        $room = TradeRoom::with('purchase.item')->findOrFail($roomId);

        // 参加者検証（出品者 or 購入者のみ）
        $user = $request->user();
        if (!in_array($user->id, [$room->purchase->buyer_id, $room->purchase->item->seller_id])) {
            abort(403, 'Not allowed.');
        }

        // 画像があれば保存（storage/app/public/trade_messages 配下）
        $filename = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('trade_messages', 'public');
            $filename = basename($path); // DBにはファイル名のみを保存
        }

        $tm = TradeMessage::create([
            'trade_room_id' => $room->id,
            'sender_id'     => $user->id,
            'message'       => $request->message,
            'image_filename' => $filename,
        ]);

        return response()->json([
            'success' => true,
            'message' => [
                'id'         => $tm->id,
                'sender_id'  => $tm->sender_id,
                'text'       => $tm->message,
                'image_url'  => $filename ? asset('storage/trade_messages/' . $filename) : null,
                'created_at' => $tm->created_at->toDateTimeString(),
                'user_name'  => $user->name,
                'user_image' => $user->image_filename,
            ],
        ]);
    }

    public function uploadImage(UploadImageRequest $request)
    {
        return response()->json(['ok' => true]);
    }

    public function update(StoreTradeMessageRequest $request, $roomId, $messageId)
    {
        $room = TradeRoom::with('purchase.item')->findOrFail($roomId);

        $user = $request->user();
        if (!in_array($user->id, [$room->purchase->buyer_id, $room->purchase->item->seller_id])) {
            abort(403, 'Not allowed.');
        }

        $msg = TradeMessage::where('id', $messageId)
            ->where('trade_room_id', $room->id)
            ->firstOrFail();

        if ($msg->sender_id !== $user->id) {
            abort(403, 'You can edit only your messages.');
        }

        $msg->message = $request->input('message', '');
        $msg->save();

        return response()->json([
            'success' => true,
            'message' => [
                'id'   => $msg->id,
                'text' => $msg->message,
            ],
        ]);
    }

    public function destroy(\Illuminate\Http\Request $request, $roomId, $messageId)
    {
        $room = TradeRoom::with('purchase.item')->findOrFail($roomId);

        $user = $request->user();
        if (!in_array($user->id, [$room->purchase->buyer_id, $room->purchase->item->seller_id])) {
            abort(403, 'Not allowed.');
        }

        $msg = TradeMessage::where('id', $messageId)
            ->where('trade_room_id', $room->id)
            ->firstOrFail();

        if ($msg->sender_id !== $user->id) {
            abort(403, 'You can delete only your messages.');
        }

        if ($msg->image_filename) {
            Storage::disk('public')->delete('trade_messages/'.$msg->image_filename);
        }

        $msg->delete();

        return response()->json(['success' => true]);
    }
}
