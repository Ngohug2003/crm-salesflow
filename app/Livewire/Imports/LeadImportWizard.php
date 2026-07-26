<?php

declare(strict_types=1);

namespace App\Livewire\Imports;

use App\Services\Import\ImportPreviewService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LeadImportWizard extends Component
{
    use WithFileUploads;

    /** @var mixed */
    public $importFile = null;

    public ?string $tempFileKey = null;

    /** @var array<string, mixed>|null */
    public ?array $preview = null;

    public int $step = 1;

    public function mount(): void
    {
        $actor = auth()->user();
        if ($actor === null) {
            throw new AuthorizationException('Bạn cần đăng nhập để thực hiện nhập dữ liệu.');
        }

        if (! Gate::allows('leads.import') && ! Gate::allows('leads.create')) {
            throw new AuthorizationException('Bạn không có quyền nhập khách hàng tiềm năng.');
        }
    }

    public function updatedImportFile(ImportPreviewService $service): void
    {
        $this->uploadAndPreview($service);
    }

    public function uploadAndPreview(ImportPreviewService $service): void
    {
        $this->validate([
            'importFile' => ['required', 'file', 'max:10240'],
        ], [
            'importFile.required' => 'Vui lòng chọn tệp dữ liệu CSV.',
            'importFile.file' => 'Tệp tải lên không hợp lệ.',
            'importFile.max' => 'Dung lượng tệp vượt quá giới hạn 10MB.',
        ]);

        /** @var UploadedFile $file */
        $file = $this->importFile;
        $originalName = $file->getClientOriginalName();

        $tempKey = $service->storeTempFile($file);
        $previewData = $service->generatePreview($tempKey, $originalName);

        $this->tempFileKey = $tempKey;
        $this->preview = $previewData->toArray();
        $this->reset('importFile');
    }

    public function downloadTemplate(ImportPreviewService $service): StreamedResponse
    {
        return $service->downloadSampleCsvTemplate();
    }

    public function resetWizard(): void
    {
        $this->reset(['importFile', 'tempFileKey', 'preview']);
        $this->step = 1;
    }

    public function proceedToMapping(): void
    {
        if ($this->preview === null) {
            return;
        }

        $this->step = 2;
    }

    public function render(): View
    {
        return view('livewire.imports.lead-import-wizard')
            ->layout('layouts.app', ['title' => 'Nhập Khách hàng tiềm năng']);
    }
}
