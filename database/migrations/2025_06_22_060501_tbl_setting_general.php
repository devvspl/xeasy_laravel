<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tbl_setting_general', function (Blueprint $table) {
            $table->id();
            $table->string('project_name');
            $table->string('time_zone');
            $table->string('default_language');
            $table->boolean('maintenance_mode')->default(false);
            $table->string('site_url')->nullable();
            $table->text('contact_info')->nullable();
            $table->text('site_description')->nullable();
            $table->string('logo_path')->nullable();

            // You must define the columns before setting foreign keys
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Foreign key constraints
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps(); // For created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_setting_general');
    }
};
