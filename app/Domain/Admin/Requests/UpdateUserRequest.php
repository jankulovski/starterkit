<?php

namespace App\Domain\Admin\Requests;

use App\Domain\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'is_admin' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->route('user');
            $isAdmin = $this->boolean('is_admin', $user->is_admin);

            // Prevent demoting the last admin
            if ($user->is_admin && ! $isAdmin) {
                $adminCount = User::where('is_admin', true)->count();

                if ($adminCount <= 1) {
                    $validator->errors()->add(
                        'is_admin',
                        'Cannot demote the last remaining admin user.'
                    );
                }
            }
        });
    }
}

