<?php

namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
            'link_only' => ['required', 'accepted'],
            'staff_id' => [
                'present',
                'nullable',
                'integer',
                Rule::exists('staff', 'id'),
                Rule::unique('users', 'staff_id')->ignore($this->route('user'), 'id_user'),
            ],
        ];
    }

    public function messages()
    {
        return [
            'staff_id.exists' => 'Karyawan yang dipilih tidak tersedia.',
            'staff_id.unique' => 'Karyawan ini sudah tertaut ke akun lain.',
        ];
    }
}
