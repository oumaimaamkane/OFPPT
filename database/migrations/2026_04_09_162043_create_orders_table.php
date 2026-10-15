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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('status_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('delivery_company_id')->nullable();
             $table->unsignedBigInteger('store_id')->nullable();
             $table->unsignedBigInteger('delivery_status_id')->nullable();
            $table->string('name');
            $table->string('phone');
            $table->string('city');
            $table->string('delivery_city');
            $table->string('address')->nullable();
            $table->string('price');
            $table->string('quantity');
            // $table->string('total');
            $table->text('comment')->nullable();
            $table->text('whatsapp_link')->nullable();
            $table->integer('delivery_city');
            $table->string('tracking_number')->nullable();
            $table->decimal('delivery_fee' , 10 , 2)->default(0);
            $table->string('sync_status')->nullable();
            $table->string('sync_message')->nullable();
            $table->enum('replace' , ['yes' , 'no'])->default('no');
            $table->enum('open' , ['yes' , 'no'])->default('no');
            $table->enum('try' , ['yes' , 'no'])->default('no');
            $table->string('brand_name')->nullable();
            $table->unsignedBigInteger('assign_to')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('delivery_company_id')->references('id')->on('delivery_companies')->onDelete('cascade');
            $table->foreign('delivery_status_id')->references('id')->on('statuses')->onDelete('cascade');
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            $table->foreign('assign_to')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
