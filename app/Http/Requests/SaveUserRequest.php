<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SaveUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('user') ? $this->user()->can('update', $this->route('user')) : $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:100'], 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->route('user'))], 'password' => [$this->route('user') ? 'nullable' : 'required', 'confirmed', Password::min(12)], 'role_id' => [$this->user()->isAdmin() ? 'required' : 'prohibited', Rule::exists('roles', 'id')], 'is_active' => ['required', 'boolean']];
    }
}
