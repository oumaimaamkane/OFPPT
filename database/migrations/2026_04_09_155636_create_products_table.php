<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
           $table->id();
            $table->string('name');
            $table->text('barcode')->unique();
            $table->string('sku');
            $table->longText('description')->nullable();
            $table->unsignedBigInteger('warehouse_qty')->default(0);
            $table->decimal('price', 10, 2)->nullable();
            $table->enum('status' , ['new','available' , 'out of stock' , 'preorder' ])->default('new');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
