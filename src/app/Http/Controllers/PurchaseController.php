<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\PaymentMethod;
use App\Models\Purchase;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AddressRequest;
use App\Http\Requests\PurchaseRequest;
use Stripe\Stripe;

class PurchaseController extends Controller
{
    public function showPurchaseForm(Request $request, $itemId){
        $item = Item::with('itemCondition')->findOrFail($itemId);
        $paymentMethods = PaymentMethod::all();
        $user = Auth::user();

        // セッションに保存された商品IDと異なる場合、支払い方法IDをリセット
        $prevItemId = session('selected_item_id');
        if ($prevItemId !== (int) $itemId) {
            session()->forget('selected_payment_method_id');
            session(['selected_item_id' => (int) $itemId]);
        }

        $selectedPaymentMethodId = session('selected_payment_method_id', null);

        // 既に仮保存されている購入レコードがあるか検索
        $purchase = Purchase::where('item_id', $item->id)
            ->whereNull('completed_at')
            ->first();

        if (!$purchase) {
            $data = [
                'buyer_id' => $user->id,
                'item_id' => $item->id,
                'purchase_price' => $item->price,
            ];
            if (!empty($user->postal_code)) {
                $data['postal_code'] = $user->postal_code;
            }
            if (!empty($user->address)) {
                $data['address'] = $user->address;
            }
            if (!empty($user->building)) {
                $data['building'] = $user->building;
            }
            $purchase = Purchase::create($data); // 仮保存
        }

        // 選択された支払い方法IDをビューに渡す
        $selectedPaymentMethodId = session('selected_payment_method_id', null);

        return view('purchases.create', compact(
            'item',
            'user',
            'paymentMethods',
            'purchase',
            'selectedPaymentMethodId'
        ));
    }

    public function savePaymentMethod(Request $request, $itemId) {
        $request->validate([
            'payment_method' => 'required|exists:payment_methods,id',
        ]);

        session([
            'selected_payment_method_id' => $request->payment_method,
            'selected_item_id' => (int) $itemId,
        ]);

        return response()->json(['success' => true]);
    }

    public function purchase(PurchaseRequest $request, $itemId){
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        $user = Auth::user();
        $purchase = Purchase::where('item_id', $itemId)
            ->where('buyer_id', $user->id)
            ->whereNull('completed_at')
            ->firstOrFail();
        $data = $request->validated();

        $purchase->update([
            'postal_code' => $data['postal_code'],
            'address' => $data['address'],
            'building' => $data['building'],
            'payment_method_id' => $data['payment_method'],
            'completed_at' => now(),
        ]);

        $method = PaymentMethod::find($data['payment_method']);

        $paymentMethodTypes = [];

        switch ((int) $data['payment_method']) {
            case 2:
                $paymentMethodTypes = ['card'];
                break;
            case 1:
                $paymentMethodTypes = ['konbini'];
                break;
            default:
                abort(400, '対応していない支払い方法です。');
        }

        $sessionData = [
            'metadata' => [
                'purchase_id' => $purchase->id,
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'purchase_id' => $purchase->id,
                ],
            ],
            'client_reference_id' => $purchase->id,
            'payment_method_types' => $paymentMethodTypes,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'jpy',
                    'product_data' => [
                        'name' => $purchase->item->name
                    ],
                    'unit_amount' => $purchase->item->price,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('payment.success') . '?purchase_id=' . $purchase->id,
            'cancel_url' => route('payment.cancel'),
        ];

        if ($method->name === 'コンビニ支払い') {
            $sessionData['billing_address_collection'] = 'required';
        }

        $session = \Stripe\Checkout\Session::create($sessionData);

        session()->forget(['selected_payment_method_id', 'selected_item_id']);

        return redirect($session->url);
    }

    public function showAddressForm($itemId){
        $purchase = Purchase::where('item_id', $itemId)
            ->whereNull('completed_at')
            ->firstOrFail();

        return view('purchases.address', compact('purchase', 'itemId'));
    }

    public function updateAddress(AddressRequest $request, $itemId){
        $data = $request->validated();

        $purchase = Purchase::where('item_id', $itemId)
            ->whereNull('completed_at')
            ->firstOrFail();

        $purchase->postal_code = $data['postal_code'];
        $purchase->address = $data['address'];
        $purchase->building = $data['building'];
        $purchase->save();

        return redirect()->route('purchase.show', $purchase->item_id);
    }

    public function showSuccess() {
        return view('purchases.checkout')->with('message', '支払いが成功しました。');
    }

    public function handleCancel() {
        return view('purchases.checkout')->with('message', '支払いがキャンセルされました。');
    }
}

