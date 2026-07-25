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
                    <flux:button wire:click="confirmDeleteAttachment({{ $att->id }})" size="sm" variant="danger" icon="trash">
                        Xóa
                    </flux:button>
                </div>
            </div>
        @empty
            <p class="py-4 text-sm text-slate-500">Chưa có tệp đính kèm nào được đăng tải.</p>
        @endforelse
    </div>

    <!-- Modal Xác nhận xóa Tệp đính kèm -->
    <div
        x-data="{ open: @entangle('confirmingDeleteAttachmentId') }"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
    >
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200 transform"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150 transform"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-4"
        >
            <div class="flex items-center gap-3 text-red-600 dark:text-red-400">
                <div class="rounded-full bg-red-100 p-2.5 dark:bg-red-950/60">
                    <flux:icon.exclamation-triangle class="size-6" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Xác nhận xóa tệp đính kèm</h3>
                    <p class="text-xs text-slate-500">Tệp tin sẽ bị loại bỏ khỏi hệ thống.</p>
                </div>
            </div>

            <p class="text-sm text-slate-600 dark:text-slate-300">
                Bạn có chắc chắn muốn xóa tệp đính kèm này không?
            </p>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <flux:button wire:click="$set('confirmDeleteAttachmentId', null)" variant="ghost" size="sm">
                    Hủy bỏ
                </flux:button>
                <flux:button wire:click="deleteConfirmedAttachment" variant="danger" size="sm">
                    Xác nhận xóa
                </flux:button>
            </div>
        </div>
    </div>
</section>
