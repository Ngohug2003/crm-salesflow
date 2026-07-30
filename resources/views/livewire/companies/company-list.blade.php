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
        <div class="mb-5">
            <div class="data-list-heading">
                <div>
                    <h2 id="company-list-title" class="font-semibold">Danh sách Doanh nghiệp</h2>
                    <p class="mt-1 text-sm text-slate-500">Tìm thấy {{ $this->companies->total() }} doanh nghiệp phù hợp.</p>
                </div>
                @if ($search !== '' || $industry !== 'all' || $size !== 'all' || $owner !== 'all' || $department !== 'all')
                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="resetFilters">Xóa bộ lọc</flux:button>
                @endif
            </div>

            <div class="data-list-filters">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    label="Tìm kiếm"
                    placeholder="Tên doanh nghiệp, MST, email, phone, ngành..."
                />

                <x-forms.smart-select wire:model.live="industry" label="Ngành nghề">
                    <option value="all">Tất cả ngành nghề</option>
                    @foreach ($this->industries as $industryOption)
                        <option value="{{ $industryOption }}">{{ $industryOption }}</option>
                    @endforeach
                </x-forms.smart-select>

                <x-forms.smart-select wire:model.live="size" label="Quy mô">
                    <option value="all">Tất cả quy mô</option>
                    @foreach ($this->sizes as $sizeOption)
                        <option value="{{ $sizeOption }}">{{ $sizeOption }}</option>
                    @endforeach
                </x-forms.smart-select>

                <x-forms.smart-select wire:model.live="owner" label="Người phụ trách">
                    <option value="all">Tất cả người phụ trách</option>
                    @foreach ($this->users as $userOption)
                        <option value="{{ $userOption->id }}">{{ $userOption->name }}</option>
                    @endforeach
                </x-forms.smart-select>
            </div>
        </div>

        <div class="data-list-content">
            <x-data-list.loading target="search,industry,size,owner,department,sort,direction,perPage,resetFilters,gotoPage,nextPage,previousPage" />

            @if ($this->companies->isEmpty())
                <x-data-list.empty
                    title="Không tìm thấy doanh nghiệp"
                    description="Thử thay đổi từ khóa hoặc các bộ lọc hiện tại."
                    icon="building-office-2"
                >
                    @if ($search !== '' || $industry !== 'all' || $size !== 'all' || $owner !== 'all' || $department !== 'all')
                        <x-slot:action>
                            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="resetFilters">Đặt lại bộ lọc</flux:button>
                        </x-slot:action>
                    @endif
                </x-data-list.empty>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column sortable :sorted="$sort === 'name'" :direction="$sort === 'name' ? $direction : null" wire:click="sortBy('name')">Doanh nghiệp</flux:table.column>
                        <flux:table.column>Mã số thuế</flux:table.column>
                        <flux:table.column>Ngành nghề / Quy mô</flux:table.column>
                        <flux:table.column>Thông tin liên hệ</flux:table.column>
                        <flux:table.column>Người phụ trách</flux:table.column>
                        <flux:table.column sortable :sorted="$sort === 'created_at'" :direction="$sort === 'created_at' ? $direction : null" wire:click="sortBy('created_at')">Ngày tạo</flux:table.column>
                        <flux:table.column align="end">Thao tác</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->companies as $company)
                            <flux:table.row :key="$company->id">
                                <flux:table.cell variant="strong">
                                    <div class="min-w-56">
                                        <a href="{{ route('companies.show', $company) }}" wire:navigate class="hover:text-emerald-600 dark:hover:text-emerald-400">
                                            {{ $company->name }}
                                        </a>
                                        @if ($company->website)
                                            <div class="mt-1 max-w-xs truncate text-xs font-normal text-slate-400">
                                                {{ parse_url($company->website, PHP_URL_HOST) ?? $company->website }}
                                            </div>
                                        @endif
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell><span class="font-mono text-xs">{{ $company->tax_code ?: '—' }}</span></flux:table.cell>
                                <flux:table.cell>
                                    <div>{{ $company->industry ?: '—' }}</div>
                                    @if ($company->company_size)
                                        <div class="mt-1 text-xs font-normal text-slate-400">{{ $company->company_size }}</div>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="min-w-48 text-xs">
                                        @if ($company->phone)
                                            <div>{{ $company->phone }}</div>
                                        @endif
                                        @if ($company->email)
                                            <div class="mt-1 max-w-xs truncate text-slate-400">{{ $company->email }}</div>
                                        @endif
                                        @if (! $company->phone && ! $company->email)
                                            —
                                        @endif
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>{{ $company->owner?->name ?: 'Chưa phân công' }}</flux:table.cell>
                                <flux:table.cell>
                                    <span class="text-xs text-slate-500">{{ $company->created_at?->timezone(config('crm.display_timezone'))->format('d/m/Y H:i') }}</span>
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <div class="flex justify-end gap-1">
                                        <flux:button :href="route('companies.show', $company)" wire:navigate size="sm" variant="ghost">Xem</flux:button>
                                        @can('update', $company)
                                            <flux:button :href="route('companies.edit', $company)" wire:navigate size="sm" variant="ghost">Sửa</flux:button>
                                        @endcan
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <x-data-list.pagination :paginator="$this->companies" />
            @endif
        </div>
    </section>
</div>
