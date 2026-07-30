<div>
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">Nhập & Xuất / Quản lý</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">Lịch sử Nhập dữ liệu (Import History)</h1>
            <p class="mt-2 max-w-3xl text-slate-500">Theo dõi trạng thái, tiến độ và tỷ lệ thành công của các tệp dữ liệu đã tải lên hệ thống (Leads, Công ty, Liên hệ).</p>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Tổng đợt Import</p>
            <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['total_batches']) }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-5 shadow-xs dark:border-emerald-950 dark:bg-emerald-950/20">
            <p class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Đã hoàn thành</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ number_format($stats['completed_batches']) }}</p>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-5 shadow-xs dark:border-blue-950 dark:bg-blue-950/20">
            <p class="text-xs font-medium text-blue-600 dark:text-blue-400">Dòng nhập thành công</p>
            <p class="mt-2 text-2xl font-bold text-blue-700 dark:text-blue-300">{{ number_format($stats['total_successful_rows']) }}</p>
        </div>
        <div class="rounded-xl border border-red-200 bg-red-50/50 p-5 shadow-xs dark:border-red-950 dark:bg-red-950/20">
            <p class="text-xs font-medium text-red-600 dark:text-red-400">Dòng bị lỗi / Bỏ qua</p>
            <p class="mt-2 text-2xl font-bold text-red-700 dark:text-red-300">{{ number_format($stats['total_failed_rows']) }}</p>
        </div>
    </div>

    {{-- Filters Card --}}
    <section class="crm-card">
        <div class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/50">
            <div class="grid gap-4 md:grid-cols-12">
                <div class="md:col-span-5">
                    <label class="mb-1.5 block text-xs font-medium text-slate-600 dark:text-slate-300" for="import-search">Tìm kiếm file / Người tạo</label>
                    <flux:input id="import-search" wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Tên tệp gốc, người tải lên..." />
                </div>
                <div class="md:col-span-3">
                    <label class="mb-1.5 block text-xs font-medium text-slate-600 dark:text-slate-300" for="import-type">Loại dữ liệu</label>
                    <x-forms.smart-select id="import-type" wire:model.live="type">
                        <option value="all">Tất cả loại dữ liệu</option>
                        <option value="lead">Khách hàng tiềm năng (Lead)</option>
                        <option value="company">Doanh nghiệp (Company)</option>
                        <option value="contact">Người liên hệ (Contact)</option>
                    </x-forms.smart-select>
                </div>
                <div class="md:col-span-4">
                    <label class="mb-1.5 block text-xs font-medium text-slate-600 dark:text-slate-300" for="import-status">Trạng thái</label>
                    <x-forms.smart-select id="import-status" wire:model.live="status">
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
                    <p class="font-medium text-slate-900 dark:text-white">Chưa có lịch sử nhập dữ liệu nào</p>
                    <p class="mt-1 text-xs text-slate-500">Hãy thực hiện nhập danh sách Lead/Doanh nghiệp để ghi nhận kết quả tại đây.</p>
                </div>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Thời gian / Tệp dữ liệu</flux:table.column>
                    <flux:table.column>Loại dữ liệu</flux:table.column>
                    <flux:table.column>Người tải lên</flux:table.column>
                    <flux:table.column>Kết quả dòng</flux:table.column>
                    <flux:table.column>Trạng thái</flux:table.column>
                    <flux:table.column class="text-right">Thao tác</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($batches as $batch)
                        <flux:table.row :key="$batch->id">
                            <flux:table.cell>
                                <p class="font-medium text-slate-900 dark:text-white">{{ $batch->original_filename }}</p>
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
                                <div class="text-xs space-y-0.5">
                                    <p class="text-slate-700 dark:text-slate-300">Tổng: <strong>{{ number_format($batch->total_rows) }}</strong> dòng</p>
                                    <p class="text-emerald-600 dark:text-emerald-400">Thành công: <strong>{{ number_format($batch->successful_rows) }}</strong></p>
                                    @if ($batch->failed_rows > 0)
                                        <p class="text-red-600 dark:text-red-400 font-semibold">Lỗi / Bỏ qua: <strong>{{ number_format($batch->failed_rows) }}</strong></p>
                                    @endif
                                </div>
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
                            <flux:table.cell class="text-right space-x-2">
                                @if ($batch->failed_rows > 0 || ($batch->error_log !== null && count($batch->error_log) > 0))
                                    <flux:button wire:click="openErrorModal({{ $batch->id }})" size="xs" variant="subtle" icon="eye" class="text-amber-600 dark:text-amber-400">
                                        Xem lỗi
                                    </flux:button>
                                    <flux:button href="{{ route('imports.history.download-errors', $batch->id) }}" size="xs" variant="ghost" icon="arrow-down-tray" class="text-red-600 dark:text-red-400">
                                        CSV Lỗi
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

    {{-- Error Log Modal / Drawer --}}
    @if ($selectedBatch !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="w-full max-w-3xl rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900 flex flex-col max-h-[85vh]">
                <div class="flex items-center justify-between border-b border-slate-200 pb-4 dark:border-slate-800">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Chi tiết lỗi Import: {{ $selectedBatch->original_filename }}</h2>
                        <p class="text-xs text-slate-500">Đợt import #{{ $selectedBatch->id }} · {{ number_format($selectedBatch->failed_rows) }} dòng lỗi</p>
                    </div>
                    <flux:button wire:click="closeErrorModal" icon="x-mark" variant="ghost" size="sm" />
                </div>

                <div class="flex-1 overflow-y-auto py-4">
                    @if (empty($selectedBatch->error_log))
                        <div class="p-4 text-center text-xs text-slate-500">
                            Không tìm thấy bản ghi chi tiết dòng lỗi.
                        </div>
                    @else
                        <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800">
                            <table class="w-full text-left text-xs">
                                <thead class="border-b border-slate-200 bg-slate-100 text-slate-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400">
                                    <tr>
                                        <th class="py-2.5 pl-3 pr-2 font-medium w-16">Dòng</th>
                                        <th class="px-2 py-2.5 font-medium w-36">Trường dữ liệu</th>
                                        <th class="py-2.5 pl-2 pr-3 font-medium">Nguyên nhân lỗi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                    @foreach ($selectedBatch->error_log as $idx => $err)
                                        <tr>
                                            <td class="py-2.5 pl-3 pr-2 font-mono text-slate-900 dark:text-white font-medium">
                                                #{{ $err['row'] ?? ($idx + 1) }}
                                            </td>
                                            <td class="px-2 py-2.5 font-mono text-slate-600 dark:text-slate-400">
                                                {{ $err['field'] ?? 'N/A' }}
                                            </td>
                                            <td class="py-2.5 pl-2 pr-3 text-red-600 dark:text-red-400 font-medium">
                                                {{ $err['message'] ?? ($err['error'] ?? 'Lỗi không xác định') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-between border-t border-slate-200 pt-4 dark:border-slate-800">
                    <flux:button href="{{ route('imports.history.download-errors', $selectedBatch->id) }}" icon="arrow-down-tray" variant="primary" size="sm">
                        Tải báo cáo lỗi (CSV)
                    </flux:button>
                    <flux:button wire:click="closeErrorModal" variant="ghost" size="sm">Đóng</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
