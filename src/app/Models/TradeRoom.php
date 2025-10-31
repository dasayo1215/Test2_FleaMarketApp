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
}
