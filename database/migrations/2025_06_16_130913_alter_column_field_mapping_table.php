<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterColumnFieldMappingTable extends Migration
{
    public function up()
    {
        Schema::table('column_field_mapping', function (Blueprint $table) {
            // Drop existing columns that we will replace
            $table->dropColumn(['field_name', 'map_with']);
            
            // Add new columns for mapping details
            $table->string('temp_column')->after('claim_id');
            $table->string('input_type')->after('temp_column');
            $table->string('select_table')->nullable()->after('input_type');
            $table->string('search_column')->nullable()->after('select_table');
            $table->string('return_column')->nullable()->after('search_column');
            $table->string('punch_table')->after('return_column');
            $table->string('punch_column')->after('punch_table');
            $table->string('condition')->nullable()->after('punch_column');
            
            // Add created_by and updated_by columns
            $table->unsignedBigInteger('created_by')->nullable()->after('condition');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            
            // Add foreign key constraints if users table exists
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('column_field_mapping', function (Blueprint $table) {
            // Drop new columns
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn([
                'temp_column',
                'input_type',
                'select_table',
                'search_column',
                'return_column',
                'punch_table',
                'punch_column',
                'condition',
                'created_by',
                'updated_by'
            ]);
            
            // Recreate dropped columns
            $table->string('field_name', 255)->after('claim_id');
            $table->string('map_with', 255)->after('field_name');
        });
    }
}