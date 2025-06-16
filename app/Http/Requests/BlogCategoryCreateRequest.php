<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BlogCategoryCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|min:5|max:200|unique:blog_categories',
            'slug' => 'nullable|max:200|unique:blog_categories',
            'parent_id' => 'nullable|integer|exists:blog_categories,id',
            'description' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'title.required' => 'Введіть назву категорії',
            'title.unique' => 'Категорія з такою назвою вже існує',
            'slug.max' => 'Максимальна довжина [:max]',
            'parent_id.exists' => 'Батьківська категорія не існує',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'title' => 'Назва категорії',
            'parent_id' => 'Батьківська категорія',
        ];
    }
}
