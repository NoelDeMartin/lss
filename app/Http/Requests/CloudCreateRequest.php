<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloudCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nextcloud_url' => 'required|url',
            'nextcloud_username' => 'required|string',
            'nextcloud_password' => 'required|string',
        ];
    }
}
