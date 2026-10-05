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

        return [
            'full_name' => ['required', 'string'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($isCreate ? null : $this->route('user'), 'id_user')],
            'password' => [$isCreate ? 'required' : 'nullable', 'string'],
            'id_role' => ['required', 'exists:role,id_role'],
            'id_branch' => ['required', 'array', 'min:1'],
            'id_branch.*' => ['required', 'distinct', 'exists:branch,id'],
        ];
    }

    public function messages()
    {
        return [
            'email.unique' => 'Email sudah digunakan user lain, mohon untuk menggunakan email yang lain',
            // 'captcha' => 'captcha tidak sesuai',
            'password.required' => 'Password wajib diisi.',
            // 'password.string' => 'Password harus berupa teks.',
            // 'password.min' => 'Password harus memiliki minimal 12 karakter.',
            // 'password.regex' => 'Password harus mengandung minimal satu huruf besar, satu huruf kecil, satu angka, dan satu karakter spesial (!@#$%^&*()_+-).',
        ];
    }
}
