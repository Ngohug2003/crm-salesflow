<div>
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">CRM / Lead / Thùng rác</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">Lead đã xóa</h1>
            <p class="mt-2 text-slate-500">Khôi phục Lead trong phạm vi dữ liệu của bạn. Không có thao tác xóa vĩnh viễn trên giao diện.</p>
        </div>
        <flux:button :href="route('leads.index')" wire:navigate variant="ghost" icon="arrow-left">Về danh sách</flux:button>
    </div>

    @if ($notice)
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">{{ $notice }}</div>
    @endif

    <section class="crm-card">
        <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
                <h2 class="font-semibold">Thùng rác</h2>
                <p class="mt-1 text-sm text-slate-500">Có {{ $this->leads->total() }} Lead đã xóa trong phạm vi.</p>
            </div>
            <div class="w-full sm:w-80">
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Tên, email, điện thoại, công ty" aria-label="Tìm Lead đã xóa" />
            </div>
        </div>

        @if ($this->leads->isEmpty())
            <div class="grid min-h-52 place-items-center rounded-xl border border-dashed border-slate-300 text-center dark:border-slate-700">
                <div><p class="font-medium">Thùng rác đang trống</p><p class="mt-1 text-sm text-slate-500">Lead bị xóa mềm sẽ xuất hiện tại đây.</p></div>
            </div>
        @else
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Lead</flux:table.column>
                        <flux:table.column>Phụ trách</flux:table.column>
                        <flux:table.column>Trạng thái</flux:table.column>
                        <flux:table.column>Đã xóa lúc</flux:table.column>
                        <flux:table.column align="end">Thao tác</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->leads as $lead)
                            <flux:table.row :key="$lead->id">
                                <flux:table.cell variant="strong"><p>{{ $lead->full_name }}</p><p class="mt-1 text-xs font-normal text-slate-500">{{ $lead->email ?: $lead->phone ?: 'Chưa có liên hệ' }}</p></flux:table.cell>
                                <flux:table.cell><p>{{ $lead->owner?->name ?? 'Chưa phân công' }}</p><p class="mt-1 text-xs text-slate-500">{{ $lead->department?->name ?? 'Chưa gán' }}</p></flux:table.cell>
                                <flux:table.cell><flux:badge :color="$lead->status->color()" size="sm">{{ $lead->status->label() }}</flux:badge></flux:table.cell>
                                <flux:table.cell>{{ $lead->deleted_at?->timezone(config('crm.display_timezone'))->format('d/m/Y H:i:s') }}</flux:table.cell>
                                <flux:table.cell align="end"><flux:button size="sm" variant="ghost" wire:click="openRestore({{ $lead->id }})">Khôi phục</flux:button></flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
            <div class="mt-5">{{ $this->leads->onEachSide(1)->links() }}</div>
        @endif
    </section>

    <flux:modal name="restore-lead" class="md:w-[32rem]" wire:close="dismissRestore">
        <div class="space-y-5">
            <div><flux:heading size="lg">Khôi phục Lead?</flux:heading><flux:text class="mt-2">Lead <strong>{{ $pendingRestoreName }}</strong> sẽ trở lại danh sách chính với tag và workflow history được giữ nguyên.</flux:text></div>
            <flux:textarea wire:model="restoreReason" label="Lý do khôi phục" rows="3" maxlength="500" placeholder="Không bắt buộc" />
            <div class="flex justify-end gap-3"><flux:button variant="ghost" x-on:click="$flux.modal('restore-lead').close()">Hủy</flux:button><flux:button variant="primary" wire:click="confirmRestore" wire:loading.attr="disabled" wire:target="confirmRestore">Xác nhận khôi phục</flux:button></div>
        </div>
    </flux:modal>
</div>
