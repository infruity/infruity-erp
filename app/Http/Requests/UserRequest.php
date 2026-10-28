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
        $isCreate = $this->isMethod('post');
        $staffIdRule = [
            'nullable',
            'integer',
            Rule::exists('staff', 'id'),
        ];
        $staffUniqueRule = Rule::unique('users', 'staff_id');
        $staffIdRule[] = $isCreate ? $staffUniqueRule : $staffUniqueRule->ignore($this->route('user'), 'id_user');

        $roleRules = [
            'role_ids' => [$this->boolean('link_only') ? 'sometimes' : 'required', 'array', 'min:1'],
            'role_ids.*' => ['required', 'integer', 'distinct', 'exists:role,id_role'],
        ];

        if (!$isCreate && $this->boolean('link_only')) {
            return array_merge([
                'link_only' => ['required', 'accepted'],
                'staff_id' => $staffIdRule,
            ], $roleRules);
        }

        return array_merge([
            'full_name' => ['required', 'string'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($isCreate ? null : $this->route('user'), 'id_user')],
            'password' => [$isCreate ? 'required' : 'nullable', 'string'],
            'id_branch' => ['required', 'array', 'min:1'],
            'id_branch.*' => ['required', 'distinct', 'exists:branch,id'],
            'staff_id' => $staffIdRule,
        ], $roleRules);
    }

    public function messages()
    {
        return [
            'email.unique' => 'Email sudah digunakan user lain, mohon untuk menggunakan email yang lain',
            'staff_id.exists' => 'Karyawan yang dipilih tidak tersedia.',
            'staff_id.unique' => 'Karyawan ini sudah tertaut ke akun lain.',
            'role_ids.required' => 'Pilih minimal satu role untuk akun.',
            'role_ids.min' => 'Pilih minimal satu role untuk akun.',
            // 'captcha' => 'captcha tidak sesuai',
            'password.required' => 'Password wajib diisi.',
            // 'password.string' => 'Password harus berupa teks.',
            // 'password.min' => 'Password harus memiliki minimal 12 karakter.',
            // 'password.regex' => 'Password harus mengandung minimal satu huruf besar, satu huruf kecil, satu angka, dan satu karakter spesial (!@#$%^&*()_+-).',
        ];
    }
}
