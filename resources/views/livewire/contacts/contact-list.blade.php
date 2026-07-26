<div>
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">CRM / Bán hàng</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">Người liên hệ</h1>
            <p class="mt-2 max-w-3xl text-slate-500">Quản lý danh sách các cá nhân đại diện thuộc các doanh nghiệp khách hàng và đối tác.</p>
        </div>
        <div class="flex items-center gap-2">
            <flux:badge color="emerald">{{ $this->contacts->total() }} Người liên hệ trong phạm vi</flux:badge>
            @can('create', App\Models\Contact::class)
                <flux:button :href="route('contacts.create')" wire:navigate variant="primary" icon="plus">Tạo mới Người liên hệ</flux:button>
            @endcan
        </div>
    </div>

    <section class="crm-card relative" aria-labelledby="contact-list-title">
        <div class="mb-5">
            <div class="data-list-heading">
                <div>
                    <h2 id="contact-list-title" class="font-semibold">Danh sách Người liên hệ</h2>
                    <p class="mt-1 text-sm text-slate-500">Tìm thấy {{ $this->contacts->total() }} người liên hệ phù hợp.</p>
                </div>
                @if ($search !== '' || $company !== 'all' || $primary !== 'all' || $owner !== 'all')
                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="resetFilters">Xóa bộ lọc</flux:button>
                @endif
            </div>

            <div class="data-list-filters">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    label="Tìm kiếm"
                    placeholder="Họ tên, email, điện thoại, chức danh..."
                />

                <flux:select wire:model.live="company" label="Doanh nghiệp">
                    <option value="all">Tất cả doanh nghiệp</option>
                    @foreach ($this->companies as $companyOption)
                        <option value="{{ $companyOption->id }}">{{ $companyOption->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="primary" label="Đại diện chính">
                    <option value="all">Tất cả liên hệ</option>
                    <option value="yes">Chỉ Đại diện chính</option>
                    <option value="no">Liên hệ thường</option>
                </flux:select>

                <flux:select wire:model.live="owner" label="Người phụ trách">
                    <option value="all">Tất cả người phụ trách</option>
                    @foreach ($this->users as $userOption)
                        <option value="{{ $userOption->id }}">{{ $userOption->name }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <div class="data-list-content">
            <x-data-list.loading target="search,company,primary,owner,sort,direction,perPage,resetFilters,gotoPage,nextPage,previousPage" />

            @if ($this->contacts->isEmpty())
                <x-data-list.empty
                    title="Không tìm thấy người liên hệ"
                    description="Thử thay đổi từ khóa hoặc các bộ lọc hiện tại."
                    icon="user-group"
                >
                    @if ($search !== '' || $company !== 'all' || $primary !== 'all' || $owner !== 'all')
                        <x-slot:action>
                            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="resetFilters">Đặt lại bộ lọc</flux:button>
                        </x-slot:action>
                    @endif
                </x-data-list.empty>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column sortable :sorted="$sort === 'full_name'" :direction="$sort === 'full_name' ? $direction : null" wire:click="sortBy('full_name')">Họ và tên</flux:table.column>
                        <flux:table.column>Doanh nghiệp</flux:table.column>
                        <flux:table.column>Chức danh / Phòng ban</flux:table.column>
                        <flux:table.column>Thông tin liên hệ</flux:table.column>
                        <flux:table.column>Người phụ trách</flux:table.column>
                        <flux:table.column sortable :sorted="$sort === 'created_at'" :direction="$sort === 'created_at' ? $direction : null" wire:click="sortBy('created_at')">Ngày tạo</flux:table.column>
                        <flux:table.column align="end">Thao tác</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->contacts as $contact)
                            <flux:table.row :key="$contact->id">
                                <flux:table.cell variant="strong">
                                    <div class="flex min-w-48 items-center gap-2">
                                        <a href="{{ route('contacts.show', $contact) }}" wire:navigate class="hover:text-emerald-600 dark:hover:text-emerald-400">
                                            {{ $contact->full_name }}
                                        </a>
                                        @if ($contact->is_primary)
                                            <flux:badge color="emerald" size="sm">Chính</flux:badge>
                                        @endif
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($contact->company)
                                        <a href="{{ route('companies.show', $contact->company) }}" wire:navigate class="text-emerald-600 hover:underline dark:text-emerald-400">
                                            {{ $contact->company->name }}
                                        </a>
                                    @else
                                        <span class="font-normal text-slate-400">Cá nhân tự do</span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div>{{ $contact->job_title ?: '—' }}</div>
                                    @if ($contact->department_name)
                                        <div class="mt-1 text-xs font-normal text-slate-400">{{ $contact->department_name }}</div>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="min-w-48 text-xs">
                                        @if ($contact->phone)
                                            <div>{{ $contact->phone }}</div>
                                        @endif
                                        @if ($contact->email)
                                            <div class="mt-1 max-w-xs truncate text-slate-400">{{ $contact->email }}</div>
                                        @endif
                                        @if (! $contact->phone && ! $contact->email)
                                            —
                                        @endif
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>{{ $contact->owner?->name ?: 'Chưa phân công' }}</flux:table.cell>
                                <flux:table.cell>
                                    <span class="text-xs text-slate-500">{{ $contact->created_at?->timezone(config('crm.display_timezone'))->format('d/m/Y H:i') }}</span>
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <div class="flex justify-end gap-1">
                                        <flux:button :href="route('contacts.show', $contact)" wire:navigate size="sm" variant="ghost">Xem</flux:button>
                                        @can('update', $contact)
                                            <flux:button :href="route('contacts.edit', $contact)" wire:navigate size="sm" variant="ghost">Sửa</flux:button>
                                        @endcan
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <x-data-list.pagination :paginator="$this->contacts" />
            @endif
        </div>
    </section>
</div>
