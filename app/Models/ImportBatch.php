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
 * @property string $temp_file_key
 * @property string $original_filename
 * @property string $duplicate_strategy
 * @property string $status
 * @property int $total_rows
 * @property int $processed_rows
 * @property int $successful_rows
 * @property int $failed_rows
 * @property int $skipped_rows
 * @property array<string, string> $column_mapping
 * @property array<int, array<string, mixed>>|null $error_log
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ImportBatch extends Model
{
    use HasFactory;

    protected $table = 'import_batches';

    protected $fillable = [
        'user_id',
        'type',
        'temp_file_key',
        'original_filename',
        'duplicate_strategy',
        'status',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'skipped_rows',
        'column_mapping',
        'error_log',
        'completed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'successful_rows' => 'integer',
            'failed_rows' => 'integer',
            'skipped_rows' => 'integer',
            'column_mapping' => 'array',
            'error_log' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
