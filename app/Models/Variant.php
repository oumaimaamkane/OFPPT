<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'values',
    ];

   protected $casts = [
    'values' => 'array',
    ];

    public function setValuesAttribute($value)
    {
        if (is_string($value)) {
            // Convert comma-separated string to array, then let cast encode it as JSON
            $this->attributes['values'] = json_encode(explode(',', $value));
        } else {
            $this->attributes['values'] = json_encode($value);
        }
    }

    public function options()
    {
        return $this->hasMany(VariantOption::class);
    }
}
