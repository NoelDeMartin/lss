<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'client_name' => 'required|string',
            'redirect_uris' => 'required|array|min:1',
            'redirect_uris.*' => 'url',
        ];
    }
}
