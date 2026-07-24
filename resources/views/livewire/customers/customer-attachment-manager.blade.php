<section class="crm-card">
    <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-lg font-semibold">Tệp đính kèm ({{ $this->attachments->count() }})</h2>
            <p class="mt-1 text-sm text-slate-500">Tải lên tệp tài liệu, tài liệu báo giá, hợp đồng hoặc tài nguyên liên quan.</p>
        </div>
    </div>

    @if (session()->has('attachment_message'))
        <div class="mb-4 rounded-lg bg-emerald-50 p-3 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">
            {{ session('attachment_message') }}
        </div>
    @endif

    <form wire:submit="uploadFile" class="mb-6 flex flex-col items-start gap-3 sm:flex-row sm:items-center">
        <div class="flex-1 w-full">
            <flux:input
                wire:model="file"
                type="file"
                placeholder="Chọn tệp tin (Tối đa 10MB)"
            />
            @error('file') <span class="mt-1 text-xs text-red-500 font-semibold">{{ $message }}</span> @enderror
        </div>
        <flux:button type="submit" variant="primary" icon="arrow-up-tray" size="sm" class="mt-1 sm:mt-0">
            Tải lên tệp
        </flux:button>
    </form>

    <div class="divide-y divide-slate-200 dark:divide-slate-800">
        @forelse ($this->attachments as $att)
            <div class="flex items-center justify-between py-3">
                <div class="flex items-center gap-3">
                    <flux:icon.document-text class="size-6 text-slate-400 shrink-0" />
                    <div>
                        <div class="font-medium text-slate-900 dark:text-white text-sm">
                            {{ $att->file_name }}
                        </div>
                        <div class="text-xs text-slate-500">
                            {{ $att->humanSize() }} | Tải lên bởi {{ $att->createdBy?->name ?: 'Hệ thống' }} vào {{ $att->created_at?->format('d/m/Y H:i') }}
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <flux:button wire:click="downloadAttachment({{ $att->id }})" size="sm" variant="ghost" icon="arrow-down-tray">
                        Tải về
                    </flux:button>
                    <flux:button wire:click="deleteAttachment({{ $att->id }})" wire:confirm="Bạn có chắc chắn muốn xóa tệp này không?" size="sm" variant="danger" icon="trash">
                        Xóa
                    </flux:button>
                </div>
            </div>
        @empty
            <p class="py-4 text-sm text-slate-500">Chưa có tệp đính kèm nào được đăng tải.</p>
        @endforelse
    </div>
</section>
