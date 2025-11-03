<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'buyer_last_read_at',
        'seller_last_read_at',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function messages()
    {
        return $this->hasMany(TradeMessage::class);
    }

    public function scopeInvolvingUser($query, int $userId)
    {
        return $query->whereHas('purchase', function ($purchaseQuery) use ($userId) {
            $purchaseQuery
                ->where('buyer_id', $userId)
                ->orWhereHas('item', function ($itemQuery) use ($userId) {
                    $itemQuery->where('seller_id', $userId);
                });
        });
    }

    public function scopeActive($query)
    {
        return $query
            ->whereHas('purchase', function ($purchaseQuery) {
                $purchaseQuery->whereNotNull('paid_at');
            })
            ->has('purchase.reviews', '<', 2);
    }
}
