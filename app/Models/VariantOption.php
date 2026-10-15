<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariantOption extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'price',
        'quantity',
    ];

  
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
