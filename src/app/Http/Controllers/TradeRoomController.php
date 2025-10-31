<?php

namespace App\Http\Controllers;

use App\Models\TradeRoom;
use Illuminate\Support\Facades\Auth;

class TradeRoomController extends Controller
{
    public function show($roomId)
    {
        $user = \Auth::user();

        $room = TradeRoom::with([
            'purchase.user:id,name,image_filename',  // 購入者
            'purchase.item:id,name,price,seller_id,image_filename',  // 商品
            'purchase.item.user:id,name,image_filename',  // 出品者
        ])->findOrFail($roomId);

        $buyer  = $room->purchase->user;
        $seller = $room->purchase->item->user;
        $item   = $room->purchase->item;

        if ($user->id !== $buyer->id && $user->id !== $seller->id) {
            abort(403);
        }

        $myRole = $user->id === $buyer->id ? 'buyer' : 'seller';
        $partner = $myRole === 'buyer' ? $seller : $buyer;

        $messages = $room->messages()
            ->with('sender:id,name,image_filename')
            ->oldest()
            ->get()
            ->map(function ($m) {
                return [
                    'id'         => $m->id,
                    'sender_id'  => $m->sender_id,
                    'text'       => $m->message,
                    'created_at' => optional($m->created_at)->toIso8601String(),
                    'user_name'  => optional($m->sender)->name,
                    'user_image' => optional($m->sender)->image_filename,
                ];
            });

        return view('trades.show', [
            'roomId'  => $room->id,
            'item'    => $item,
            'me'      => $user,
            'partner' => $partner,
            'myRole'  => $myRole,
            'messages' => $messages,
        ]);
    }
}
