<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryCompany extends Model
{
    protected $fillable = [
        'name',
        'token',
        'api_id'
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
