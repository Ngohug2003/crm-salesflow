@php
    $steps = [
        1 => ['label' => 'Upload', 'description' => 'Tải tệp và xem trước', 'icon' => 'arrow-up-tray'],
        2 => ['label' => 'Mapping', 'description' => 'Ghép cột dữ liệu', 'icon' => 'rectangle-group'],
        3 => ['label' => 'Trùng lặp', 'description' => 'Chọn cách xử lý', 'icon' => 'shield-check'],
        4 => ['label' => 'Queue', 'description' => 'Theo dõi tiến trình', 'icon' => 'clock'],
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400" wire:navigate>Trang chủ</a>
                <span>/</span>
                <a href="{{ route('leads.index') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400" wire:navigate>Khách hàng tiềm năng</a>
                <span>/</span>
                <span class="text-slate-900 dark:text-white">Import Lead</span>
            </div>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">Nhập dữ liệu Lead hàng loạt</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-400">
                Tải CSV/TSV, kiểm tra dữ liệu mẫu, chọn cách xử lý trùng lặp rồi đưa vào hàng đợi để xử lý nền.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                wire:click="downloadTemplate"
                class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 text-sm font-medium text-slate-700 shadow-xs transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
            >
                <flux:icon.arrow-down-tray class="size-4 text-slate-500" />
                Tải tệp mẫu CSV
            </button>

            <a
                href="{{ route('leads.index') }}"
                wire:navigate
                class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 text-sm font-medium text-slate-700 shadow-xs transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
            >
                <flux:icon.arrow-left class="size-4 text-slate-500" />
                Danh sách Lead
            </a>
        </div>
    </div>

    <nav aria-label="Tiến trình Import" class="crm-card p-4">
        <ol class="grid gap-3 md:grid-cols-4">
            @foreach ($steps as $stepNumber => $item)
                @php
                    $isCurrent = $step === $stepNumber;
                    $isDone = $step > $stepNumber;
                    $isAvailable = $step >= $stepNumber;
                @endphp

                <li class="rounded-lg border px-3 py-3 transition {{ $isCurrent ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/30' : ($isDone ? 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900' : 'border-slate-200 bg-slate-50 opacity-70 dark:border-slate-800 dark:bg-slate-900/50') }}">
                    <div class="flex items-start gap-3">
                        <span class="grid size-8 shrink-0 place-items-center rounded-full {{ $isCurrent ? 'bg-emerald-600 text-white' : ($isDone ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-400') }}">
                            @if ($isDone)
                                <flux:icon.check class="size-4" />
                            @else
                                <flux:icon :name="$item['icon']" class="size-4" />
                            @endif
                        </span>
                        <span class="min-w-0">
                            <span class="block text-xs font-medium {{ $isAvailable ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-500 dark:text-slate-400' }}">Bước {{ $stepNumber }}</span>
                            <span class="block text-sm font-semibold text-slate-950 dark:text-white">{{ $item['label'] }}</span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $item['description'] }}</span>
                        </span>
                    </div>
                </li>
            @endforeach
        </ol>
    </nav>

    @if ($step === 1)
        <section class="crm-card space-y-6" aria-labelledby="import-upload-title">
            @if ($preview === null)
                <div class="grid gap-6 lg:grid-cols-[0.7fr_0.3fr]">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Bước 1</p>
                        <h2 id="import-upload-title" class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">Chọn tệp dữ liệu từ máy tính</h2>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                            Chấp nhận CSV, TSV hoặc TXT mã hóa UTF-8. Dung lượng tối đa 10MB. Sau khi tải lên, hệ thống chỉ đọc trước dữ liệu mẫu để anh kiểm tra.
                        </p>

                        <div
                            x-data="{ isDragging: false }"
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="isDragging = false"
                            class="mt-5 grid min-h-64 place-items-center rounded-lg border-2 border-dashed px-6 py-10 text-center transition"
                            :class="isDragging ? 'border-emerald-500 bg-emerald-50 dark:border-emerald-400 dark:bg-emerald-950/20' : 'border-slate-300 bg-slate-50 hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900/50 dark:hover:border-slate-600'"
                        >
                            <div>
                                <div class="mx-auto grid size-12 place-items-center rounded-full bg-white text-emerald-600 shadow-xs dark:bg-slate-950 dark:text-emerald-300">
                                    <flux:icon.cloud-arrow-up class="size-6" />
                                </div>
                                <div class="mt-4 text-sm text-slate-700 dark:text-slate-200">
                                    <label for="file-upload" class="cursor-pointer font-semibold text-emerald-700 hover:text-emerald-600 dark:text-emerald-300 dark:hover:text-emerald-200">
                                        Chọn tệp để tải lên
                                        <input id="file-upload" wire:model="importFile" type="file" class="sr-only" accept=".csv,.tsv,.txt">
                                    </label>
                                    <span> hoặc kéo thả vào vùng này</span>
                                </div>
                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">CSV/TXT/TSV, tối đa 10MB</p>

                                <div wire:loading wire:target="importFile" class="mt-4 inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                    <flux:icon.arrow-path class="size-4 animate-spin" />
                                    Đang đọc dữ liệu xem trước...
                                </div>

                                @error('importFile')
                                    <p class="mt-4 text-sm font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <aside class="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                        <h3 class="text-sm font-semibold text-slate-950 dark:text-white">Chuẩn bị file</h3>
                        <div class="mt-4 space-y-4 text-sm text-slate-600 dark:text-slate-400">
                            <div class="flex gap-3">
                                <flux:icon.identification class="mt-0.5 size-4 shrink-0 text-slate-400" />
                                <p>Nên có ít nhất một định danh: email hoặc số điện thoại.</p>
                            </div>
                            <div class="flex gap-3">
                                <flux:icon.table-cells class="mt-0.5 size-4 shrink-0 text-slate-400" />
                                <p>Dòng đầu tiên là tiêu đề cột để hệ thống tự gợi ý mapping.</p>
                            </div>
                            <div class="flex gap-3">
                                <flux:icon.shield-check class="mt-0.5 size-4 shrink-0 text-slate-400" />
                                <p>Dữ liệu được lưu tạm/private và xử lý bằng queue sau khi anh xác nhận.</p>
                            </div>
                        </div>
                    </aside>
                </div>
            @else
                <div class="space-y-5">
                    <div class="flex flex-col gap-4 rounded-lg border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/20 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-white text-emerald-600 dark:bg-slate-950 dark:text-emerald-300">
                                <flux:icon.document-check class="size-5" />
                            </span>
                            <div class="min-w-0">
                                <h2 class="truncate text-base font-semibold text-slate-950 dark:text-white">{{ $preview['original_filename'] }}</h2>
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                                    <span>{{ $preview['file_size_formatted'] }}</span>
                                    <span class="text-slate-300 dark:text-slate-700">|</span>
                                    <span>{{ number_format($preview['total_rows_estimate']) }} dòng dữ liệu</span>
                                    <span class="text-slate-300 dark:text-slate-700">|</span>
                                    <span>{{ count($preview['headers']) }} cột</span>
                                    <span class="text-slate-300 dark:text-slate-700">|</span>
                                    <span>Phân cách: <code class="rounded bg-white px-1.5 py-0.5 text-[11px] text-slate-700 dark:bg-slate-900 dark:text-slate-200">{{ $preview['delimiter'] === "\t" ? 'TAB' : $preview['delimiter'] }}</code></span>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            wire:click="resetWizard"
                            class="inline-flex min-h-9 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-3 text-sm font-medium text-slate-700 shadow-xs transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                        >
                            <flux:icon.arrow-path class="size-4 text-slate-500" />
                            Tải tệp khác
                        </button>
                    </div>

                    <div>
                        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-950 dark:text-white">Xem trước 5 dòng đầu tiên</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Kiểm tra nhanh tiêu đề và vài dòng mẫu trước khi mapping.</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800">
                            <table class="min-w-full divide-y divide-slate-200 text-left text-sm dark:divide-slate-800">
                                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-900 dark:text-slate-400">
                                    <tr>
                                        <th class="w-14 px-4 py-3">#</th>
                                        @foreach ($preview['headers'] as $header)
                                            <th class="whitespace-nowrap px-4 py-3">{{ $header }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white text-slate-700 dark:divide-slate-800 dark:bg-slate-950 dark:text-slate-300">
                                    @foreach ($preview['preview_rows'] as $index => $row)
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900">
                                            <td class="px-4 py-3 font-mono text-xs text-slate-400">{{ $index + 1 }}</td>
                                            @foreach ($preview['headers'] as $header)
                                                <td class="max-w-64 truncate whitespace-nowrap px-4 py-3">{{ $row[$header] ?? '' }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                        <button type="button" wire:click="resetWizard" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 shadow-xs transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                            Hủy
                        </button>

                        <button type="button" wire:click="proceedToMapping" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white shadow-xs transition hover:bg-emerald-500">
                            Tiếp tục mapping
                            <flux:icon.arrow-right class="size-4" />
                        </button>
                    </div>
                </div>
            @endif
        </section>
    @elseif ($step === 2)
        <section class="crm-card space-y-6" aria-labelledby="mapping-title">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Bước 2</p>
                    <h2 id="mapping-title" class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">Ghép nối Cột & Kiểm tra dữ liệu mẫu</h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Chọn cột trong file tương ứng với từng trường Lead trong CRM.</p>
                </div>

                <button type="button" wire:click="runValidation" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 text-sm font-medium text-slate-700 shadow-xs transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                    <flux:icon.arrow-path class="size-4 text-slate-500" />
                    Kiểm tra lại
                </button>
            </div>

            @error('mapping')
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200" role="alert">
                    {{ $message }}
                </div>
            @enderror

            <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
                <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm dark:divide-slate-800">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-900 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3">Trường CRM</th>
                                <th class="px-4 py-3">Yêu cầu</th>
                                <th class="px-4 py-3">Cột trong file</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-950">
                            @foreach ($schema as $crmField => $fieldConfig)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-900">
                                    <td class="px-4 py-3">
                                        <span class="block font-medium text-slate-950 dark:text-white">{{ $fieldConfig['label'] }}</span>
                                        <code class="text-xs text-slate-400">{{ $crmField }}</code>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($fieldConfig['required'])
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">Định danh</span>
                                        @else
                                            <span class="text-xs text-slate-500 dark:text-slate-400">Tùy chọn</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <select
                                            wire:model.live="mapping.{{ $crmField }}"
                                            class="min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 shadow-xs focus:border-emerald-500 focus:outline-hidden focus:ring-1 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                                        >
                                            <option value="">Bỏ qua trường này</option>
                                            @foreach ($preview['headers'] ?? [] as $header)
                                                <option value="{{ $header }}">{{ $header }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <aside class="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-950 dark:text-white">Báo cáo Kiểm tra dữ liệu</h3>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Kết quả xem trước mẫu 50 dòng đầu tiên.</p>
                        </div>
                        <flux:icon.clipboard-document-check class="size-5 text-slate-400" />
                    </div>

                    @if ($validationResult !== null)
                        <dl class="mt-5 space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <dt class="text-slate-500 dark:text-slate-400">Tổng dữ liệu tệp</dt>
                                <dd class="font-semibold text-indigo-600 dark:text-indigo-400">{{ number_format($preview['total_rows_estimate'] ?? 0) }} dòng</dd>
                            </div>
                            <div class="flex items-center justify-between text-sm border-t border-slate-200 dark:border-slate-800 pt-2">
                                <dt class="text-slate-500 dark:text-slate-400">Dòng xem trước mẫu</dt>
                                <dd class="font-semibold text-slate-950 dark:text-white">{{ $validationResult['total_checked'] }} dòng đầu</dd>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <dt class="text-slate-500 dark:text-slate-400">Hợp lệ trong mẫu</dt>
                                <dd class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">{{ $validationResult['valid_count'] }} dòng</dd>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <dt class="text-slate-500 dark:text-slate-400">Cảnh báo / Lỗi mẫu</dt>
                                <dd class="rounded-full {{ $validationResult['warning_count'] > 0 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300' : 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }} px-2 py-0.5 text-xs font-semibold">{{ $validationResult['warning_count'] }} dòng</dd>
                            </div>
                        </dl>

                        @if ($validationResult['row_errors'] !== [])
                            <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-800">
                                <p class="text-xs font-semibold text-amber-800 dark:text-amber-300">Chi tiết cảnh báo mẫu</p>
                                <div class="mt-2 max-h-48 space-y-2 overflow-y-auto pr-1">
                                    @foreach ($validationResult['row_errors'] as $error)
                                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
                                            <strong>Dòng {{ $error['row'] }}:</strong> {{ $error['message'] }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="mt-5 rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            Mapping email hoặc số điện thoại để xem kết quả kiểm tra.
                        </div>
                    @endif
                </aside>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                <button type="button" wire:click="backToUpload" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 shadow-xs transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                    Quay lại upload
                </button>

                <button type="button" wire:click="proceedToDuplicates" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white shadow-xs transition hover:bg-emerald-500">
                    Chọn chiến lược trùng lặp
                    <flux:icon.arrow-right class="size-4" />
                </button>
            </div>
        </section>
    @elseif ($step === 3)
        <section class="crm-card space-y-6" aria-labelledby="duplicate-title">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Bước 3</p>
                <h2 id="duplicate-title" class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">Chiến lược xử lý trùng lặp</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Chọn cách hệ thống xử lý khi email hoặc số điện thoại đã tồn tại trong CRM.</p>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <label
                    class="relative flex cursor-pointer flex-col rounded-lg border p-4 transition"
                    :class="$wire.duplicateStrategy === 'skip' ? 'border-emerald-500 bg-emerald-50 ring-2 ring-emerald-500/20 dark:border-emerald-700 dark:bg-emerald-950/20' : 'border-slate-200 hover:border-slate-300 dark:border-slate-800 dark:hover:border-slate-700'"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <span class="block text-sm font-semibold text-slate-950 dark:text-white">Bỏ qua</span>
                            <span class="mt-1 inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">Khuyên dùng</span>
                        </div>
                        <input type="radio" wire:model.live="duplicateStrategy" value="skip" class="mt-1 size-4 text-emerald-600">
                    </div>
                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">Giữ dữ liệu hiện tại, bỏ qua dòng trùng trong file import.</p>
                </label>

                <label
                    class="relative flex cursor-pointer flex-col rounded-lg border p-4 transition"
                    :class="$wire.duplicateStrategy === 'update' ? 'border-emerald-500 bg-emerald-50 ring-2 ring-emerald-500/20 dark:border-emerald-700 dark:bg-emerald-950/20' : 'border-slate-200 hover:border-slate-300 dark:border-slate-800 dark:hover:border-slate-700'"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <span class="block text-sm font-semibold text-slate-950 dark:text-white">Cập nhật</span>
                            <span class="mt-1 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">Ghi đè có kiểm soát</span>
                        </div>
                        <input type="radio" wire:model.live="duplicateStrategy" value="update" class="mt-1 size-4 text-emerald-600">
                    </div>
                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">Cập nhật record Lead đã có bằng dữ liệu mới trong file.</p>
                </label>

                <label
                    class="relative flex cursor-pointer flex-col rounded-lg border p-4 transition"
                    :class="$wire.duplicateStrategy === 'create_new' ? 'border-emerald-500 bg-emerald-50 ring-2 ring-emerald-500/20 dark:border-emerald-700 dark:bg-emerald-950/20' : 'border-slate-200 hover:border-slate-300 dark:border-slate-800 dark:hover:border-slate-700'"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <span class="block text-sm font-semibold text-slate-950 dark:text-white">Tạo mới</span>
                            <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">Cho dữ liệu tách biệt</span>
                        </div>
                        <input type="radio" wire:model.live="duplicateStrategy" value="create_new" class="mt-1 size-4 text-emerald-600">
                    </div>
                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">Luôn tạo Lead mới, kể cả khi có email hoặc số điện thoại giống nhau.</p>
                </label>
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                <h3 class="text-sm font-semibold text-slate-950 dark:text-white">Tóm tắt trước khi chạy queue</h3>
                <dl class="mt-4 grid gap-4 text-sm md:grid-cols-3">
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Tệp nguồn</dt>
                        <dd class="mt-1 truncate font-medium text-slate-950 dark:text-white">{{ $preview['original_filename'] ?? '' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Dòng ước tính</dt>
                        <dd class="mt-1 font-medium text-slate-950 dark:text-white">{{ number_format($preview['total_rows_estimate'] ?? 0) }} dòng</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Owner mặc định</dt>
                        <dd class="mt-1 truncate font-medium text-slate-950 dark:text-white">{{ auth()->user()->name }}</dd>
                    </div>
                </dl>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                <button type="button" wire:click="backToMapping" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 shadow-xs transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                    Quay lại mapping
                </button>

                <button type="button" wire:click="startImport" wire:loading.attr="disabled" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-5 text-sm font-semibold text-white shadow-xs transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-70">
                    <span wire:loading.remove wire:target="startImport">Bắt đầu import</span>
                    <span wire:loading wire:target="startImport">Đang tạo batch...</span>
                    <flux:icon.play class="size-4" wire:loading.remove wire:target="startImport" />
                    <flux:icon.arrow-path class="size-4 animate-spin" wire:loading wire:target="startImport" />
                </button>
            </div>
        </section>
    @elseif ($step === 4)
        @php
            $isProcessing = $currentBatch !== null && in_array($currentBatch->status, ['pending', 'processing'], true);
            $total = $currentBatch->total_rows ?? 0;
            $processed = $currentBatch->processed_rows ?? 0;
            $percentage = $total > 0 ? min(100, (int) round(($processed / $total) * 100)) : ($isProcessing ? 50 : 100);
        @endphp

        <section @if ($isProcessing) wire:poll.1000ms @endif class="crm-card space-y-6" aria-labelledby="progress-title">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-4">
                    <span class="grid size-12 shrink-0 place-items-center rounded-full {{ $isProcessing ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-emerald-600 text-white' }}">
                        @if ($isProcessing)
                            <flux:icon.arrow-path class="size-6 animate-spin" />
                        @else
                            <flux:icon.check class="size-6" />
                        @endif
                    </span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Bước 4</p>
                        <h2 id="progress-title" class="mt-1 text-lg font-semibold text-slate-950 dark:text-white">
                            {{ $isProcessing ? 'Đang xử lý import trong queue' : 'Import đã hoàn thành' }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                            Mã đợt import: <strong>#{{ $currentBatch->id ?? $batchId }}</strong>. Trang này tự cập nhật khi batch còn pending hoặc processing.
                        </p>
                    </div>
                </div>

                <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    {{ $currentBatch->status ?? 'processing' }}
                </span>
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between text-sm">
                    <span class="font-medium text-slate-700 dark:text-slate-300">Tiến độ thực thi Queue</span>
                    <span class="font-semibold text-emerald-700 dark:text-emerald-300">{{ $percentage }}%</span>
                </div>
                <div class="h-3 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                    <div class="h-full rounded-full transition-all duration-500 {{ $percentage === 100 ? 'bg-emerald-500' : 'bg-emerald-600' }}" style="width: {{ $percentage }}%"></div>
                </div>
                <div class="mt-2 flex flex-wrap justify-between gap-2 text-xs text-slate-500 dark:text-slate-400">
                    <span>Đã xử lý: <strong>{{ number_format($processed) }}</strong> / <strong>{{ number_format($total) }}</strong> dòng</span>
                    <span>Batch status: <strong class="uppercase">{{ $currentBatch->status ?? 'processing' }}</strong></span>
                </div>
            </div>

            @if ($currentBatch !== null)
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Tổng số dòng</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ number_format($currentBatch->total_rows) }}</p>
                    </div>
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/20">
                        <p class="text-xs font-medium text-emerald-700 dark:text-emerald-300">Thành công</p>
                        <p class="mt-2 text-2xl font-semibold text-emerald-700 dark:text-emerald-300">{{ number_format($currentBatch->successful_rows) }}</p>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50/60 p-4 dark:border-amber-900/60 dark:bg-amber-950/20">
                        <p class="text-xs font-medium text-amber-800 dark:text-amber-300">Bỏ qua trùng</p>
                        <p class="mt-2 text-2xl font-semibold text-amber-800 dark:text-amber-300">{{ number_format($currentBatch->skipped_rows) }}</p>
                    </div>
                    <div class="rounded-lg border border-rose-200 bg-rose-50/60 p-4 dark:border-rose-900/60 dark:bg-rose-950/20">
                        <p class="text-xs font-medium text-rose-700 dark:text-rose-300">Thất bại</p>
                        <p class="mt-2 text-2xl font-semibold text-rose-700 dark:text-rose-300">{{ number_format($currentBatch->failed_rows) }}</p>
                    </div>
                </div>

                @if ($currentBatch->failed_rows > 0 || ($currentBatch->error_log !== null && $currentBatch->error_log !== []))
                    <div class="rounded-lg border border-rose-200 bg-rose-50/60 p-4 dark:border-rose-900/60 dark:bg-rose-950/20">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-rose-900 dark:text-rose-200">Có {{ $currentBatch->failed_rows }} dòng import lỗi</h3>
                                <p class="mt-1 text-sm text-rose-700 dark:text-rose-300">Tải file lỗi CSV để sửa dữ liệu rồi import lại.</p>
                            </div>

                            <button type="button" wire:click="downloadErrorFile" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-rose-600 px-4 text-sm font-semibold text-white shadow-xs transition hover:bg-rose-500">
                                <flux:icon.arrow-down-tray class="size-4" />
                                Tải file lỗi CSV
                            </button>
                        </div>

                        @if ($currentBatch->error_log !== null && $currentBatch->error_log !== [])
                            <div class="mt-4 border-t border-rose-200 pt-4 dark:border-rose-900/60">
                                <p class="text-xs font-semibold text-rose-900 dark:text-rose-200">Chi tiết lỗi mẫu</p>
                                <div class="mt-2 max-h-48 space-y-2 overflow-y-auto pr-1">
                                    @foreach (array_slice($currentBatch->error_log, 0, 10) as $err)
                                        <div class="rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs text-rose-900 dark:border-rose-900/50 dark:bg-slate-950 dark:text-rose-200">
                                            <strong>Dòng {{ $err['row'] ?? 'N/A' }}:</strong> {{ $err['error'] ?? 'Lỗi không xác định' }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            @endif

            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                <button type="button" wire:click="resetWizard" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 shadow-xs transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                    Nhập tệp khác
                </button>

                <a href="{{ route('leads.index') }}" wire:navigate class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white shadow-xs transition hover:bg-emerald-500">
                    Về danh sách Lead
                    <flux:icon.arrow-right class="size-4" />
                </a>
            </div>
        </section>
    @endif
</div>
