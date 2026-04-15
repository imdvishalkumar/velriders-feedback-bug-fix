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
        Schema::create('car_host_banks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('car_hosts_id');
            $table->string('account_holder_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('city')->nullable();
            $table->string('account_no');
            $table->string('ifsc_code');
            $table->string('nick_name')->nullable();
            $table->string('passbook_image')->nullable();
            $table->tinyInteger('is_primary')->nullable()->comment('1 = Primary, 2 = Not Primary');
            $table->tinyInteger('is_deleted')->nullable()->comment('0 = Not Deleted, 1 = Deleted');
            $table->timestamps();

            //Define Foraign Keys
            $table->foreign('car_hosts_id')->references('id')->on('car_hosts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_host_banks');
    }
};
