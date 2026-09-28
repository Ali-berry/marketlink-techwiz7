<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:2000'],
            'audience' => ['required', Rule::in(['everyone', 'customers', 'farmers'])],
            'action' => ['required', Rule::in(['draft', 'save', 'publish', 'unpublish'])],
        ];
    }
}
