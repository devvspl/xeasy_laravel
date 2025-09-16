<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create email_templates table
        Schema::create('eml_email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('subject', 255);
            $table->mediumText('body_html');
            $table->mediumText('body_text');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->boolean('is_active')->default(true);
            $table->string('category', 50)->nullable();
        });

        // Create template_variables table
        Schema::create('eml_template_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('eml_email_templates')->onDelete('cascade');
            $table->string('variable_name', 100);
            $table->string('description', 255);
        });

        // Create sent_emails table
        Schema::create('eml_sent_emails', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('template_id')->constrained('eml_email_templates')->onDelete('set null');
            $table->string('recipient_email', 255);
            $table->string('sender_email', 255);
            $table->string('subject', 255);
            $table->longText('body_html');
            $table->longText('body_text');
            $table->timestamp('sent_at')->useCurrent();
            $table->string('status', 20);
            $table->text('error_message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eml_sent_emails');
        Schema::dropIfExists('eml_template_variables');
        Schema::dropIfExists('eml_email_templates');
    }
}