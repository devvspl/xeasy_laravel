<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmailTemplateRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->hasPermissionTo('Create Email Template');
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'is_active' => 'required|boolean',
            'category' => 'nullable|string|max:50'
        ];
    }
}
