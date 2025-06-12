<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadImageRequest extends FormRequest
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
            'image' => ['image', 'mimes:jpeg,png'],
        ];
    }

    public function messages() {
        return [
            'image.image' => '拡張子が.jpegもしくは.pngの商品画像を選択してください',
            'image.mimes' => '拡張子が.jpegもしくは.pngの商品画像を選択してください',
        ];
    }
}
