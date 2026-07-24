<div>
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">CRM / Bán hàng</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">Doanh nghiệp</h1>
            <p class="mt-2 max-w-3xl text-slate-500">Quản lý hồ sơ doanh nghiệp khách hàng, đối tác và phân công trách nhiệm trong hệ thống.</p>
        </div>
        <div class="flex items-center gap-2">
            <flux:badge color="emerald">{{ $this->companies->total() }} Doanh nghiệp trong phạm vi</flux:badge>
            @can('create', App\Models\Company::class)
                <flux:button :href="route('companies.create')" wire:navigate variant="primary" icon="plus">Tạo mới Doanh nghiệp</flux:button>
            @endcan
        </div>
    </div>

    <section class="crm-card relative" aria-labelledby="company-list-title">
        <div
            wire:loading.flex
            wire:target="search,industry,size,owner,department,sort,direction,perPage,resetFilters,gotoPage,nextPage,previousPage"
            class="absolute inset-x-5 top-3 z-10 hidden items-center justify-end gap-2 text-xs font-medium text-emerald-700 dark:text-emerald-300"
            role="status"
        >
            <span class="size-2 animate-pulse rounded-full bg-emerald-500"></span>
            Đang tải dữ liệu…
        </div>

        <div class="mb-5">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                <div>
                    <h2 id="company-list-title" class="font-semibold">Danh sách Doanh nghiệp</h2>
                    <p class="mt-1 text-sm text-slate-500">Tìm thấy {{ $this->companies->total() }} doanh nghiệp phù hợp.</p>
                </div>
                @if ($search !== '' || $industry !== 'all' || $size !== 'all' || $owner !== 'all' || $department !== 'all')
                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="resetFilters">Xóa bộ lọc</flux:button>
                @endif
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    label="Tìm kiếm"
                    placeholder="Tên doanh nghiệp, MST, email, phone, ngành..."
                />

                <flux:select wire:model.live="industry" label="Ngành nghề">
                    <option value="all">Tất cả ngành nghề</option>
                    @foreach ($this->industries as $ind)
                        <option value="{{ $ind }}">{{ $ind }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="size" label="Quy mô">
                    <option value="all">Tất cả quy mô</option>
                    @foreach ($this->sizes as $s)
                        <option value="{{ $s }}">{{ $s }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="owner" label="Người phụ trách">
                    <option value="all">Tất cả người phụ trách</option>
                    @foreach ($this->users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm font-normal text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                    <tr>
                        <th scope="col" class="px-4 py-3">
                            <button wire:click="sortBy('name')" type="button" class="group inline-flex items-center gap-1 font-semibold">
                                Doanh nghiệp
                                <flux:icon.arrows-up-down class="size-3 text-slate-400 group-hover:text-slate-600" />
                            </button>
                        </th>
                        <th scope="col" class="px-4 py-3">Mã số thuế</th>
                        <th scope="col" class="px-4 py-3">Ngành nghề & Quy mô</th>
                        <th scope="col" class="px-4 py-3">Thông tin liên hệ</th>
                        <th scope="col" class="px-4 py-3">Người phụ trách</th>
                        <th scope="col" class="px-4 py-3">
                            <button wire:click="sortBy('created_at')" type="button" class="group inline-flex items-center gap-1 font-semibold">
                                Ngày tạo
                                <flux:icon.arrows-up-down class="size-3 text-slate-400 group-hover:text-slate-600" />
                            </button>
                        </th>
                        <th scope="col" class="px-4 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($this->companies as $company)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">
                                <a href="{{ route('companies.show', $company) }}" wire:navigate class="hover:text-emerald-600 dark:hover:text-emerald-400">
                                    {{ $company->name }}
                                </a>
                                @if ($company->website)
                                    <div class="mt-0.5 text-xs text-slate-400 font-normal truncate max-w-xs">
                                        {{ parse_url($company->website, PHP_URL_HOST) ?? $company->website }}
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs">
                                {{ $company->tax_code ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <div>{{ $company->industry ?: '—' }}</div>
                                @if ($company->company_size)
                                    <div class="text-slate-400 font-normal">{{ $company->company_size }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if ($company->phone)
                                    <div>📞 {{ $company->phone }}</div>
                                @endif
                                @if ($company->email)
                                    <div class="text-slate-400 truncate max-w-xs">✉️ {{ $company->email }}</div>
                                @endif
                                @if (! $company->phone && ! $company->email)
                                    —
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs font-medium">
                                {{ $company->owner?->name ?: 'Chưa phân công' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-400">
                                {{ $company->created_at?->format('d/m/Y H:i') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-xs font-medium">
                                <div class="flex justify-end gap-2">
                                    <flux:button :href="route('companies.show', $company)" wire:navigate size="sm" variant="ghost">Xem</flux:button>
                                    @can('update', $company)
                                        <flux:button :href="route('companies.edit', $company)" wire:navigate size="sm" variant="subtle">Sửa</flux:button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                Chưa có doanh nghiệp nào trong hệ thống hoặc phù hợp với bộ lọc hiện tại.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->companies->hasPages())
            <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-800">
                {{ $this->companies->links() }}
            </div>
        @endif
    </section>
</div>
