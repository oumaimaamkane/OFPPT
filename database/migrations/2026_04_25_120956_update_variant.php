<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Variant;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Variant::each(function ($variant) {
            $raw = $variant->getRawOriginal('values');

            if (empty($raw)) {
                $variant->timestamps = false;
                $variant->values = [];
                $variant->save();
                return;
            }

            // Already valid JSON array — skip
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) return;

            // Convert comma-separated string to JSON array
            $variant->timestamps = false;
            $variant->values = array_map('trim', explode(',', $raw));
            $variant->save();
        });
    }

    public function down(): void
    {
        // Convert back to comma-separated strings
        Variant::withTrashed()->each(function ($variant) {
            $values = $variant->values;

            if (is_array($values)) {
                $variant->timestamps = false;
                $variant->attributes['values'] = implode(',', $values);
                $variant->saveQuietly();
            }
        });
    }
};
