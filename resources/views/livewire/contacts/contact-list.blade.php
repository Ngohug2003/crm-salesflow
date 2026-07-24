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
        <div
            wire:loading.flex
            wire:target="search,company,primary,owner,sort,direction,perPage,resetFilters,gotoPage,nextPage,previousPage"
            class="absolute inset-x-5 top-3 z-10 hidden items-center justify-end gap-2 text-xs font-medium text-emerald-700 dark:text-emerald-300"
            role="status"
        >
            <span class="size-2 animate-pulse rounded-full bg-emerald-500"></span>
            Đang tải dữ liệu…
        </div>

        <div class="mb-5">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                <div>
                    <h2 id="contact-list-title" class="font-semibold">Danh sách Người liên hệ</h2>
                    <p class="mt-1 text-sm text-slate-500">Tìm thấy {{ $this->contacts->total() }} người liên hệ phù hợp.</p>
                </div>
                @if ($search !== '' || $company !== 'all' || $primary !== 'all' || $owner !== 'all')
                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="resetFilters">Xóa bộ lọc</flux:button>
                @endif
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    label="Tìm kiếm"
                    placeholder="Họ tên, email, điện thoại, chức danh..."
                />

                <flux:select wire:model.live="company" label="Doanh nghiệp">
                    <option value="all">Tất cả doanh nghiệp</option>
                    @foreach ($this->companies as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="primary" label="Đại diện chính">
                    <option value="all">Tất cả liên hệ</option>
                    <option value="yes">Chỉ Đại diện chính</option>
                    <option value="no">Liên hệ thường</option>
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
                            <button wire:click="sortBy('full_name')" type="button" class="group inline-flex items-center gap-1 font-semibold">
                                Họ và tên
                                <flux:icon.arrows-up-down class="size-3 text-slate-400 group-hover:text-slate-600" />
                            </button>
                        </th>
                        <th scope="col" class="px-4 py-3">Doanh nghiệp</th>
                        <th scope="col" class="px-4 py-3">Chức danh & Phòng ban</th>
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
                    @forelse ($this->contacts as $contact)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('contacts.show', $contact) }}" wire:navigate class="hover:text-emerald-600 dark:hover:text-emerald-400">
                                        {{ $contact->full_name }}
                                    </a>
                                    @if ($contact->is_primary)
                                        <flux:badge color="emerald" size="sm">Chính</flux:badge>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs font-medium">
                                @if ($contact->company)
                                    <a href="{{ route('companies.show', $contact->company) }}" wire:navigate class="hover:underline text-emerald-600 dark:text-emerald-400">
                                        {{ $contact->company->name }}
                                    </a>
                                @else
                                    <span class="text-slate-400 font-normal">Cá nhân tự do</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <div>{{ $contact->job_title ?: '—' }}</div>
                                @if ($contact->department_name)
                                    <div class="text-slate-400 font-normal">{{ $contact->department_name }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if ($contact->phone)
                                    <div>📞 {{ $contact->phone }}</div>
                                @endif
                                @if ($contact->email)
                                    <div class="text-slate-400 truncate max-w-xs">✉️ {{ $contact->email }}</div>
                                @endif
                                @if (! $contact->phone && ! $contact->email)
                                    —
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs font-medium">
                                {{ $contact->owner?->name ?: 'Chưa phân công' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-400">
                                {{ $contact->created_at?->format('d/m/Y H:i') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-xs font-medium">
                                <div class="flex justify-end gap-2">
                                    <flux:button :href="route('contacts.show', $contact)" wire:navigate size="sm" variant="ghost">Xem</flux:button>
                                    @can('update', $contact)
                                        <flux:button :href="route('contacts.edit', $contact)" wire:navigate size="sm" variant="subtle">Sửa</flux:button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                Chưa có người liên hệ nào trong hệ thống hoặc phù hợp với bộ lọc hiện tại.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->contacts->hasPages())
            <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-800">
                {{ $this->contacts->links() }}
            </div>
        @endif
    </section>
</div>
