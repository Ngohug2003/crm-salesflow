<div>
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">Cài đặt / Phân quyền & Vai trò</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">Ma trận phân quyền (Permission Matrix)</h1>
            <p class="mt-2 max-w-3xl text-slate-500">Tra cứu chi tiết các quyền hạn được gán cho từng vai trò và phạm vi dữ liệu (Data Scope) tương ứng.</p>
        </div>
    </div>

    <section class="crm-card mb-8" aria-labelledby="roles-summary-title">
        <h2 id="roles-summary-title" class="text-base font-semibold text-slate-950 dark:text-white">Danh mục vai trò & Phạm vi dữ liệu (Data Scope)</h2>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Các quy tắc giới hạn phạm vi truy cập dữ liệu được áp dụng tự động cho từng vai trò.</p>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            @foreach ($roles as $role)
                <div class="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-950 dark:text-white">{{ $role['label'] }}</span>
                        <flux:badge color="blue" size="sm">{{ $role['name'] }}</flux:badge>
                    </div>
                    <p class="mt-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400">{{ $role['data_scope'] }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ $role['description'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="crm-card">
        <div class="mb-5 grid gap-4 md:grid-cols-2 xl:grid-cols-12">
            <div class="md:col-span-2 xl:col-span-8">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    label="Tìm kiếm quyền hạn"
                    placeholder="Nhập tên quyền (VD: leads.view, opportunities.create)..."
                />
            </div>
            <div class="xl:col-span-4">
                <x-forms.smart-select wire:model.live="module" label="Lọc theo phân hệ">
                    <option value="all">Tất cả phân hệ (7 phân hệ)</option>
                    @foreach ($allModuleKeys as $label => $key)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </x-forms.smart-select>
            </div>
        </div>

        @if ($search !== '' || $module !== 'all')
            <div class="mb-4 flex items-center justify-between border-y border-slate-200 py-2.5 text-xs text-slate-500 dark:border-slate-800">
                <span>Đang áp dụng bộ lọc ma trận</span>
                <button type="button" wire:click="clearFilters" class="font-medium text-blue-600 hover:underline dark:text-blue-400">Xóa bộ lọc</button>
            </div>
        @endif

        @if ($modules === [])
            <div class="grid min-h-48 place-items-center rounded-xl border border-dashed border-slate-300 text-center dark:border-slate-700">
                <div class="px-6">
                    <p class="font-medium">Không tìm thấy quyền hạn phù hợp</p>
                    <p class="mt-1 text-sm text-slate-500">Thử xóa từ khóa tìm kiếm hoặc chọn lại phân hệ.</p>
                </div>
            </div>
        @else
            <div class="space-y-6">
                @foreach ($modules as $m)
                    <div wire:key="module-{{ $m['key'] }}" class="overflow-hidden rounded-lg border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950/50">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $m['label'] }}</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="border-b border-slate-200 bg-slate-100/50 text-slate-500 dark:border-slate-800 dark:bg-slate-900/50 dark:text-slate-400">
                                    <tr>
                                        <th class="py-2.5 pl-4 pr-2 font-medium">Mã quyền hạn (Permission Key)</th>
                                        <th class="px-2 py-2.5 font-medium">Mô tả quyền hạn</th>
                                        @foreach ($roles as $role)
                                            <th class="px-2 py-2.5 text-center font-medium min-w-28">{{ $role['label'] }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                    @foreach ($m['permissions'] as $p)
                                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50">
                                            <td class="py-2.5 pl-4 pr-2 font-mono text-slate-900 dark:text-white whitespace-nowrap">{{ $p['name'] }}</td>
                                            <td class="px-2 py-2.5 text-slate-600 dark:text-slate-300 whitespace-nowrap">{{ $p['label'] }}</td>
                                            @foreach ($roles as $role)
                                                @php $hasPerm = $p['roles'][$role['name']] ?? false; @endphp
                                                <td class="px-2 py-2.5 text-center whitespace-nowrap">
                                                    @if ($hasPerm)
                                                        <span class="inline-flex items-center justify-center rounded-full bg-emerald-100 p-1 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400" title="Có quyền">
                                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                            </svg>
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center justify-center rounded-full bg-slate-100 p-1 text-slate-300 dark:bg-slate-800 dark:text-slate-600" title="Không có quyền">
                                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                            </svg>
                                                        </span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
