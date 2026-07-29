<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $staff_code
 * @property string $full_name
 * @property string $email
 * @property string|null $phone
 * @property Carbon|null $birthday
 * @property string|null $address
 * @property int|null $province_id
 * @property int|null $ward_id
 * @property int|null $department_id
 * @property string|null $position
 * @property Carbon|null $join_date
 * @property bool $is_active
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User|null $user
 * @property-read Department|null $department
 * @property-read Province|null $provinceUnit
 * @property-read Ward|null $ward
 */
final class Staff extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff';

    protected $fillable = [
        'user_id',
        'staff_code',
        'full_name',
        'email',
        'phone',
        'birthday',
        'address',
        'province_id',
        'ward_id',
        'department_id',
        'position',
        'join_date',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'join_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<Province, $this> */
    public function provinceUnit(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    /** @return BelongsTo<Ward, $this> */
    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class, 'ward_id');
    }
}
