<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use SoftDeletes , InteractsWithMedia;
    protected $fillable = [
        'name',
        'barcode',
        'sku',
        'description',
        'warehouse_qty',
        'price',
        'status',
    ];

    protected $casts = [
        'warehouse_qty' => 'integer',
        'price' => 'decimal:2',
        'status' => 'string',
        
    ];

    public function variants()
    {
        return $this->hasMany(Variant::class);
    }
    public function allOptions()
    {
        return $this->hasMany(VariantOption::class , 'product_id');
    }

    public function warehouseProducts()
    {
        return $this->hasMany(WarehouseProduct::class);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'like', "%{$term}%")
            ->orWhere('sku', 'like', "%{$term}%")
            ->orWhere('barcode', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%");
    }

    public function scopeFilterByStatus($query, $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        return $query;
    }

    public function scopeFilterByWarehouseQty($query, $minQty, $maxQty)
    {
        if ($minQty !== null && $maxQty !== null) {
            return $query->whereBetween('warehouse_qty', [$minQty, $maxQty]);
        }
        return $query;
    }

    public function scopeFilterByPrice($query, $minPrice, $maxPrice)
    {
        if ($minPrice !== null && $maxPrice !== null) {
            return $query->whereBetween('price', [$minPrice, $maxPrice]);
        }
        return $query;
    }

    public function scopeFilterByDate($query, $startDate, $endDate)
    {
        if ($startDate && $endDate) {
            return $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        return $query;
    }

    public function scopeSortBy($query, $column, $direction = 'asc')
    {
        return $query->orderBy($column, $direction);
    }
}
