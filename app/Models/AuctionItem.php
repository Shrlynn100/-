<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuctionItem extends Model
{
    protected $fillable = [
        'category_id',
        'slot_number',
        'page_number',
        'name',
        'book1',
        'book2',
        'book3',
        'book4',
        'winner_name',
        'final_price',
        'notes',
        'item_date',
    ];

    protected $casts = [
        'slot_number' => 'integer',
        'page_number' => 'integer',
        'book1' => 'boolean',
        'book2' => 'boolean',
        'book3' => 'boolean',
        'book4' => 'boolean',
        'final_price' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
