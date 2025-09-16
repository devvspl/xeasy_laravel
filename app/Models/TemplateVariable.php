<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TemplateVariable extends Model
{
    protected $table = 'eml_template_variables';
    protected $fillable = ['template_id', 'variable_name', 'description'];
}