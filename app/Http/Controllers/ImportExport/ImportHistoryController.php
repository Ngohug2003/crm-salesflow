<?php

declare(strict_types=1);

namespace App\Http\Controllers\ImportExport;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Models\User;
use App\Services\ImportExport\ImportHistoryService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ImportHistoryController extends Controller
{
    public function downloadErrorLog(ImportBatch $batch, ImportHistoryService $service): StreamedResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $superAdminRole = (string) config('crm.rbac.super_admin_role', 'super-admin');
        if ($batch->user_id !== $user->id && ! $user->hasRole($superAdminRole) && ! $user->hasRole('admin') && ! $user->can('users.manage')) {
            abort(403, 'Bạn không có quyền tải xuống nhật ký lỗi của lượt import này.');
        }

        return $service->downloadErrorLogStream($batch);
    }
}
