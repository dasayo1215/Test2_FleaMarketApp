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

    public function scopeInvolvingUser($q, int $userId)
    {
        return $q->whereHas('purchase', function ($qq) use ($userId) {
            $qq->where('buyer_id', $userId)
            ->orWhereHas('item', fn($qi) => $qi->where('seller_id', $userId));
        });
    }

    public function scopeActive($q)
    {
        return $q->whereHas('purchase', fn($p) => $p->whereNotNull('paid_at'))
                ->has('purchase.reviews','<',2);
    }
}
