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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id('vehicle_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            // $table->unsignedBigInteger('type_id')->nullable()->after('category_id');
            // $table->unsignedBigInteger('manufacturer_id')->nullable()->after('type_id');

            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger('category_id');
            $table->integer('year');
            $table->text('description')->nullable();
            $table->string('color', 255)->nullable();
            $table->string('license_plate', 255)->nullable();
            $table->tinyInteger('availability');
            $table->integer('host_step_count')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->decimal('rental_price', 10, 2)->nullable();
            $table->decimal('extra_km_rate', 10, 2)->nullable();
            $table->decimal('extra_hour_rate', 10, 2)->nullable();
            $table->json('availability_calendar')->nullable();
            $table->tinyInteger('commission_percent', 3)->default(0)->nullable();
            $table->string('chassis_no')->nullable();
            $table->tinyInteger('publish')->default(1)->comment('0 - Unpublished, 1 - Published');
            $table->tinyInteger('vehicle_created_by')->default(1)->comment('1 - Admin, 2 = Car Host');
            $table->string('nick_name')->default(null);
            $table->tinyInteger('apply_for_publish')->default(0)->comment('0 - Unpublished, 1 - Apply For Publish');
            $table->unsignedBigInteger('temp_city_id')->nullable();
            $table->decimal('deposit_amount', 10, 2)->nullable();
            $table->tinyInteger('is_deposit_amount_show')->default(0)->comment('0 - Disabled, 1 - Enable');
            $table->tinyInteger('step_cnt')->default(0);
            $table->unsignedBigInteger('updated_temp_city_id')->nullable();
            $table->unsignedBigInteger('updated_model_id')->nullable();
            $table->integer('updated_year')->nullable();
            $table->tinyInteger('is_host_updated')->default(0)->comment('0 - Host has not updated any vehicle details, 1 - Host has updated vehicle details');
            $table->decimal('updated_extra_km_rate', 10, 2)->nullable();
            $table->decimal('updated_deposit_amount', 10, 2)->nullable();
            $table->tinyInteger('updated_is_deposit_amount_show')->nullable()->comment('0 - Disabled, 1 - Enable');
            $table->timestamps();

             // Define foreign key constraints
            // $table->foreign('type_id')->references('type_id')->on('vehicle_types')->onDelete('cascade');
            // $table->foreign('manufacturer_id')->references('manufacturer_id')->on('vehicle_manufacturers')->onDelete('cascade');

            // Define foreign key constraints
            $table->foreign('branch_id')->references('branch_id')->on('branches')->onDelete('cascade');
            $table->foreign('model_id')->references('model_id')->on('vehicle_models')->onDelete('cascade');
            $table->foreign('category_id')->references('category_id')->on('vehicle_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
