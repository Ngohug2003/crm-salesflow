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
            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">Tải lên tệp CSV/TSV để thêm hàng loạt khách hàng tiềm năng vào hệ thống CRM.</p>
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
            <li class="flex items-center gap-3">
                <span class="flex size-8 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white shadow-xs">1</span>
                <div>
                    <div class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">Bước 1</div>
                    <div class="text-xs font-medium text-slate-900 dark:text-white">Upload & Xem trước</div>
                </div>
            </li>
            <div class="hidden h-0.5 w-12 bg-slate-200 dark:bg-slate-800 sm:block"></div>
            <li class="flex items-center gap-3 opacity-50">
                <span class="flex size-8 items-center justify-center rounded-full border border-slate-300 bg-slate-100 text-xs font-bold text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">2</span>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Bước 2</div>
                    <div class="text-xs font-medium text-slate-700 dark:text-slate-300">Cấu hình Cột (Mapping)</div>
                </div>
            </li>
            <div class="hidden h-0.5 w-12 bg-slate-200 dark:bg-slate-800 sm:block"></div>
            <li class="flex items-center gap-3 opacity-50">
                <span class="flex size-8 items-center justify-center rounded-full border border-slate-300 bg-slate-100 text-xs font-bold text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">3</span>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Bước 3</div>
                    <div class="text-xs font-medium text-slate-700 dark:text-slate-300">Xử lý Trùng lặp & Queue</div>
                </div>
            </li>
            <div class="hidden h-0.5 w-12 bg-slate-200 dark:bg-slate-800 sm:block"></div>
            <li class="flex items-center gap-3 opacity-50">
                <span class="flex size-8 items-center justify-center rounded-full border border-slate-300 bg-slate-100 text-xs font-bold text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">4</span>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Bước 4</div>
                    <div class="text-xs font-medium text-slate-700 dark:text-slate-300">Hoàn thành</div>
                </div>
            </li>
        </ol>
    </nav>

    {{-- Main Wizard Card --}}
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
</div>
