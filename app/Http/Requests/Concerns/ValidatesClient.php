<?php

namespace App\Http\Requests\Concerns;

use App\Models\Client;
use App\Support\CurrentOrganization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ValidatesClient
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function clientRules(?Client $client = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'regex:/^\+?[0-9 .\-()]{6,20}$/'],
            'siret' => [
                'nullable',
                'digits:14',
                Rule::unique('clients', 'siret')
                    ->where('organization_id', app(CurrentOrganization::class)->id())
                    ->ignore($client),
            ],
        ];
    }
}
