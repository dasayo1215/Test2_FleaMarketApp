<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Item;
use App\Models\Review;
use App\Models\TradeRoom;
use App\Http\Requests\ProfileRequest;
use App\Http\Requests\AddressRequest;

class UserController extends Controller
{
    public function showProfile(Request $request)
    {
        $page = $request->query('page');
        $resp = $page === 'buy' ? $this->showPurchasedItems()
            : ($page === 'trade' ? $this->showTradingItems() : $this->showListedItems());
        return $resp->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function showPurchasedItems()
    {
        $user = Auth::user();
        $items = Item::whereHas('purchase', fn($q) => $q->where('buyer_id', $user->id)->whereNotNull('completed_at'))
            ->with('purchase')->get()
            ->sortByDesc(fn($it) => $it->purchase->completed_at)->values();

        return $this->render($items, $user);
    }

    public function showListedItems()
    {
        $user = Auth::user();
        $items = Item::where('seller_id', $user->id)->with('purchase')->latest()->get();

        return $this->render($items, $user);
    }

    public function showTradingItems()
    {
        $user = Auth::user();

        $rooms = TradeRoom::query()
            ->involvingUser($user->id)
            ->active()
            ->with([
                'purchase:id,buyer_id,item_id,paid_at',
                'purchase.item:id,name,price,seller_id,image_filename',
            ])
            ->withMax('messages as last_msg_at', 'created_at')
            ->get();

        // メッセージ無しは最古扱いでソート
        $rooms = $rooms->sortByDesc(fn($r) => $r->last_msg_at ?? '1970-01-01 00:00:00')->values();

        $items = $rooms
            ->map(fn($r) => optional($r->purchase)->item)
            ->filter()
            ->values();

        return $this->render($items, $user);
    }

    private function render($items, $user)
    {
        $unreadByRoom = $this->buildUnreadMap($user->id);
        $totalUnread  = $unreadByRoom->sum();
        ['ratingAvgRounded' => $ratingAvgRounded, 'ratingCount' => $ratingCount] = $this->aggregateRating($user->id);

        return response()->view('users.show', compact('items', 'user', 'unreadByRoom', 'totalUnread', 'ratingAvgRounded', 'ratingCount'));
    }

    private function buildUnreadMap(int $userId)
    {
        [$buyerRoomIds, $sellerRoomIds] = $this->activeRoomIds($userId);
        if (empty($buyerRoomIds) && empty($sellerRoomIds)) return collect();

        $buyer = collect();
        if (!empty($buyerRoomIds)) {
            $buyer = DB::table('trade_messages as m')->join('trade_rooms as r', 'r.id', '=', 'm.trade_room_id')
                ->whereIn('m.trade_room_id', $buyerRoomIds)->where('m.sender_id', '!=', $userId)
                ->whereRaw("m.created_at > COALESCE(r.buyer_last_read_at,'1970-01-01 00:00:00')")
                ->select('m.trade_room_id', DB::raw('COUNT(*) as cnt'))
                ->groupBy('m.trade_room_id')->pluck('cnt', 'm.trade_room_id');
        }

        $seller = collect();
        if (!empty($sellerRoomIds)) {
            $seller = DB::table('trade_messages as m')->join('trade_rooms as r', 'r.id', '=', 'm.trade_room_id')
                ->whereIn('m.trade_room_id', $sellerRoomIds)->where('m.sender_id', '!=', $userId)
                ->whereRaw("m.created_at > COALESCE(r.seller_last_read_at,'1970-01-01 00:00:00')")
                ->select('m.trade_room_id', DB::raw('COUNT(*) as cnt'))
                ->groupBy('m.trade_room_id')->pluck('cnt', 'm.trade_room_id');
        }

        return $buyer->union($seller);
    }

    /**
     * アクティブなルームの buyer/seller をスコープで一発取得 → 仕分け
     */
    private function activeRoomIds(int $userId): array
    {
        $rooms = TradeRoom::query()
            ->involvingUser($userId)
            ->active()
            ->with([
                'purchase:id,buyer_id,item_id',
                'purchase.item:id,seller_id',
            ])
            ->get(['id', 'purchase_id']);

        $buyerRoomIds  = $rooms->filter(fn($r) => $r->purchase && $r->purchase->buyer_id === $userId)
            ->pluck('id')->all();

        $sellerRoomIds = $rooms->filter(fn($r) => $r->purchase && $r->purchase->item && $r->purchase->item->seller_id === $userId)
            ->pluck('id')->all();

        return [$buyerRoomIds, $sellerRoomIds];
    }

    public function editProfile()
    {
        $user = Auth::user();

        return view('users.edit', compact('user'));
    }

    public function updateProfile(AddressRequest $request)
    {
        $data = $request->validated();
        $user = Auth::user();
        $path = $request->input('profile_uploaded_image_path');

        if ($path) {
            if ($user->image_filename) Storage::disk('public')->delete('users/' . $user->image_filename);
            $filename = $user->id . '_' . time() . '.' . pathinfo($path, PATHINFO_EXTENSION);
            Storage::disk('public')->move($path, 'users/' . $filename);
            $data['image_filename'] = $filename;
        }

        $user->update($data);

        return redirect('/');
    }

    public function uploadProfileImage(ProfileRequest $request)
    {
        $path = $request->file('image')->store('tmp', 'public');

        return response()->json(['success' => true, 'image_url' => asset('storage/' . $path), 'path' => $path]);
    }

    private function aggregateRating(int $userId): array
    {
        $count = Review::where('ratee_id', $userId)->count();
        if ($count === 0) return ['ratingAvgRounded' => null, 'ratingCount' => 0];
        $rounded = (int) round(Review::where('ratee_id', $userId)->avg('score'));
        $rounded = max(1, min(5, $rounded));

        return ['ratingAvgRounded' => $rounded, 'ratingCount' => $count];
    }
}
