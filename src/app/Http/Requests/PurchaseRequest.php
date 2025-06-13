<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
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
            'payment_method' => ['required', 'integer'],
            'postal_code' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'building' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages() {
        return [
            'payment_method.required' => '支払い方法を入力してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->missingAddressFields()) {
                $validator->errors()->add('address_group', '配送先を適切に入力してください');
            }
        });
    }

    protected function missingAddressFields()
    {
        return !$this->filled('postal_code') || !$this->filled('address') || !$this->filled('building');
    }
}
