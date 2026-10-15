<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class orderProducts extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'variant_option_id',
        'quantity',
        'product_url'
        // 'price',
        // 'total',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class , 'product_id');
    }

    public function option()
    {
        return $this->belongsTo(VariantOption::class , 'variant_option_id');
    }
    
}
