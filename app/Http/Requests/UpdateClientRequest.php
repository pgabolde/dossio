<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesClient;
use App\Models\Client;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
{
    use ValidatesClient;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->client());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->clientRules($this->client());
    }

    private function client(): Client
    {
        /** @var Client */
        return $this->route('client');
    }
}
