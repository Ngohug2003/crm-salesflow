<?php

declare(strict_types=1);

namespace App\Livewire\Customers;

use App\Models\Attachment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Services\CustomerAttachmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class CustomerAttachmentManager extends Component
{
    use WithFileUploads;

    public string $modelType = '';

    public int $modelId = 0;

    /** @var mixed */
    public $file = null;

    public function mount(string $modelType, int $modelId): void
    {
        $this->modelType = $modelType;
        $this->modelId = $modelId;
    }

    public function uploadFile(): void
    {
        $this->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB max
        ]);

        /** @var User $actor */
        $actor = Auth::user();
        /** @var CustomerAttachmentService $service */
        $service = app(CustomerAttachmentService::class);

        $targetModel = $this->resolveModel();
        if ($targetModel === null) {
            return;
        }

        try {
            $service->upload($actor, $targetModel, $this->file);
            $this->reset('file');
            $this->dispatch('attachment-updated');
            session()->flash('attachment_message', 'Tải lên tệp đính kèm thành công.');
        } catch (\Throwable $e) {
            $this->addError('file', $e->getMessage());
        }
    }

    public function deleteAttachment(int $attachmentId): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var CustomerAttachmentService $service */
        $service = app(CustomerAttachmentService::class);

        $attachment = Attachment::query()->find($attachmentId);
        if ($attachment !== null) {
            $service->delete($actor, $attachment);
            $this->dispatch('attachment-updated');
            session()->flash('attachment_message', 'Đã xóa tệp đính kèm.');
        }
    }

    public function downloadAttachment(int $attachmentId): ?BinaryFileResponse
    {
        $attachment = Attachment::query()->find($attachmentId);
        if ($attachment === null) {
            return null;
        }

        $fullPath = storage_path("app/{$attachment->file_path}");
        if (file_exists($fullPath)) {
            return response()->download($fullPath, $attachment->file_name);
        }

        $this->addError('file', 'Không tìm thấy tệp tin trên hệ thống lưu trữ.');

        return null;
    }

    /** @return Collection<int, Attachment> */
    #[Computed]
    public function attachments(): Collection
    {
        return Attachment::query()
            ->where('attachable_type', $this->modelType)
            ->where('attachable_id', $this->modelId)
            ->with('createdBy')
            ->orderByDesc('created_at')
            ->get();
    }

    private function resolveModel(): ?Model
    {
        if ($this->modelType === Company::class || $this->modelType === 'company' || $this->modelType === (new Company)->getMorphClass()) {
            return Company::query()->find($this->modelId);
        }
        if ($this->modelType === Contact::class || $this->modelType === 'contact' || $this->modelType === (new Contact)->getMorphClass()) {
            return Contact::query()->find($this->modelId);
        }

        return null;
    }

    public function render(): View
    {
        return view('livewire.customers.customer-attachment-manager');
    }
}
