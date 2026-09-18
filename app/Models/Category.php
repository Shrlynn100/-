<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'icon',
        'total_items',
        'sort_order',
    ];

    public function items()
    {
        return $this->hasMany(AuctionItem::class)->orderBy('slot_number');
    }

    public function getFilledCountAttribute()
    {
        return $this->items()->whereNotNull('name')->where('name', '!=', '')->count();
    }
}
