<?php

namespace Modules\Crm\Support;

final class ExpSettingsRules
{
    public static function rules(): array
    {
        $finite = static function ($attribute, $value, $fail, $validator): void {
            $data = $validator->getData();
            if (! is_finite((float) $value)
                || (is_numeric($data['skala'] ?? null) && is_numeric($data['value'] ?? null)
                    && ! is_finite((float) $data['skala'] * (float) $data['value']))) {
                $fail('Perhitungan EXP harus berupa angka terbatas.');
            }
        };

        return [
            'skala' => ['bail', 'required', 'numeric', 'gt:0', $finite],
            'value' => ['bail', 'required', 'numeric', 'min:0', $finite],
        ];
    }
}
