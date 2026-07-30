<?php

declare(strict_types=1);

namespace App\Services\Audit;

use Spatie\Activitylog\Models\Activity;

final class AuditLogDetailService
{
    /**
     * Parse and structure details for an Activity record including field diffs and metadata.
     *
     * @return array{
     *     id: int,
     *     log_name: string,
     *     event: string,
     *     event_label: string,
     *     event_color: string,
     *     description: string,
     *     request_id: string,
     *     ip_address: string,
     *     user_agent: string,
     *     causer_name: string,
     *     causer_email: string,
     *     subject_type: string,
     *     subject_id: string,
     *     created_at: string,
     *     changes: list<array{
     *         field: string,
     *         label: string,
     *         old: string,
     *         new: string
     *     }>
     * }|null
     */
    public function getAuditDetail(int $activityId): ?array
    {
        /** @var Activity|null $activity */
        $activity = Activity::with(['causer', 'subject'])->find($activityId);
        if ($activity === null) {
            return null;
        }

        $properties = $activity->properties->toArray();
        $old = is_array($properties['old'] ?? null) ? $properties['old'] : [];
        $attributes = is_array($properties['new'] ?? null) ? $properties['new'] : (is_array($properties['attributes'] ?? null) ? $properties['attributes'] : []);

        if ($old === [] && $attributes === []) {
            $ignoredMeta = ['ip', 'ip_address', 'user_agent', 'request_id', 'session_id', 'target_user_id', 'target_user_email', 'deleted_sessions_count', 'current_session_id'];
            /** @var array<string, mixed> $attributes */
            $attributes = array_diff_key($properties, array_flip($ignoredMeta));
        }

        // Build list of field diffs
        $changes = [];
        $allFields = array_unique(array_merge(array_keys($old), array_keys($attributes)));

        // Ignored internal fields
        $ignoredFields = ['updated_at', 'created_at', 'deleted_at', 'password', 'remember_token'];

        foreach ($allFields as $field) {
            if (in_array($field, $ignoredFields, true)) {
                continue;
            }

            $oldVal = $old[$field] ?? null;
            $newVal = $attributes[$field] ?? null;

            if ($oldVal === $newVal) {
                continue;
            }

            $changes[] = [
                'field' => (string) $field,
                'label' => $this->humanizeFieldLabel((string) $field),
                'old' => $this->formatValue($oldVal),
                'new' => $this->formatValue($newVal),
            ];
        }

        /** @var string $event */
        $event = $activity->event ?? 'updated';
        $eventMeta = $this->resolveEventMeta($event);

        $causer = $activity->causer;
        $causerName = $causer !== null && isset($causer->name) ? (string) $causer->name : 'Hệ thống';
        $causerEmail = $causer !== null && isset($causer->email) ? (string) $causer->email : 'N/A';

        $subject = $activity->subject;
        $subjectType = (string) ($activity->subject_type ?? 'Mô hình');
        $subjectId = (string) ($activity->subject_id ?? '—');
        if ($subject !== null && isset($subject->name)) {
            $subjectId .= " ({$subject->name})";
        } elseif ($subject !== null && isset($subject->title)) {
            $subjectId .= " ({$subject->title})";
        }

        return [
            'id' => (int) $activity->id,
            'log_name' => (string) ($activity->log_name ?? 'default'),
            'event' => $event,
            'event_label' => $eventMeta['label'],
            'event_color' => $eventMeta['color'],
            'description' => (string) $activity->description,
            'request_id' => (string) ($activity->request_id ?? ($properties['request_id'] ?? 'N/A')),
            'ip_address' => (string) ($properties['ip_address'] ?? ($properties['ip'] ?? '127.0.0.1')),
            'user_agent' => (string) ($properties['user_agent'] ?? 'N/A'),
            'causer_name' => $causerName,
            'causer_email' => $causerEmail,
            'subject_type' => class_basename($subjectType),
            'subject_id' => $subjectId,
            'created_at' => $activity->created_at !== null
                ? $activity->created_at->timezone((string) config('crm.display_timezone', 'Asia/Ho_Chi_Minh'))->format('d/m/Y H:i:s')
                : now()->format('d/m/Y H:i:s'),
            'changes' => $changes,
        ];
    }

    /**
     * Resolve event label and color badge.
     *
     * @return array{label: string, color: string}
     */
    private function resolveEventMeta(string $event): array
    {
        return match (strtolower($event)) {
            'created' => ['label' => 'Tạo mới', 'color' => 'emerald'],
            'updated' => ['label' => 'Cập nhật', 'color' => 'amber'],
            'deleted' => ['label' => 'Đã xóa', 'color' => 'red'],
            'restored' => ['label' => 'Khôi phục', 'color' => 'blue'],
            'session.revoked', 'session.revoked_others' => ['label' => 'Thu hồi phiên', 'color' => 'purple'],
            default => ['label' => strtoupper($event), 'color' => 'slate'],
        };
    }

    /**
     * Convert field key to Vietnamese label.
     */
    private function humanizeFieldLabel(string $field): string
    {
        $map = [
            'full_name' => 'Họ và tên',
            'name' => 'Tên / Họ tên',
            'title' => 'Tiêu đề',
            'email' => 'Địa chỉ Email',
            'phone' => 'Số điện thoại',
            'status' => 'Trạng thái',
            'stage_id' => 'Giai đoạn bán hàng',
            'amount' => 'Giá trị (VNĐ)',
            'probability' => 'Xác suất (%)',
            'expected_close_date' => 'Ngày dự kiến chốt',
            'actual_close_date' => 'Ngày chốt thực tế',
            'owner_id' => 'Người phụ trách',
            'department_id' => 'Phòng ban',
            'is_active' => 'Trạng thái hoạt động',
            'is_won' => 'Cơ hội Thắng',
            'is_lost' => 'Cơ hội Thua',
            'lost_reason' => 'Lý do thất bại',
            'description' => 'Mô tả chi tiết',
            'source' => 'Nguồn khách hàng',
            'tax_code' => 'Mã số thuế',
            'website' => 'Địa chỉ Website',
            'address' => 'Địa chỉ liên hệ',
        ];

        return $map[$field] ?? str($field)->headline()->toString();
    }

    /**
     * Format mixed value into readable string.
     */
    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '— (Bỏ trống)';
        }

        if (is_bool($value)) {
            return $value ? 'Có / Bật (True)' : 'Không / Tắt (False)';
        }

        if (is_array($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            return $encoded !== false ? $encoded : 'Array';
        }

        return (string) $value;
    }
}
