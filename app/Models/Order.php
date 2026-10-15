<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'phone',
        'delivery_city',
        'city',
        'address',
        'price',
        'quantity',
        'comment',
        'status_id',
        'delivery_company_id',
        'tracking_number',
        'delivery_fee',
        'store_id',
        'delivery_status_id',
        'whatsapp_link',
        'sync_status',
        'sync_message',
        'assign_to',
        'open',
        'replace',
        'try',
        'brand_name'

    ];
    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = app(\App\Jobs\ImportOrdersFromSheet::class)
            ->normalizePhone($value);
        $this->attributes['whatsapp_link'] = app(\App\Jobs\ImportOrdersFromSheet::class)
            ->createWtspLink($this->phone, 'Bonjour');
    }

    public function products()
    {
        return $this->hasMany(orderProducts::class);
    }
    
    public function status()
    {
        return $this->belongsTo(Status::class);
    }
    public function delivery_company()
    {
        return $this->belongsTo(DeliveryCompany::class);
    }

    public function delivery_status()
    {
        return $this->belongsTo(Status::class);
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    

    
}
