<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Form;

final class UserForm extends Form
{
    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public string $departmentId = '';

    public ?int $originalDepartmentId = null;

    public bool $isActive = true;

    public string $password = '';

    public string $passwordConfirmation = '';

    /** @return array<string, list<mixed>> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email:rfc',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($this->userId),
            ],
            'departmentId' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where(function (Builder $query): void {
                    $query->where('is_active', true)
                        ->when(
                            $this->originalDepartmentId !== null,
                            fn (Builder $query): Builder => $query->orWhere('id', $this->originalDepartmentId),
                        );
                }),
            ],
            'isActive' => ['boolean'],
            'password' => [
                $this->userId === null ? 'required' : 'nullable',
                'string',
                Password::default(),
            ],
            'passwordConfirmation' => [
                $this->password === '' ? 'nullable' : 'required',
                'same:password',
            ],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'name' => 'họ và tên',
            'email' => 'email',
            'departmentId' => 'phòng ban',
            'isActive' => 'trạng thái',
            'password' => 'mật khẩu',
            'passwordConfirmation' => 'xác nhận mật khẩu',
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập họ và tên.',
            'name.max' => 'Họ và tên không được vượt quá 255 ký tự.',
            'email.required' => 'Vui lòng nhập email.',
            'email.lowercase' => 'Email phải được viết bằng chữ thường.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã được sử dụng.',
            'departmentId.integer' => 'Phòng ban không hợp lệ.',
            'departmentId.exists' => 'Phòng ban không tồn tại hoặc đã ngừng hoạt động.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'passwordConfirmation.required' => 'Vui lòng xác nhận mật khẩu.',
            'passwordConfirmation.same' => 'Xác nhận mật khẩu không khớp.',
        ];
    }

    /** @return array{name: string, email: string, department_id: ?int, is_active: bool, password?: string} */
    public function validatedPayload(): array
    {
        $this->name = trim($this->name);
        $this->email = Str::lower(trim($this->email));
        $validated = $this->validate();
        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'department_id' => $validated['departmentId'] === '' ? null : (int) $validated['departmentId'],
            'is_active' => (bool) $validated['isActive'],
        ];

        if ($validated['password'] !== '') {
            $payload['password'] = $validated['password'];
        }

        return $payload;
    }

    public function fillFrom(User $user): void
    {
        $this->userId = $user->getKey();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->departmentId = $user->department_id === null ? '' : (string) $user->department_id;
        $this->originalDepartmentId = $user->department_id;
        $this->isActive = $user->is_active;
        $this->password = '';
        $this->passwordConfirmation = '';
    }

    public function clear(): void
    {
        $this->reset();
        $this->isActive = true;
    }
}
