<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Purchase;
use App\Models\TradeRoom;

class StripeWebhookController extends Controller
{
    public function handlePayment(Request $request)
    {
        $payload = file_get_contents('php://input');
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sigHeader, $secret
            );
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe webhook: Invalid payload', ['error' => $e->getMessage()]);
            return response('Invalid payload', 400); // 無効なペイロード
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Stripe webhook: Signature verification failed', ['error' => $e->getMessage()]); // 署名検証に失敗
            return response('Signature verification failed', 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $purchaseId = $event->data->object->client_reference_id ?? 'null';
                Log::info("Stripe webhook: チェックアウト完了 - purchase_id: {$purchaseId}");
                break;

            case 'payment_intent.succeeded':
                $intent = $event->data->object;
                $purchaseId = $intent->metadata->purchase_id ?? null;

                if (!$purchaseId) {
                    Log::warning("payment_intent に purchase_id が含まれていません");
                    break;
                }

                $purchase = Purchase::find($purchaseId);

                if (!$purchase) {
                    Log::warning("purchase_id {$purchaseId} が見つかりません");
                    break;
                }

                if ($purchase->paid_at) {
                    Log::info("purchase_id {$purchaseId} はすでに完了済み");
                    break;
                }

                $purchase->update([
                    'paid_at' => now(),
                ]);

                Log::info("purchase_id {$purchaseId} を paid として更新");

                // 支払い確定タイミングで取引ルームを一意に作成（既にあれば取得）
                TradeRoom::firstOrCreate([
                    'purchase_id' => $purchase->id,
                ]);

                break;

            case 'payment_intent.payment_failed':
                $purchaseId = $event->data->object->metadata->purchase_id ?? 'null';
                Log::info("Stripe webhook: 支払い失敗 - purchase_id: {$purchaseId}");
                break;
        }

        return response()->json(['status' => 'ok']);
    }
}
