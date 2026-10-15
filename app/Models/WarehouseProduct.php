<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class WarehouseProduct extends Model
{
    use SoftDeletes , LogsActivity;
    
    protected $fillable = [
        'warehouse_id',
        'product_id',
        'quantity',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['quantity'])
            ->logOnlyDirty();
    }

    public function warehouse() 
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function allOptions()
    {
        return $this->product->allOptions;
    }
}
