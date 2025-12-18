<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'section',
        'size',
        'stock',
        'dimension',
        'reorder_level',
        'items_per_sleve',
        'rate',
        'value',
    ];
}
