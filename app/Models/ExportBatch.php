<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property array<string, mixed>|null $filters
 * @property string|null $file_path
 * @property string|null $file_name
 * @property int $file_size
 * @property string $status
 * @property int $total_rows
 * @property string|null $error_message
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ExportBatch extends Model
{
    use HasFactory;

    protected $table = 'export_batches';

    protected $fillable = [
        'user_id',
        'type',
        'filters',
        'file_path',
        'file_name',
        'file_size',
        'status',
        'total_rows',
        'error_message',
        'completed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'total_rows' => 'integer',
            'filters' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
