<?php

namespace App\Http\Requests;

use App\Rules\PublicHost;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CloudUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nextcloud_url' => ['url:http,https', new PublicHost],
            'nextcloud_username' => 'string',
            'nextcloud_password' => 'string',
        ];
    }
}
