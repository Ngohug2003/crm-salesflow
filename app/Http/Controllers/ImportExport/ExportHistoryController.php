<?php

declare(strict_types=1);

namespace App\Http\Controllers\ImportExport;

use App\Http\Controllers\Controller;
use App\Models\ExportBatch;
use App\Models\User;
use App\Services\ImportExport\ExportHistoryService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportHistoryController extends Controller
{
    public function downloadFile(ExportBatch $batch, ExportHistoryService $service): StreamedResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $superAdminRole = (string) config('crm.rbac.super_admin_role', 'super-admin');
        if ($batch->user_id !== $user->id && ! $user->hasRole($superAdminRole) && ! $user->hasRole('admin') && ! $user->can('users.update')) {
            abort(403, 'Bạn không có quyền tải xuống tệp xuất này.');
        }

        return $service->downloadExportFileStream($batch);
    }
}
