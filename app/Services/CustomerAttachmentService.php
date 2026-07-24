<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attachment;
use App\Models\User;
use App\Services\Authorization\DataScopeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

final readonly class CustomerAttachmentService
{
    /** @var list<string> */
    private const array DISALLOWED_EXTENSIONS = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'phar',
        'exe', 'bat', 'cmd', 'sh', 'bash', 'bin', 'dll', 'cgi', 'pl', 'py', 'js',
    ];

    public function __construct(
        private SystemAuditService $audit,
    ) {}

    public function upload(User $actor, Model $attachable, UploadedFile $file, ?string $customName = null): Attachment
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, self::DISALLOWED_EXTENSIONS, true)) {
            throw new InvalidArgumentException("Định dạng tệp .{$extension} không được phép tải lên do lý do an toàn bảo mật.");
        }

        $fileName = $customName ?: $file->getClientOriginalName();
        $typeFolder = class_basename($attachable);
        $path = $file->storeAs(
            "private/attachments/{$typeFolder}/{$attachable->getKey()}",
            uniqid().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName),
            'local',
        );

        $attachment = Attachment::query()->create([
            'attachable_type' => $attachable->getMorphClass(),
            'attachable_id' => $attachable->getKey(),
            'file_name' => $fileName,
            'file_path' => $path,
            'disk' => 'local',
            'file_size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
            'owner_id' => $attachable->getAttribute('owner_id'),
            'department_id' => $attachable->getAttribute('department_id'),
            'created_by' => $actor->getKey(),
        ]);

        $this->audit->record(
            $actor,
            $attachable,
            'attachment_added',
            "Tải lên tệp đính kèm: {$fileName}",
            null,
            [
                'attachment_id' => $attachment->id,
                'file_name' => $fileName,
                'file_size' => $attachment->humanSize(),
            ],
        );

        return $attachment;
    }

    public function delete(User $actor, Attachment $attachment): bool
    {
        $attachable = $attachment->attachable;
        if ($attachable instanceof Model) {
            $this->audit->record(
                $actor,
                $attachable,
                'attachment_deleted',
                "Xóa tệp đính kèm: {$attachment->file_name}",
                ['attachment_id' => $attachment->id, 'file_name' => $attachment->file_name],
                null,
            );
        }

        return (bool) $attachment->delete();
    }
}
