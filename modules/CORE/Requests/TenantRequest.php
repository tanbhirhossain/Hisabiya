<?php

namespace Modules\CORE\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = $this->route('tenant')?->id ?? $this->route('tenant');

        return [
            'name' => ['required', 'string', 'max:191'],
            'slug' => ['nullable', 'string', 'max:191', 'alpha_dash', Rule::unique('tenants', 'slug')->ignore($tenantId)],
            'email' => ['nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:500'],
            'currency' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'max:60'],
            'status' => ['required', 'string', Rule::in(['active', 'trial', 'suspended'])],
            'plan' => ['required', 'string', Rule::in(['free', 'starter', 'pro', 'enterprise'])],
            'trial_ends_at' => ['nullable', 'date'],
        ];
    }
}
