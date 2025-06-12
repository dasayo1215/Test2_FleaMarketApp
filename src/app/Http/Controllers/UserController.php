<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\ProfileRequest;
use App\Http\Requests\AddressRequest;

class UserController extends Controller
{
    public function showProfile(Request $request){
        $page = $request->query('page');

        if($page === 'buy'){
            return $this->purchasedItems();
        }else{
            return $this->listedItems();
        }
    }

    public function purchasedItems(){
        // 購入した商品を表示させる(購入した順)
        $user = Auth::user();
        $items = Item::whereHas('purchase', function($query) use ($user) {
            $query->where('buyer_id', $user->id)->whereNotNull('completed_at');
        })->with('purchase')->latest()->get()
            ->sortByDesc(fn($item) => $item->purchase->completed_at)
            ->values();
        return view('users.show', compact('items', 'user'));
    }

    public function ListedItems(){
        // 出品した商品を表示させる(出品した順)
        $user = Auth::user();
        $items = Item::where('seller_id', $user->id)
        ->with('purchase')
        ->latest()
        ->get();
        return view('users.show', compact('items', 'user'));
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

    public function uploadImage(ProfileRequest $request) {
        $path = $request->file('image')->store('tmp', 'public');

        // JSONレスポンスを返す
        return response()->json([
            'success' => true,
            'image_url' => asset('storage/' . $path),
            'path' => $path,
        ]);
    }
}
