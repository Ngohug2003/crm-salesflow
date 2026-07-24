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
            <input
                type="file"
                wire:model="file"
                class="block w-full text-sm text-slate-500 rounded-lg border border-slate-300 bg-white p-2 file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:file:bg-slate-800 dark:file:text-slate-300 dark:hover:file:bg-slate-700"
            />
            <div wire:loading wire:target="file" class="mt-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400">
                Đang xử lý tệp...
            </div>
            @error('file') <span class="mt-1.5 block text-xs font-semibold text-red-500">{{ $message }}</span> @enderror
        </div>
        <flux:button type="submit" variant="primary" icon="arrow-up-tray" size="sm" class="mt-1 sm:mt-0" wire:loading.attr="disabled">
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
