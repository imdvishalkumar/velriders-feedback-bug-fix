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
        Schema::create('login_tokens', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('app')->default(null)->comment('1 = Velrider, 2 = Velrider Host');
            $table->unsignedBigInteger('customer_id');
            $table->string('token', 500);
            $table->timestamps();

            // Define foreign key constraints
            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade')->name('login_tokens_customer_id_customers_foreign');
            $table->foreign('customer_id')->references('id')->on('car_hosts')->onDelete('cascade')->name('login_tokens_customer_id_car_hosts_foreign');
           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_tokens');
    }
};
