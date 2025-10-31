<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'ratee_id',
        'score',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function ratee()
    {
        return $this->belongsTo(User::class, 'ratee_id');
    }
}
