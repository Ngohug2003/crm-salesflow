<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Department;
use App\Models\Staff;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final class StaffService
{
    /**
     * Generate automatic staff code format: NV-[DEPT_CODE]-[STT]
     */
    public function generateStaffCode(?int $departmentId): string
    {
        $deptCode = 'GEN';
        if ($departmentId !== null) {
            $dept = Department::query()->find($departmentId);
            if ($dept !== null) {
                $code = strtoupper(trim($dept->code ?: $dept->name));
                $deptCode = match ($code) {
                    'MANAGEMENT', 'BAN GIÁM ĐỐC', 'QUẢN TRỊ' => 'MGT',
                    'SALES', 'PHÒNG BÁN HÀNG' => 'SALES',
                    'MARKETING' => 'MKT',
                    'IT', 'CÔNG NGHỆ THÔNG TIN' => 'IT',
                    default => substr(preg_replace('/[^A-Z0-9]/', '', $code) ?: 'GEN', 0, 6),
                };
            }
        }

        $count = Staff::query()
            ->where('department_id', $departmentId)
            ->withTrashed()
            ->count() + 1;

        return sprintf('NV-%s-%04d', $deptCode, $count);
    }

    /**
     * Create or update a Staff profile record.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveStaff(array $data, ?int $staffId = null): Staff
    {
        $staffCode = $data['staff_code'] ?? null;
        if (empty($staffCode)) {
            $departmentId = isset($data['department_id']) ? (int) $data['department_id'] : null;
            $staffCode = $this->generateStaffCode($departmentId);
        }

        $staffData = [
            'staff_code' => $staffCode,
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'birthday' => ! empty($data['birthday']) ? Carbon::parse($data['birthday']) : null,
            'address' => $data['address'] ?? null,
            'province_id' => $data['province_id'] ?? null,
            'ward_id' => $data['ward_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'position' => $data['position'] ?? null,
            'join_date' => ! empty($data['join_date']) ? Carbon::parse($data['join_date']) : null,
            'is_active' => $data['is_active'] ?? true,
            'notes' => $data['notes'] ?? null,
        ];

        // Link with existing User account if email matches
        $existingUser = User::query()->where('email', $data['email'])->first();
        if ($existingUser !== null) {
            $staffData['user_id'] = $existingUser->id;
        }

        return Staff::query()->updateOrCreate(
            ['id' => $staffId],
            $staffData
        );
    }

    /**
     * Invite a staff member via email: creates UserInvitation and Staff profile record.
     */
    public function inviteStaff(string $name, string $email, ?int $departmentId, string $role, User $inviter): Staff
    {
        UserInvitation::query()->create([
            'email' => $email,
            'name' => $name,
            'department_id' => $departmentId,
            'role' => $role,
            'token' => Str::random(40),
            'expires_at' => now()->addDays(7),
            'invited_by' => $inviter->id,
        ]);

        return $this->saveStaff([
            'full_name' => $name,
            'email' => $email,
            'department_id' => $departmentId,
            'position' => 'Staff (Đã mời)',
            'is_active' => true,
        ]);
    }
}
