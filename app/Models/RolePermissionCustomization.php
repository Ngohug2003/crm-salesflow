<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

final class RolePermissionCustomization extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'role_id',
        'customized_by',
    ];

    /** @return BelongsTo<Role, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** @return BelongsTo<User, $this> */
    public function customizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customized_by');
    }
}
