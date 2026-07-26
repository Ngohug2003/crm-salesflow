<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ExportBatch;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportDownloadController extends Controller
{
    /**
     * Handle the incoming signed request for export file download.
     *
     * @throws AuthorizationException
     */
    public function __invoke(Request $request, ExportBatch $batch): StreamedResponse
    {
        $actor = auth()->user();
        if ($actor === null) {
            throw new AuthorizationException('Bạn chưa đăng nhập.');
        }

        // Check ownership or super admin / manager permission
        if ($batch->user_id !== $actor->id && ! Gate::allows('leads.view')) {
            throw new AuthorizationException('Bạn không có quyền tải xuống tệp dữ liệu này.');
        }

        if ($batch->status !== 'completed' || $batch->file_path === null || ! Storage::disk('local')->exists($batch->file_path)) {
            abort(404, 'Tệp dữ liệu xuất không tồn tại hoặc đợt xuất chưa hoàn tất.');
        }

        $fileName = $batch->file_name ?? "export_#{$batch->id}.csv";

        return Storage::disk('local')->download($batch->file_path, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
