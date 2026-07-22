<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class SystemAuditService
{
    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        User $actor,
        Model $subject,
        string $event,
        string $description,
        ?array $old,
        ?array $new,
        array $metadata = [],
    ): void {
        activity($this->logName($subject))
            ->performedOn($subject)
            ->causedBy($actor)
            ->event($event)
            ->withProperties($this->sanitize([
                'old' => $old,
                'new' => $new,
                ...$metadata,
            ]))
            ->log($description);
    }

    /** @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function sanitize(array $values): array
    {
        $sensitiveKeys = ['password', 'password_hash', 'remember_token', 'token', 'secret'];

        foreach ($values as $key => $value) {
            if (in_array(mb_strtolower((string) $key), $sensitiveKeys, true)) {
                unset($values[$key]);
            } elseif (is_array($value)) {
                $values[$key] = $this->sanitize($value);
            }
        }

        return $values;
    }

    private function logName(Model $subject): string
    {
        return match (true) {
            $subject instanceof User => 'users',
            default => str($subject->getTable())->replace('_', '-')->toString(),
        };
    }
}
