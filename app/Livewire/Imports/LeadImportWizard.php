<?php

declare(strict_types=1);

namespace App\Livewire\Imports;

use App\Data\ImportMappingSchema;
use App\Models\ImportBatch;
use App\Services\Import\ImportExecutionService;
use App\Services\Import\ImportPreviewService;
use App\Services\Import\ImportValidationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
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

    /** @var array<string, string> */
    public array $mapping = [];

    /** @var array<string, mixed>|null */
    public ?array $validationResult = null;

    public string $duplicateStrategy = 'skip';

    public ?int $batchId = null;

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

    public function updatedImportFile(ImportPreviewService $previewService, ImportValidationService $validationService): void
    {
        $this->uploadAndPreview($previewService, $validationService);
    }

    public function uploadAndPreview(ImportPreviewService $previewService, ImportValidationService $validationService): void
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

        $tempKey = $previewService->storeTempFile($file);
        $previewData = $previewService->generatePreview($tempKey, $originalName);

        $this->tempFileKey = $tempKey;
        $this->preview = $previewData->toArray();
        $this->reset('importFile');

        // Auto map headers
        $this->mapping = $validationService->autoMapHeaders($previewData->headers);
        $this->runValidation($validationService);
    }

    public function updatedMapping(ImportValidationService $validationService): void
    {
        $this->runValidation($validationService);
    }

    public function runValidation(ImportValidationService $validationService): void
    {
        if ($this->tempFileKey === null || $this->preview === null) {
            return;
        }

        try {
            $delimiter = (string) ($this->preview['delimiter'] ?? ',');
            $this->validationResult = $validationService->validateMappedData(
                $this->tempFileKey,
                $this->mapping,
                $delimiter,
            );
            $this->resetErrorBag('mapping');
        } catch (ValidationException $e) {
            $this->validationResult = null;
            $this->addError('mapping', $e->getMessage());
        }
    }

    public function downloadTemplate(ImportPreviewService $service): StreamedResponse
    {
        return $service->downloadSampleCsvTemplate();
    }

    public function resetWizard(): void
    {
        $this->reset(['importFile', 'tempFileKey', 'preview', 'mapping', 'validationResult', 'duplicateStrategy', 'batchId']);
        $this->step = 1;
    }

    public function proceedToMapping(ImportValidationService $validationService): void
    {
        if ($this->preview === null) {
            return;
        }

        if ($this->mapping === []) {
            $headers = (array) ($this->preview['headers'] ?? []);
            /** @var list<string> $stringHeaders */
            $stringHeaders = array_values(array_map('strval', $headers));
            $this->mapping = $validationService->autoMapHeaders($stringHeaders);
        }

        $this->runValidation($validationService);
        $this->step = 2;
    }

    public function backToUpload(): void
    {
        $this->step = 1;
    }

    public function proceedToDuplicates(ImportValidationService $validationService): void
    {
        if ($this->tempFileKey === null || $this->preview === null) {
            return;
        }

        $validationService->validateMappingSchema($this->mapping);
        $this->step = 3;
    }

    public function backToMapping(): void
    {
        $this->step = 2;
    }

    public function startImport(ImportExecutionService $executionService): void
    {
        if ($this->tempFileKey === null || $this->preview === null) {
            return;
        }

        $actor = auth()->user();
        if ($actor === null) {
            return;
        }

        $originalFilename = (string) ($this->preview['original_filename'] ?? 'import_leads.csv');
        $delimiter = (string) ($this->preview['delimiter'] ?? ',');

        $batch = $executionService->execute(
            $actor,
            $this->tempFileKey,
            $originalFilename,
            $this->mapping,
            $this->duplicateStrategy,
            $delimiter,
        );

        $this->batchId = $batch->id;
        $this->step = 4;
    }

    public function downloadErrorFile(ImportExecutionService $executionService): StreamedResponse
    {
        if ($this->batchId === null) {
            throw new AuthorizationException('Không tìm thấy đợt import.');
        }

        $batch = ImportBatch::query()->findOrFail($this->batchId);
        $actor = auth()->user();
        if ($actor === null || ($batch->user_id !== $actor->id && ! Gate::allows('leads.import'))) {
            throw new AuthorizationException('Bạn không có quyền tải tệp báo cáo lỗi của đợt import này.');
        }

        return $executionService->downloadErrorCsvFile($batch);
    }

    public function render(): View
    {
        $batch = $this->batchId !== null ? ImportBatch::query()->find($this->batchId) : null;

        return view('livewire.imports.lead-import-wizard', [
            'schema' => ImportMappingSchema::leadFields(),
            'currentBatch' => $batch,
        ])->layout('layouts.app', ['title' => 'Nhập Khách hàng tiềm năng']);
    }
}
