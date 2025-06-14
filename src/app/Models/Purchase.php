<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'item_id',
        'purchase_price',
        'payment_method_id',
        'postal_code',
        'address',
        'building',
        'completed_at',
        'paid_at',
    ];

    // 日時としてキャストするカラム
    protected $casts = [
        'completed_at' => 'datetime',
        'paid_at'=> 'datetime',
    ];

    public function item() {
        return $this->belongsTo(Item::class);
    }

    public function paymentMethod() {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function user() {
        return $this->belongsTo(User::class, 'buyer_id');
    }
}
