<div class="space-y-6">
    {{-- Header & Breadcrumbs --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400" wire:navigate>Trang chủ</a>
                <span>/</span>
                <a href="{{ route('leads.index') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400" wire:navigate>Khách hàng tiềm năng</a>
                <span>/</span>
                <span class="text-slate-900 dark:text-white">Nhập dữ liệu</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Nhập dữ liệu Lead hàng loạt</h1>
            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">Cấu hình chiến lược xử lý trùng lặp và kích hoạt tiến trình xử lý bất đồng bộ.</p>
        </div>

        <div class="flex items-center gap-3">
            <button
                type="button"
                wire:click="downloadTemplate"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 hover:text-slate-900 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white"
            >
                <svg class="size-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Tải tệp mẫu CSV
            </button>

            <a
                href="{{ route('leads.index') }}"
                wire:navigate
                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 hover:text-slate-900 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white"
            >
                Quay lại danh sách
            </a>
        </div>
    </div>

    {{-- Stepper Progress --}}
    <nav aria-label="Tiến trình Import" class="crm-card py-4">
        <ol role="list" class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between sm:gap-0">
            <li class="flex items-center gap-3 {{ $step >= 1 ? '' : 'opacity-50' }}">
                <span class="flex size-8 items-center justify-center rounded-full {{ $step === 1 ? 'bg-indigo-600 text-white' : 'bg-emerald-600 text-white' }} text-xs font-bold shadow-xs">
                    @if ($step > 1)
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    @else
                        1
                    @endif
                </span>
                <div>
                    <div class="text-xs font-semibold {{ $step === 1 ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500' }}">Bước 1</div>
                    <div class="text-xs font-medium text-slate-900 dark:text-white">Upload & Xem trước</div>
                </div>
            </li>
            <div class="hidden h-0.5 w-12 bg-slate-200 dark:bg-slate-800 sm:block"></div>

            <li class="flex items-center gap-3 {{ $step >= 2 ? '' : 'opacity-50' }}">
                <span class="flex size-8 items-center justify-center rounded-full {{ $step === 2 ? 'bg-indigo-600 text-white' : ($step > 2 ? 'bg-emerald-600 text-white' : 'border border-slate-300 bg-slate-100 text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400') }} text-xs font-bold shadow-xs">
                    @if ($step > 2)
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    @else
                        2
                    @endif
                </span>
                <div>
                    <div class="text-xs font-semibold {{ $step === 2 ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500' }}">Bước 2</div>
                    <div class="text-xs font-medium text-slate-900 dark:text-white">Cấu hình Cột (Mapping)</div>
                </div>
            </li>
            <div class="hidden h-0.5 w-12 bg-slate-200 dark:bg-slate-800 sm:block"></div>

            <li class="flex items-center gap-3 {{ $step >= 3 ? '' : 'opacity-50' }}">
                <span class="flex size-8 items-center justify-center rounded-full {{ $step === 3 ? 'bg-indigo-600 text-white' : ($step > 3 ? 'bg-emerald-600 text-white' : 'border border-slate-300 bg-slate-100 text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400') }} text-xs font-bold shadow-xs">
                    @if ($step > 3)
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    @else
                        3
                    @endif
                </span>
                <div>
                    <div class="text-xs font-semibold {{ $step === 3 ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500' }}">Bước 3</div>
                    <div class="text-xs font-medium text-slate-900 dark:text-white">Chiến lược Trùng lặp</div>
                </div>
            </li>
            <div class="hidden h-0.5 w-12 bg-slate-200 dark:bg-slate-800 sm:block"></div>

            <li class="flex items-center gap-3 {{ $step >= 4 ? '' : 'opacity-50' }}">
                <span class="flex size-8 items-center justify-center rounded-full {{ $step === 4 ? 'bg-indigo-600 text-white' : 'border border-slate-300 bg-slate-100 text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400' }} text-xs font-bold shadow-xs">4</span>
                <div>
                    <div class="text-xs font-semibold {{ $step === 4 ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500' }}">Bước 4</div>
                    <div class="text-xs font-medium text-slate-900 dark:text-white">Tiến trình Import</div>
                </div>
            </li>
        </ol>
    </nav>

    {{-- Main Content Wizard --}}
    @if ($step === 1)
        {{-- STEP 1 VIEW --}}
        <div class="crm-card space-y-6">
            @if ($preview === null)
                {{-- Upload Section --}}
                <div class="space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">1. Chọn tệp dữ liệu từ máy tính</h2>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Chấp nhận định dạng tệp .CSV, .TSV hoặc .TXT mã hóa UTF-8. Dung lượng tối đa 10MB.</p>
                    </div>

                    {{-- Dropzone Area --}}
                    <div
                        x-data="{ isDragging: false }"
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="isDragging = false"
                        class="relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed p-8 text-center transition-colors"
                        :class="isDragging ? 'border-indigo-500 bg-indigo-50/50 dark:border-indigo-400 dark:bg-indigo-950/20' : 'border-slate-300 hover:border-slate-400 dark:border-slate-700 dark:hover:border-slate-600 bg-slate-50/50 dark:bg-slate-900/50'"
                    >
                        <div class="flex size-12 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                            </svg>
                        </div>

                        <div class="mt-4 text-sm font-medium text-slate-900 dark:text-white">
                            <label for="file-upload" class="cursor-pointer font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300 focus-within:outline-hidden">
                                <span>Tải tệp lên</span>
                                <input id="file-upload" wire:model="importFile" type="file" class="sr-only" accept=".csv,.tsv,.txt">
                            </label>
                            <span> hoặc kéo thả tệp vào đây</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">MIME type CSV/TXT • Dung lượng tối đa 10MB</p>

                        <div wire:loading wire:target="importFile" class="mt-4 flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                            <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Đang xử lý tệp và trích xuất dữ liệu xem trước...</span>
                        </div>

                        @error('importFile')
                            <p class="mt-3 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @else
                {{-- Preview Section --}}
                <div class="space-y-6">
                    <div class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/60 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex size-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ $preview['original_filename'] }}</h3>
                                <div class="mt-0.5 flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                                    <span>Kích thước: <strong>{{ $preview['file_size_formatted'] }}</strong></span>
                                    <span>•</span>
                                    <span>Ước tính: <strong>{{ number_format($preview['total_rows_estimate']) }} dòng dữ liệu</strong></span>
                                    <span>•</span>
                                    <span>Phân cách: <code class="rounded bg-slate-200 px-1 py-0.5 text-xs text-slate-800 dark:bg-slate-800 dark:text-slate-200">{{ $preview['delimiter'] === "\t" ? 'TAB' : $preview['delimiter'] }}</code></span>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            wire:click="resetWizard"
                            class="self-start rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 sm:self-center"
                        >
                            Tải tệp khác
                        </button>
                    </div>

                    {{-- Preview Table --}}
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Xem trước 5 dòng dữ liệu đầu tiên</h3>
                            <span class="text-xs text-slate-500 dark:text-slate-400">Tổng số cột trích xuất: <strong>{{ count($preview['headers']) }}</strong></span>
                        </div>

                        <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800">
                            <table class="min-w-full divide-y divide-slate-200 text-left text-xs dark:divide-slate-800">
                                <thead class="bg-slate-100 font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                    <tr>
                                        <th class="px-3 py-2.5">#</th>
                                        @foreach ($preview['headers'] as $header)
                                            <th class="px-3 py-2.5 whitespace-nowrap">{{ $header }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white text-slate-800 dark:divide-slate-800 dark:bg-slate-900 dark:text-slate-300">
                                    @foreach ($preview['preview_rows'] as $index => $row)
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                            <td class="px-3 py-2 font-mono text-slate-400">{{ $index + 1 }}</td>
                                            @foreach ($preview['headers'] as $header)
                                                <td class="px-3 py-2 whitespace-nowrap max-w-64 truncate">
                                                    {{ $row[$header] ?? '' }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Action Bar --}}
                    <div class="flex items-center justify-between border-t border-slate-200 pt-4 dark:border-slate-800">
                        <button
                            type="button"
                            wire:click="resetWizard"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                        >
                            Hủy
                        </button>

                        <button
                            type="button"
                            wire:click="proceedToMapping"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                        >
                            <span>Tiếp tục: Cấu hình Cột (Mapping)</span>
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    @elseif ($step === 2)
        {{-- STEP 2 VIEW: COLUMN MAPPING & VALIDATION --}}
        <div class="crm-card space-y-6">
            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">2. Ghép nối Cột & Kiểm tra dữ liệu mẫu</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Chọn Cột từ tệp CSV tương ứng với từng trường thông tin Lead trong hệ thống CRM.</p>
            </div>

            @error('mapping')
                <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/50 dark:bg-rose-950/40">
                    <div class="flex items-center gap-3 text-rose-800 dark:text-rose-300">
                        <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                        <p class="text-xs font-semibold">{{ $message }}</p>
                    </div>
                </div>
            @enderror

            {{-- Grid Mapping Table & Validation Status --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {{-- Column Mapping Form (Left 2 cols) --}}
                <div class="space-y-4 lg:col-span-2">
                    <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800">
                        <table class="min-w-full divide-y divide-slate-200 text-left text-xs dark:divide-slate-800">
                            <thead class="bg-slate-100 font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                <tr>
                                    <th class="px-3.5 py-3">Trường dữ liệu CRM</th>
                                    <th class="px-3.5 py-3">Bắt buộc</th>
                                    <th class="px-3.5 py-3">Cột tương ứng trong File CSV</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white text-slate-800 dark:divide-slate-800 dark:bg-slate-900 dark:text-slate-300">
                                @foreach ($schema as $crmField => $fieldConfig)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                        <td class="px-3.5 py-3 font-medium">
                                            <span class="text-slate-900 dark:text-white">{{ $fieldConfig['label'] }}</span>
                                            <code class="ml-1 text-[10px] text-slate-400 font-mono">({{ $crmField }})</code>
                                        </td>
                                        <td class="px-3.5 py-3">
                                            @if ($fieldConfig['required'])
                                                <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-950/60 dark:text-amber-400">Định danh *</span>
                                            @else
                                                <span class="text-[10px] text-slate-400">Tùy chọn</span>
                                            @endif
                                        </td>
                                        <td class="px-3.5 py-2.5">
                                            <select
                                                wire:model.live="mapping.{{ $crmField }}"
                                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-900 focus:border-indigo-500 focus:outline-hidden focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                            >
                                                <option value="">-- Bỏ qua (Không nhập) --</option>
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
                </div>

                {{-- Dry-run Validation Report Card (Right col) --}}
                <div class="space-y-4">
                    <div class="crm-card space-y-4 bg-slate-50/70 dark:bg-slate-900/40">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-3 dark:border-slate-800">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Báo cáo Kiểm tra dữ liệu</h3>
                            <button
                                type="button"
                                wire:click="runValidation"
                                class="text-xs text-indigo-600 hover:underline dark:text-indigo-400"
                            >
                                Kiểm tra lại
                            </button>
                        </div>

                        @if ($validationResult !== null)
                            <div class="space-y-3">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-500 dark:text-slate-400">Dòng mẫu kiểm tra:</span>
                                    <strong class="text-slate-900 dark:text-white">{{ $validationResult['total_checked'] }} dòng</strong>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-500 dark:text-slate-400">Dòng hợp lệ:</span>
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">{{ $validationResult['valid_count'] }} dòng</span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-500 dark:text-slate-400">Cảnh báo / Lỗi:</span>
                                    <span class="rounded-full {{ $validationResult['warning_count'] > 0 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }} px-2 py-0.5 font-bold">
                                        {{ $validationResult['warning_count'] }} dòng
                                    </span>
                                </div>

                                @if ($validationResult['row_errors'] !== [])
                                    <div class="mt-4 space-y-2 border-t border-slate-200 pt-3 dark:border-slate-800">
                                        <div class="text-xs font-semibold text-amber-800 dark:text-amber-400">Chi tiết cảnh báo mẫu:</div>
                                        <div class="max-h-48 overflow-y-auto space-y-1.5 pr-1">
                                            @foreach ($validationResult['row_errors'] as $error)
                                                <div class="rounded border border-amber-200 bg-amber-50 p-2 text-[11px] text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-300">
                                                    <strong>Dòng {{ $error['row'] }}:</strong> {{ $error['message'] }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @else
                            <p class="text-xs text-slate-500">Vui lòng ánh xạ ít nhất 1 trường định danh chính để xem báo cáo kiểm tra.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Step 2 Action Bar --}}
            <div class="flex items-center justify-between border-t border-slate-200 pt-4 dark:border-slate-800">
                <button
                    type="button"
                    wire:click="backToUpload"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                >
                    Quay lại Step 1
                </button>

                <button
                    type="button"
                    wire:click="proceedToDuplicates"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                >
                    <span>Tiếp tục: Chiến lược Trùng lặp (Step 3)</span>
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </button>
            </div>
        </div>
    @elseif ($step === 3)
        {{-- STEP 3 VIEW: DUPLICATE STRATEGY & START IMPORT --}}
        <div class="crm-card space-y-6">
            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">3. Cấu hình Chiến lược xử lý trùng lặp</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Chọn cách hệ thống xử lý khi phát hiện thông tin trùng lặp (Email hoặc Số điện thoại) với dữ liệu sẵn có.</p>
            </div>

            {{-- Strategy Radio Options --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <label
                    class="relative flex cursor-pointer flex-col rounded-xl border p-4 shadow-xs transition-colors"
                    :class="$wire.duplicateStrategy === 'skip' ? 'border-indigo-600 bg-indigo-50/50 dark:border-indigo-500 dark:bg-indigo-950/30 ring-2 ring-indigo-600' : 'border-slate-200 hover:border-slate-300 dark:border-slate-800 dark:hover:border-slate-700'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-900 dark:text-white">Bỏ qua (Skip)</span>
                        <input type="radio" wire:model.live="duplicateStrategy" value="skip" class="size-4 text-indigo-600">
                    </div>
                    <span class="mt-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400">Khuyên dùng</span>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Bỏ qua dòng trong tệp nếu tìm thấy Lead đã tồn tại cùng Email hoặc Số điện thoại. Giữ nguyên dữ liệu hiện tại.</p>
                </label>

                <label
                    class="relative flex cursor-pointer flex-col rounded-xl border p-4 shadow-xs transition-colors"
                    :class="$wire.duplicateStrategy === 'update' ? 'border-indigo-600 bg-indigo-50/50 dark:border-indigo-500 dark:bg-indigo-950/30 ring-2 ring-indigo-600' : 'border-slate-200 hover:border-slate-300 dark:border-slate-800 dark:hover:border-slate-700'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-900 dark:text-white">Cập nhật (Update)</span>
                        <input type="radio" wire:model.live="duplicateStrategy" value="update" class="size-4 text-indigo-600">
                    </div>
                    <span class="mt-2 text-xs font-semibold text-amber-600 dark:text-amber-400">Ghi đè thông tin</span>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Cập nhật các trường thông tin mới từ tệp CSV vào record Lead sẵn có trong CRM.</p>
                </label>

                <label
                    class="relative flex cursor-pointer flex-col rounded-xl border p-4 shadow-xs transition-colors"
                    :class="$wire.duplicateStrategy === 'create_new' ? 'border-indigo-600 bg-indigo-50/50 dark:border-indigo-500 dark:bg-indigo-950/30 ring-2 ring-indigo-600' : 'border-slate-200 hover:border-slate-300 dark:border-slate-800 dark:hover:border-slate-700'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-900 dark:text-white">Tạo mới (Create New)</span>
                        <input type="radio" wire:model.live="duplicateStrategy" value="create_new" class="size-4 text-indigo-600">
                    </div>
                    <span class="mt-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400">Luôn thêm mới</span>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Bỏ qua kiểm tra trùng lặp, luôn luôn tạo một record Lead mới cho mỗi dòng dữ liệu.</p>
                </label>
            </div>

            {{-- Summary Execution Box --}}
            <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Tóm tắt thông số Import</h3>
                <dl class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-3 text-xs">
                    <div>
                        <dt class="text-slate-500">Tệp nguồn:</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $preview['original_filename'] ?? '' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Tổng số dòng ước tính:</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ number_format($preview['total_rows_estimate'] ?? 0) }} dòng</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Người chịu trách nhiệm sở hữu:</dt>
                        <dd class="font-semibold text-indigo-600 dark:text-indigo-400">{{ auth()->user()->name }} (Self-assigned)</dd>
                    </div>
                </dl>
            </div>

            {{-- Step 3 Action Bar --}}
            <div class="flex items-center justify-between border-t border-slate-200 pt-4 dark:border-slate-800">
                <button
                    type="button"
                    wire:click="backToMapping"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                >
                    Quay lại Step 2
                </button>

                <button
                    type="button"
                    wire:click="startImport"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white shadow-md hover:bg-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:bg-emerald-500 dark:hover:bg-emerald-400"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                    </svg>
                    <span>Bắt đầu Nhập dữ liệu (Start Queue)</span>
                </button>
            </div>
        </div>
    @elseif ($step === 4)
        @php
            $isProcessing = $currentBatch !== null && in_array($currentBatch->status, ['pending', 'processing'], true);
            $total = $currentBatch->total_rows ?? 0;
            $processed = $currentBatch->processed_rows ?? 0;
            $percentage = $total > 0 ? min(100, (int) round(($processed / $total) * 100)) : ($isProcessing ? 50 : 100);
        @endphp

        <div
            @if ($isProcessing) wire:poll.1000ms @endif
            class="crm-card space-y-6"
        >
            {{-- Header status banner --}}
            <div class="flex flex-col items-center text-center space-y-3 py-4">
                @if ($isProcessing)
                    <div class="flex size-14 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                        <svg class="size-7 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Đang xử lý nhập dữ liệu vào hệ thống...</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Tệp dữ liệu đang được hàng đợi Queue xử lý bất đồng bộ. Hệ thống sẽ tự động cập nhật tiến trình.</p>
                @else
                    <div class="flex size-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                        <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Tiến trình Import đã hoàn thành!</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Toàn bộ các dòng dữ liệu trong tệp đã được xử lý xong. Mã đợt Import: <strong>#{{ $currentBatch->id ?? $batchId }}</strong>.</p>
                @endif
            </div>

            {{-- Progress Bar --}}
            <div class="space-y-2">
                <div class="flex justify-between text-xs font-semibold">
                    <span class="text-slate-700 dark:text-slate-300">Tiến độ thực thi Queue:</span>
                    <span class="text-indigo-600 dark:text-indigo-400">{{ $percentage }}%</span>
                </div>
                <div class="h-3 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                    <div
                        class="h-full transition-all duration-500 {{ $percentage === 100 ? 'bg-emerald-500' : 'bg-indigo-600' }}"
                        style="width: {{ $percentage }}%"
                    ></div>
                </div>
                <div class="flex justify-between text-[11px] text-slate-500">
                    <span>Đã xử lý: <strong>{{ number_format($processed) }}</strong> / <strong>{{ number_format($total) }} dòng</strong></span>
                    <span>Trạng thái Batch: <strong class="uppercase text-indigo-600">{{ $currentBatch->status ?? 'processing' }}</strong></span>
                </div>
            </div>

            {{-- 4 Stat Cards --}}
            @if ($currentBatch !== null)
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-center dark:border-slate-800 dark:bg-slate-900/50">
                        <div class="text-xs font-medium text-slate-500">Tổng số dòng</div>
                        <div class="mt-1 text-xl font-extrabold text-slate-900 dark:text-white">{{ number_format($currentBatch->total_rows) }}</div>
                    </div>

                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 text-center dark:border-emerald-950/60 dark:bg-emerald-950/20">
                        <div class="text-xs font-medium text-emerald-700 dark:text-emerald-400">Thành công</div>
                        <div class="mt-1 text-xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ number_format($currentBatch->successful_rows) }}</div>
                    </div>

                    <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-4 text-center dark:border-amber-950/60 dark:bg-amber-950/20">
                        <div class="text-xs font-medium text-amber-700 dark:text-amber-400">Bỏ qua (Trùng)</div>
                        <div class="mt-1 text-xl font-extrabold text-amber-600 dark:text-amber-400">{{ number_format($currentBatch->skipped_rows) }}</div>
                    </div>

                    <div class="rounded-xl border border-rose-200 bg-rose-50/50 p-4 text-center dark:border-rose-950/60 dark:bg-rose-950/20">
                        <div class="text-xs font-medium text-rose-700 dark:text-rose-400">Thất bại (Lỗi)</div>
                        <div class="mt-1 text-xl font-extrabold text-rose-600 dark:text-rose-400">{{ number_format($currentBatch->failed_rows) }}</div>
                    </div>
                </div>

                {{-- Download Error File & Error Details --}}
                @if ($currentBatch->failed_rows > 0 || ($currentBatch->error_log !== null && $currentBatch->error_log !== []))
                    <div class="rounded-xl border border-rose-200 bg-rose-50/50 p-5 dark:border-rose-900/50 dark:bg-rose-950/30 space-y-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-rose-900 dark:text-rose-200">Phát hiện {{ $currentBatch->failed_rows }} dòng gặp lỗi khi xử lý</h3>
                                <p class="mt-0.5 text-xs text-rose-700 dark:text-rose-300">Tải tệp báo cáo lỗi CSV để xem chi tiết lý do nguyên nhân từng dòng và thực hiện chỉnh sửa.</p>
                            </div>

                            <button
                                type="button"
                                wire:click="downloadErrorFile"
                                class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-rose-500 focus:outline-hidden focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 dark:bg-rose-500 dark:hover:bg-rose-400"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                Tải tệp báo cáo lỗi (.CSV)
                            </button>
                        </div>

                        @if ($currentBatch->error_log !== null && $currentBatch->error_log !== [])
                            <div class="space-y-2 border-t border-rose-200 pt-3 dark:border-rose-900/50">
                                <div class="text-xs font-semibold text-rose-900 dark:text-rose-300">Chi tiết lỗi mẫu (Tối đa 10 dòng đầu):</div>
                                <div class="max-h-48 overflow-y-auto space-y-1.5 pr-1">
                                    @foreach (array_slice($currentBatch->error_log, 0, 10) as $err)
                                        <div class="rounded border border-rose-200 bg-white p-2 text-xs text-rose-900 dark:border-rose-900/40 dark:bg-slate-900 dark:text-rose-300">
                                            <strong>Dòng {{ $err['row'] ?? 'N/A' }}:</strong> {{ $err['error'] ?? 'Lỗi không xác định' }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            @endif

            {{-- Action buttons --}}
            <div class="flex items-center justify-between border-t border-slate-200 pt-4 dark:border-slate-800">
                <button
                    type="button"
                    wire:click="resetWizard"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                >
                    Nhập tệp khác
                </button>

                <a
                    href="{{ route('leads.index') }}"
                    wire:navigate
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                >
                    <span>Về danh sách Lead</span>
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            </div>
        </div>
    @endif
</div>
