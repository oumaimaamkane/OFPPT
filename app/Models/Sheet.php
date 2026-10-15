<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sheet extends Model
{
    protected $fillable = [
        'user_id',
        'spreadsheet_id',
        'sheet_name',
        'header_row',
        'last_imported_row',
        'last_synced_at',
        'column_mapping',
        'is_active',
        'brand_name',
    ];

    protected $casts = [
        'column_mapping' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
