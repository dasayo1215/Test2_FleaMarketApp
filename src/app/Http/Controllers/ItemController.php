<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExhibitionRequest;
use App\Http\Requests\CommentRequest;
use App\Http\Requests\UploadImageRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\ItemCondition;
use App\Models\Item;
use App\Models\Like;
use App\Models\Comment;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    public function index(Request $request) {
        $page = $request->query('page');
        $keyword = $request->query('keyword');

        if($page === 'mylist'){
            return $this->mylist($request);
        }

        $query = Item::with('purchase');

        // ログインしている場合は自分が出品した商品を除外
        if (Auth::check()) {
            $user = Auth::user();
            $query->where('seller_id', '!=', $user->id);
        }

        if(!empty($keyword)){
            $query->where('name', 'like', '%' . $keyword . '%');
        }

        $items = $query->latest()->get();

        return view('items.index', compact('items', 'keyword', 'page'));
    }

    public function mylist(Request $request) {
        $items = [];
        $keyword = $request->query('keyword');

        if (Auth::check()) {
            $user = Auth::user();

            $items = $user->likedItems()
                ->where('seller_id', '!=', $user->id)
                ->when($keyword, function ($query, $keyword) {
                    $query->where('name', 'like', '%' . $keyword . '%');
                })
                ->orderBy('pivot_created_at', 'desc') // いいね順に並び替え
                ->get();
        }

        $page='mylist';

        return view('items.index', compact('items', 'keyword', 'page'));
    }

    public function show($itemId){
        $item = Item::with(['categories', 'itemCondition', 'purchase'])->findOrFail($itemId);
        $user = Auth::user();
        return view('items.show', compact('item', 'user'));
    }

    public function storeComment(CommentRequest $request, $itemId){
        $request->validated();

        $comment = Comment::create([
            'user_id' => Auth::id(),
            'item_id' => $itemId,
            'comment' => $request->comment,
        ]);

        // ユーザー情報も一緒に返す（画像・名前のため）
        $comment->load('user');

        return response()->json([
            'success' => true,
            'comment' => [
                'user_name' => $comment->user->name,
                'user_image' => $comment->user->image_filename,
                'text' => $comment->comment,
            ],
        ]);
    }

    public function toggleLike(Request $request, $itemId){
        $user = Auth::user();
        $item = Item::findOrFail($itemId);
        $like = Like::where('user_id', $user->id)
                    ->where('item_id', $itemId)
                    ->first();

        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            Like::create([
                'user_id' => $user->id,
                'item_id' => $itemId,
            ]);
            $liked = true;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'liked' => $liked,
                'like_count' => $item->likes()->count(),
            ]);
        }

        return back();
    }

    public function showSellForm(){
        $categories = Category::all();
        $conditions = ItemCondition::all();
        return view('items.create', compact('categories', 'conditions'));
    }

    public function store(ExhibitionRequest $request)
    {
        $validated = $request->validated();
        $sellerId = auth()->id();

        // 新しい Item インスタンス生成
        $item = new Item();
        $item->name = $validated['name'];
        $item->brand = $validated['brand'] ?? null;
        $item->description = $validated['description'];
        $item->price = str_replace(',', '', $validated['price']);
        $item->item_condition_id = $validated['item_condition_id'];
        $item->seller_id = $sellerId;

        // 仮に空で保存（画像ファイル名にIDを使いたいため）
        $item->image_filename = '';
        $item->save();

        // 画像の一時パスから正式ファイル名に変更して移動
        $tmpPath = $validated['sell_uploaded_image_path'];
        if ($tmpPath && \Storage::disk('public')->exists($tmpPath)) {
            $extension = pathinfo($tmpPath, PATHINFO_EXTENSION);
            $filename = $item->id . '_' . now()->format('YmdHis') . '.' . $extension;
            \Storage::disk('public')->move($tmpPath, 'items/' . $filename);
            $item->image_filename = $filename;
            $item->save();
        }

        $item->categories()->sync($validated['category_id']);

        return redirect('/mypage');
    }

    public function uploadImage(UploadImageRequest $request) {
        $path = $request->file('image')->store('tmp', 'public');

        return response()->json([
            'success' => true,
            'path' => $path,
            'image_url' => asset('storage/' . $path),
        ]);
    }
}