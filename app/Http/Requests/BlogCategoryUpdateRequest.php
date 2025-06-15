<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BlogCategoryUpdateRequest extends FormRequest  // ✅ ПРАВИЛЬНО!
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|min:5|max:200',
            'slug' => 'max:200',
            'parent_id' => 'nullable|integer|exists:blog_categories,id',
            'description' => 'nullable|string|max:500',
        ];
    }
}
