<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloudUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nextcloud_url' => 'url',
            'nextcloud_username' => 'string',
            'nextcloud_password' => 'string',
        ];
    }
}
