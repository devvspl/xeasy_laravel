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
        Schema::create('tbl_theme_customizer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->nullable();
            $table->string('layout')->default('vertical')->index();
            $table->boolean('sidebar_user_profile')->default(false);
            $table->string('theme')->default('default');
            $table->string('color_scheme')->default('light');
            $table->string('sidebar_visibility')->default('show');
            $table->string('layout_width')->default('fluid');
            $table->string('layout_position')->default('fixed');
            $table->string('topbar_color')->default('light');
            $table->string('sidebar_size')->default('lg');
            $table->string('sidebar_view')->default('default');
            $table->string('sidebar_color')->default('light');
            $table->string('sidebar_image')->default('none');
            $table->string('primary_color')->default('default');
            $table->string('preloader')->default('disable');
            $table->string('body_image')->default('none');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_theme_customizer');
    }
};