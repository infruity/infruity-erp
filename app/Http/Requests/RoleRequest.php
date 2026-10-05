<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoleRequest extends FormRequest
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
            'nm_role' => ['required', 'string', 'min:4', 'max:50', 'regex:/^[a-zA-Z\s]+$/', 'unique:role,nm_role'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
