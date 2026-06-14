<?php

namespace App\Modules\Contacts\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MergeContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.access') ?? false;
    }

    public function rules(): array
    {
        return [
            'target_contact_id' => [
                'required',
                'uuid',
                Rule::exists('contacts', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->route('contact')?->id === $this->input('target_contact_id')) {
                    $validator->errors()->add('target_contact_id', 'El contacto destino debe ser diferente al contacto origen.');
                }
            },
        ];
    }
}
