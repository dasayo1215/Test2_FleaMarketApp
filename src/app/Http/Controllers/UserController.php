<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Item;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\ProfileRequest;
use App\Http\Requests\AddressRequest;

class UserController extends Controller
{
    public function showProfile(Request $request){
        $page = $request->query('page');

        if ($page === 'buy') {
            $resp = $this->showPurchasedItems();
        } elseif ($page === 'trade') {
            $resp = $this->showTradingItems();
        } else {
            $resp = $this->showListedItems();
        }

        // 戻るボタンを押した際に既読を反映（no-cache）
        return $resp->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
        ]);
    }

    public function showPurchasedItems(){
        $user = Auth::user();

        $items = Item::whereHas('purchase', function($query) use ($user) {
                $query->where('buyer_id', $user->id)
                        ->whereNotNull('completed_at');
            })
            ->with('purchase')
            ->get()
            ->sortByDesc(fn($item) => $item->purchase->completed_at)
            ->values();

        $unreadByRoom = $this->buildUnreadMap($user->id);
        $totalUnread  = $unreadByRoom->sum();

        ['ratingAvgRounded' => $ratingAvgRounded, 'ratingCount' => $ratingCount]
            = $this->aggregateRating($user->id);

        return response()->view('users.show', compact(
            'items', 'user', 'unreadByRoom', 'totalUnread', 'ratingAvgRounded', 'ratingCount'
        ));
    }

    public function showListedItems(){
        $user = Auth::user();

        $items = Item::where('seller_id', $user->id)
            ->with('purchase')
            ->latest()
            ->get();

        $unreadByRoom = $this->buildUnreadMap($user->id);
        $totalUnread  = $unreadByRoom->sum();

        ['ratingAvgRounded' => $ratingAvgRounded, 'ratingCount' => $ratingCount]
            = $this->aggregateRating($user->id);

        return response()->view('users.show', compact(
            'items', 'user', 'unreadByRoom', 'totalUnread', 'ratingAvgRounded', 'ratingCount'
        ));
    }

    public function showTradingItems()
    {
        $user = Auth::user();

        // 取引中の商品を取得
        $buyerRoomIds  = $this->activeBuyerRoomIds($user->id);
        $sellerRoomIds = $this->activeSellerRoomIds($user->id);
        $activeRoomIds = array_values(array_unique(array_merge($buyerRoomIds, $sellerRoomIds)));

        $items = Item::query()
            ->where(function ($q) use ($user) {
                $q->where('seller_id', $user->id)
                    ->orWhereHas('purchase', fn($qq) => $qq->where('buyer_id', $user->id));
            })
            ->whereHas('purchase', fn($q) => $q->whereNotNull('paid_at'))
            ->has('purchase.reviews', '<', 2)  // 両者レビュー完了(=2件)は除外
            ->with([
                'purchase:id,buyer_id,item_id,paid_at',
                'purchase.tradeRoom:id,purchase_id',
            ])
            ->get();

        // 未読マップ（共通）
        $unreadByRoom = $this->buildUnreadMap($user->id);
        $totalUnread  = $unreadByRoom->sum();

        // 並び替え用（最終メッセージ時刻）
        $lastMessageAtByRoom = empty($activeRoomIds)
            ? collect()
            : DB::table('trade_messages')
                ->select('trade_room_id', DB::raw('MAX(created_at) as last_message_at'))
                ->whereIn('trade_room_id', $activeRoomIds)
                ->groupBy('trade_room_id')
                ->pluck('last_message_at', 'trade_room_id');

        $items = $items->sortByDesc(function ($item) use ($lastMessageAtByRoom) {
            $roomId = optional(optional($item->purchase)->tradeRoom)->id;
            return $lastMessageAtByRoom[$roomId] ?? '1970-01-01 00:00:00';
        })->values();

        ['ratingAvgRounded' => $ratingAvgRounded, 'ratingCount' => $ratingCount]
            = $this->aggregateRating($user->id);

        return response()->view('users.show', compact(
            'items', 'user', 'unreadByRoom', 'totalUnread', 'ratingAvgRounded', 'ratingCount'
        ));
    }

    // ユーザーが関与する取引ルームごとの未読数を返す（paid後の取引のみ）
    private function buildUnreadMap(int $userId)
    {
        // アクティブルームID（買い手/売り手）を共通ヘルパーで取得
        $buyerRoomIds  = $this->activeBuyerRoomIds($userId);
        $sellerRoomIds = $this->activeSellerRoomIds($userId);

        if (empty($buyerRoomIds) && empty($sellerRoomIds)) {
            return collect();
        }

        $unreadBuyer = collect();
        if (!empty($buyerRoomIds)) {
            $unreadBuyer = DB::table('trade_messages as m')
                ->join('trade_rooms as r', 'r.id', '=', 'm.trade_room_id')
                ->whereIn('m.trade_room_id', $buyerRoomIds)
                ->where('m.sender_id', '!=', $userId)
                ->whereRaw("m.created_at > COALESCE(r.buyer_last_read_at, '1970-01-01 00:00:00')")
                ->select('m.trade_room_id', DB::raw('COUNT(*) as cnt'))
                ->groupBy('m.trade_room_id')
                ->pluck('cnt', 'm.trade_room_id');
        }

        $unreadSeller = collect();
        if (!empty($sellerRoomIds)) {
            $unreadSeller = DB::table('trade_messages as m')
                ->join('trade_rooms as r', 'r.id', '=', 'm.trade_room_id')
                ->whereIn('m.trade_room_id', $sellerRoomIds)
                ->where('m.sender_id', '!=', $userId)
                ->whereRaw("m.created_at > COALESCE(r.seller_last_read_at, '1970-01-01 00:00:00')")
                ->select('m.trade_room_id', DB::raw('COUNT(*) as cnt'))
                ->groupBy('m.trade_room_id')
                ->pluck('cnt', 'm.trade_room_id');
        }

        return $unreadBuyer->union($unreadSeller);
    }

    // 「買い手として」アクティブ（paid済み かつ レビュー<2）のルームID
    private function activeBuyerRoomIds(int $userId): array
    {
        return DB::table('trade_rooms as r')
            ->join('purchases as p', 'p.id', '=', 'r.purchase_id')
            ->where('p.buyer_id', $userId)
            ->whereNotNull('p.paid_at')
            ->whereRaw('(select count(*) from reviews rv where rv.purchase_id = p.id) < 2')
            ->pluck('r.id')
            ->all();
    }

    // 「売り手として」アクティブ（paid済み かつ レビュー<2）のルームID
    private function activeSellerRoomIds(int $userId): array
    {
        return DB::table('trade_rooms as r')
            ->join('purchases as p', 'p.id', '=', 'r.purchase_id')
            ->join('items as i', 'i.id', '=', 'p.item_id')
            ->where('i.seller_id', $userId)
            ->whereNotNull('p.paid_at')
            ->whereRaw('(select count(*) from reviews rv where rv.purchase_id = p.id) < 2')
            ->pluck('r.id')
            ->all();
    }

    public function editProfile(){
        $user = Auth::user();
        return view('users.edit', compact('user'));
    }

    public function updateProfile(AddressRequest $request){
        $data = $request->validated();
        $user = Auth::user();

        $path = $request->input('profile_uploaded_image_path');

        if ($path) {
            // 古い画像を削除
            if ($user->image_filename) {
                Storage::disk('public')->delete('users/' . $user->image_filename);
            }

            $filename = $user->id . '_' . time() . '.' . pathinfo($path, PATHINFO_EXTENSION);
            Storage::disk('public')->move($path, 'users/' . $filename);
            $data['image_filename'] = $filename;
        }

        $user->update($data);

        return redirect('/');
    }

    public function uploadProfileImage(ProfileRequest $request) {
        $path = $request->file('image')->store('tmp', 'public');

        return response()->json([
            'success' => true,
            'image_url' => asset('storage/' . $path),
            'path' => $path,
        ]);
    }

    // ratee(＝自分)が受け取ったレビューの平均(四捨五入)と件数
    private function aggregateRating(int $userId): array
    {
        $q = Review::where('ratee_id', $userId);

        $count = (clone $q)->count();
        if ($count === 0) {
            return ['ratingAvgRounded' => null, 'ratingCount' => 0];
        }

        $avg = (clone $q)->avg('score');           // float
        $rounded = (int) round($avg);              // 四捨五入 1～5 の整数
        $rounded = max(1, min(5, $rounded));       // 念のためガード

        return ['ratingAvgRounded' => $rounded, 'ratingCount' => $count];
    }
}
