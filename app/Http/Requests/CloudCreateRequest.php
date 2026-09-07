<?php

namespace App\Http\Requests;

use App\Rules\PublicHost;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CloudCreateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nextcloud_url' => ['required', 'url:http,https', new PublicHost],
            'nextcloud_username' => 'required|string',
            'nextcloud_password' => 'required|string',
        ];
    }
}
