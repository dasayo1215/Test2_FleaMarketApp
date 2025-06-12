<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'image_filename',
        'name',
        'brand',
        'price',
        'description',
    ];

    public function categories(){
        return $this->belongsToMany(Category::class);
    }

    public function itemCondition() {
        return $this->belongsTo(ItemCondition::class);
    }

    public function purchase() {
        return $this->hasOne(Purchase::class);
    }

    public function likes() {
        return $this->hasMany(Like::class);
    }

    public function isLikedBy($user) {
        return $this->likes->where('user_id', $user->id)->isNotEmpty();
    }

    public function comments() {
        return $this->hasMany(Comment::class);
    }
}


