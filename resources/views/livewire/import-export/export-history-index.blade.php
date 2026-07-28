<div>
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">Nhập & Xuất / Quản lý</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">Lịch sử Xuất dữ liệu (Export History)</h1>
            <p class="mt-2 max-w-3xl text-slate-500">Theo dõi danh sách tệp báo cáo và dữ liệu đã xuất từ hệ thống. Tải về lại các tập tin dữ liệu đã hoàn tất.</p>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Tổng đợt Xuất dữ liệu</p>
            <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['total_batches']) }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-5 shadow-xs dark:border-emerald-950 dark:bg-emerald-950/20">
            <p class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Đã hoàn thành</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ number_format($stats['completed_batches']) }}</p>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-5 shadow-xs dark:border-blue-950 dark:bg-blue-950/20">
            <p class="text-xs font-medium text-blue-600 dark:text-blue-400">Tổng bản ghi đã xuất</p>
            <p class="mt-2 text-2xl font-bold text-blue-700 dark:text-blue-300">{{ number_format($stats['total_exported_rows']) }}</p>
        </div>
        <div class="rounded-xl border border-purple-200 bg-purple-50/50 p-5 shadow-xs dark:border-purple-950 dark:bg-purple-950/20">
            <p class="text-xs font-medium text-purple-600 dark:text-purple-400">Tổng dung lượng tệp</p>
            <p class="mt-2 text-2xl font-bold text-purple-700 dark:text-purple-300">{{ $stats['total_file_size_formatted'] }}</p>
        </div>
    </div>

    {{-- Filters Card --}}
    <section class="crm-card">
        <div class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/50">
            <div class="grid gap-4 md:grid-cols-12">
                <div class="md:col-span-5">
                    <label class="mb-1.5 block text-xs font-medium text-slate-600 dark:text-slate-300" for="export-search">Tìm kiếm file / Người xuất</label>
                    <flux:input id="export-search" wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Tên tệp xuất, người xuất dữ liệu..." />
                </div>
                <div class="md:col-span-3">
                    <label class="mb-1.5 block text-xs font-medium text-slate-600 dark:text-slate-300" for="export-type">Loại dữ liệu</label>
                    <x-forms.smart-select id="export-type" wire:model.live="type">
                        <option value="all">Tất cả loại xuất</option>
                        <option value="lead">Khách hàng tiềm năng (Lead)</option>
                        <option value="company">Doanh nghiệp (Company)</option>
                        <option value="contact">Người liên hệ (Contact)</option>
                        <option value="opportunity">Cơ hội bán hàng (Opportunity)</option>
                        <option value="revenue">Báo cáo doanh thu</option>
                    </x-forms.smart-select>
                </div>
                <div class="md:col-span-4">
                    <label class="mb-1.5 block text-xs font-medium text-slate-600 dark:text-slate-300" for="export-status">Trạng thái</label>
                    <x-forms.smart-select id="export-status" wire:model.live="status">
                        <option value="all">Tất cả trạng thái</option>
                        <option value="completed">Hoàn thành (Completed)</option>
                        <option value="processing">Đang xử lý (Processing)</option>
                        <option value="failed">Thất bại (Failed)</option>
                        <option value="pending">Chờ xử lý (Pending)</option>
                    </x-forms.smart-select>
                </div>
            </div>
        </div>

        {{-- Table --}}
        @if ($batches->isEmpty())
            <div class="grid min-h-48 place-items-center rounded-xl border border-dashed border-slate-300 p-8 text-center dark:border-slate-700">
                <div>
                    <p class="font-medium text-slate-900 dark:text-white">Chưa có lịch sử xuất dữ liệu nào</p>
                    <p class="mt-1 text-xs text-slate-500">Hãy xuất dữ liệu từ danh sách Lead/Doanh nghiệp hoặc các báo cáo để lưu lịch sử tại đây.</p>
                </div>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Thời gian / Tệp xuất</flux:table.column>
                    <flux:table.column>Loại dữ liệu</flux:table.column>
                    <flux:table.column>Người xuất</flux:table.column>
                    <flux:table.column>Số bản ghi</flux:table.column>
                    <flux:table.column>Dung lượng tệp</flux:table.column>
                    <flux:table.column>Trạng thái</flux:table.column>
                    <flux:table.column class="text-right">Tải về</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($batches as $batch)
                        <flux:table.row :key="$batch->id">
                            <flux:table.cell>
                                <p class="font-medium text-slate-900 dark:text-white">{{ $batch->file_name ?? ('export_batch_'.$batch->id.'.csv') }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $batch->created_at?->timezone(config('crm.display_timezone', 'Asia/Ho_Chi_Minh'))->format('d/m/Y H:i:s') }}
                                </p>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm">{{ strtoupper($batch->type) }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <p class="text-xs font-medium text-slate-900 dark:text-white">{{ $batch->user?->name ?? 'N/A' }}</p>
                                <p class="text-[11px] text-slate-500">{{ $batch->user?->email }}</p>
                            </flux:table.cell>
                            <flux:table.cell>
                                <p class="text-xs font-medium text-slate-900 dark:text-white">{{ number_format($batch->total_rows) }} dòng</p>
                            </flux:table.cell>
                            <flux:table.cell>
                                <p class="text-xs font-mono text-slate-600 dark:text-slate-400">{{ $service->formatBytes($batch->file_size) }}</p>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($batch->status === 'completed')
                                    <flux:badge color="emerald" size="sm">Hoàn thành</flux:badge>
                                @elseif ($batch->status === 'processing')
                                    <flux:badge color="amber" size="sm">Đang xử lý</flux:badge>
                                @elseif ($batch->status === 'failed')
                                    <flux:badge color="red" size="sm">Thất bại</flux:badge>
                                @else
                                    <flux:badge color="blue" size="sm">Chờ xử lý</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                @if ($batch->status === 'completed')
                                    <flux:button href="{{ route('exports.history.download', $batch->id) }}" size="xs" variant="subtle" icon="arrow-down-tray">
                                        Tải tệp
                                    </flux:button>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            <div class="mt-4">
                {{ $batches->links() }}
            </div>
        @endif
    </section>
</div>
