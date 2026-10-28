<?php

namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Master\Entities\Branch;
use Modules\Master\Entities\Staff;

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
            'integer',
            Rule::exists('staff', 'id'),
            Rule::unique('users', 'staff_id')->ignore($isCreate ? null : $this->route('user'), 'id_user'),
        ];

        if (!$isCreate) {
            array_unshift($staffIdRule, 'present', 'nullable');

            return [
                'link_only' => ['required', 'accepted'],
                'staff_id' => $staffIdRule,
            ];
        }

        array_unshift($staffIdRule, 'required');

        return [
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => ['required', 'string'],
            'staff_id' => $staffIdRule,
        ];
    }

    public function withValidator($validator): void
    {
        if (!$this->isMethod('post')) {
            return;
        }

        $validator->after(function ($validator) {
            if ($validator->errors()->has('staff_id') || !$this->filled('staff_id')) {
                return;
            }

            $staff = Staff::query()->withCount('pendingRoles')->find($this->input('staff_id'));
            if (!$staff) {
                return;
            }

            if (!$staff->branch_id || !Branch::query()->whereKey($staff->branch_id)->exists()) {
                $validator->errors()->add('staff_id', 'Pilih karyawan yang cabangnya sudah diatur.');
            }
            if ((int) $staff->pending_roles_count < 1) {
                $validator->errors()->add('staff_id', 'Pilih karyawan yang Hak Akses-nya sudah diatur.');
            }
        });
    }

    public function messages()
    {
        return [
            'email.unique' => 'Email sudah digunakan user lain, mohon untuk menggunakan email yang lain',
            'staff_id.required' => 'Pilih karyawan untuk akun ini.',
            'staff_id.exists' => 'Karyawan yang dipilih tidak tersedia.',
            'staff_id.unique' => 'Karyawan ini sudah tertaut ke akun lain.',
            // 'captcha' => 'captcha tidak sesuai',
            'password.required' => 'Password wajib diisi.',
            // 'password.string' => 'Password harus berupa teks.',
            // 'password.min' => 'Password harus memiliki minimal 12 karakter.',
            // 'password.regex' => 'Password harus mengandung minimal satu huruf besar, satu huruf kecil, satu angka, dan satu karakter spesial (!@#$%^&*()_+-).',
        ];
    }
}
