<?php

namespace Modules\CORE\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionRequest extends FormRequest
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
        $permissionId = $this->route('permission')?->id ?? $this->route('permission');

        return [
            'name' => ['required', 'string', 'max:191', Rule::unique('permissions', 'name')->ignore($permissionId)],
            'guard_name' => ['required', 'string', 'max:191'],
        ];
    }
}
