<div>
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">CRM / Bán hàng</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">Khách hàng tiềm năng</h1>
            <p class="mt-2 max-w-3xl text-slate-500">Tra cứu và theo dõi Lead theo đúng phạm vi sở hữu hoặc phòng ban của bạn.</p>
        </div>
        <div class="flex items-center gap-2">
            <flux:badge color="emerald">{{ $this->visibleTotal }} Lead trong phạm vi</flux:badge>
            @can('viewTrash', \App\Models\Lead::class)
                <flux:button :href="route('leads.trash')" wire:navigate variant="ghost" icon="trash">Thùng rác</flux:button>
            @endcan
            @can('create', \App\Models\Lead::class)
                <flux:button :href="route('leads.create')" wire:navigate variant="primary" icon="plus">Tạo Lead</flux:button>
            @endcan
        </div>
    </div>

    <section class="crm-card relative" aria-labelledby="lead-list-title">
        <div
            wire:loading.flex
            wire:target="search,status,priority,source,tag,owner,department,dateFrom,dateTo,sort,direction,perPage,clearFilters,gotoPage,nextPage,previousPage"
            class="absolute inset-x-5 top-3 z-10 hidden items-center justify-end gap-2 text-xs font-medium text-emerald-700 dark:text-emerald-300"
            role="status"
        >
            <span class="size-2 animate-pulse rounded-full bg-emerald-500"></span>
            Đang cập nhật danh sách…
        </div>

        <div class="mb-5">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                <div>
                    <h2 id="lead-list-title" class="font-semibold">Danh sách Lead</h2>
                    <p class="mt-1 text-sm text-slate-500">Tìm thấy {{ $this->leads->total() }} Lead phù hợp.</p>
                </div>
                @if ($this->hasActiveFilters)
                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">Xóa bộ lọc</flux:button>
                @endif
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    label="Tìm kiếm"
                    placeholder="Tên, email, điện thoại, công ty"
                />

                <flux:select wire:model.live="status" label="Trạng thái">
                    <option value="all">Tất cả trạng thái</option>
                    @foreach ($this->statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="priority" label="Mức ưu tiên">
                    <option value="all">Tất cả mức ưu tiên</option>
                    @foreach ($this->priorityOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="source" label="Nguồn Lead">
                    <option value="all">Tất cả nguồn</option>
                    @foreach ($this->sourceOptions as $sourceOption)
                        <option value="{{ $sourceOption->id }}">{{ $sourceOption->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <details class="group mt-4 rounded-xl border border-slate-200 bg-slate-50/70 dark:border-slate-800 dark:bg-slate-950/30" @if ($tag !== 'all' || $owner !== 'all' || $department !== 'all' || $dateFrom !== '' || $dateTo !== '' || $sort !== 'created_at' || $direction !== 'desc' || $perPage !== 15) open @endif>
                <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 px-4 text-sm font-medium">
                    <span>Bộ lọc nâng cao và sắp xếp</span>
                    <span class="text-slate-400 transition group-open:rotate-180">⌄</span>
                </summary>
                <div class="grid gap-3 border-t border-slate-200 p-4 md:grid-cols-2 xl:grid-cols-4 dark:border-slate-800">
                    <flux:select wire:model.live="tag" label="Tag">
                        <option value="all">Tất cả tag</option>
                        @foreach ($this->tagOptions as $tagOption)
                            <option value="{{ $tagOption->id }}">{{ $tagOption->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="owner" label="Người phụ trách">
                        <option value="all">Tất cả người phụ trách</option>
                        @foreach ($this->ownerOptions as $ownerOption)
                            <option value="{{ $ownerOption->id }}">{{ $ownerOption->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="department" label="Phòng ban">
                        <option value="all">Tất cả phòng ban</option>
                        @foreach ($this->departmentOptions as $departmentOption)
                            <option value="{{ $departmentOption->id }}">{{ $departmentOption->name }} ({{ $departmentOption->code }})</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="perPage" label="Số dòng mỗi trang">
                        @foreach ([10, 15, 25, 50, 100] as $size)
                            <option value="{{ $size }}">{{ $size }} dòng</option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model.live="dateFrom" type="date" label="Tạo từ ngày" />
                    <flux:input wire:model.live="dateTo" type="date" label="Đến ngày" />

                    <flux:select wire:model.live="sort" label="Sắp xếp theo">
                        @foreach ($this->sortOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="direction" label="Thứ tự">
                        <option value="desc">Giảm dần</option>
                        <option value="asc">Tăng dần</option>
                    </flux:select>
                </div>
            </details>
        </div>

        @if ($selectedLeadIds !== [])
            <div class="mb-4 flex flex-col justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm dark:border-emerald-900 dark:bg-emerald-950/30 sm:flex-row sm:items-center">
                <p class="font-medium text-emerald-800 dark:text-emerald-200">
                    Đã chọn {{ count($selectedLeadIds) }} Lead trên trang hiện tại.
                    <span class="font-normal text-emerald-700/80 dark:text-emerald-300/80">Bulk action sẽ được bổ sung ở P3-07/P3-08.</span>
                </p>
                <flux:button size="sm" variant="ghost" wire:click="clearSelection">Bỏ chọn</flux:button>
            </div>
        @endif

        @if ($this->leads->isEmpty())
            <div class="grid min-h-64 place-items-center rounded-xl border border-dashed border-slate-300 text-center dark:border-slate-700">
                <div class="max-w-md px-6">
                    <span class="mx-auto grid size-12 place-items-center rounded-full bg-slate-100 text-xl dark:bg-slate-800">◎</span>
                    @if ($this->visibleTotal === 0)
                        <p class="mt-4 font-medium">Chưa có Lead trong phạm vi của bạn</p>
                        <p class="mt-1 text-sm text-slate-500">Lead mới sẽ xuất hiện tại đây sau khi được tạo hoặc phân công.</p>
                    @else
                        <p class="mt-4 font-medium">Không có Lead phù hợp bộ lọc</p>
                        <p class="mt-1 text-sm text-slate-500">Thử thay đổi từ khóa, trạng thái hoặc xóa các bộ lọc hiện tại.</p>
                        <flux:button class="mt-4" size="sm" variant="ghost" wire:click="clearFilters">Đặt lại bộ lọc</flux:button>
                    @endif
                </div>
            </div>
        @else
            <div class="relative">
                <div wire:loading.delay.longest class="absolute inset-0 bg-white/50 dark:bg-slate-900/50 backdrop-blur-[1px] z-10 flex items-center justify-center rounded-xl">
                    <svg class="animate-spin h-8 w-8 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <div wire:loading.class="opacity-60" class="transition-opacity">
                <div class="hidden overflow-x-auto lg:block">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>
                                <input
                                    type="checkbox"
                                    wire:click="togglePageSelection"
                                    @checked($this->allPageSelected)
                                    class="size-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                    aria-label="Chọn tất cả Lead trên trang"
                                >
                            </flux:table.column>
                            <flux:table.column>Lead</flux:table.column>
                            <flux:table.column>Nguồn / Tag</flux:table.column>
                            <flux:table.column>Phụ trách</flux:table.column>
                            <flux:table.column>Trạng thái</flux:table.column>
                            <flux:table.column align="end">Giá trị dự kiến</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($this->leads as $lead)
                                <flux:table.row :key="$lead->id">
                                    <flux:table.cell>
                                        <input
                                            type="checkbox"
                                            wire:model.live="selectedLeadIds"
                                            value="{{ $lead->id }}"
                                            class="size-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                            aria-label="Chọn Lead {{ $lead->full_name }}"
                                        >
                                    </flux:table.cell>
                                    <flux:table.cell variant="strong">
                                        <div class="min-w-56">
                                            <p>{{ $lead->full_name }}</p>
                                            <p class="mt-1 truncate text-xs font-normal text-slate-500">{{ $lead->company_name ?: 'Chưa có công ty' }}</p>
                                            <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs font-normal text-slate-500">
                                                <span>{{ $lead->email ?: 'Chưa có email' }}</span>
                                                <span>{{ $lead->phone ?: 'Chưa có SĐT' }}</span>
                                            </div>
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <div class="min-w-44">
                                            @if ($lead->source)
                                                <flux:badge size="sm">{{ $lead->source->name }}</flux:badge>
                                            @else
                                                <span class="text-sm text-slate-400">Chưa có nguồn</span>
                                            @endif
                                            <div class="mt-2 flex flex-wrap gap-1">
                                                @foreach ($lead->tags as $leadTag)
                                                    <flux:badge size="sm" color="zinc">{{ $leadTag->name }}</flux:badge>
                                                @endforeach
                                            </div>
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <div class="min-w-40">
                                            <p>{{ $lead->owner?->name ?? 'Chưa phân công' }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ $lead->department?->name ?? 'Chưa có phòng ban' }}</p>
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <div class="flex min-w-32 flex-col items-start gap-1.5">
                                            <flux:badge :color="$lead->status->color()" size="sm">{{ $lead->status->label() }}</flux:badge>
                                            <flux:badge :color="$lead->priority->color()" size="sm">{{ $lead->priority->label() }}</flux:badge>
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell align="end">
                                        <p class="font-medium">{{ $lead->estimated_value !== null ? number_format((float) $lead->estimated_value, 0, ',', '.').' ₫' : '—' }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $lead->created_at?->format('d/m/Y') }}</p>
                                        <div class="mt-2 flex justify-end gap-1">
                                            <flux:button :href="route('leads.show', ['leadId' => $lead->id])" wire:navigate size="sm" variant="ghost">Xem</flux:button>
                                            @can('convert', $lead)
                                                @if ($lead->status->value !== 'converted')
                                                    <flux:button :href="route('leads.show', ['leadId' => $lead->id])" wire:navigate size="sm" variant="subtle" color="emerald">Chuyển đổi</flux:button>
                                                @endif
                                            @endcan
                                            @can('update', $lead)
                                                <flux:button :href="route('leads.edit', ['leadId' => $lead->id])" wire:navigate size="sm" variant="ghost">Sửa</flux:button>
                                            @endcan
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                <div class="grid gap-3 lg:hidden">
                    @foreach ($this->leads as $lead)
                        <article wire:key="lead-card-{{ $lead->id }}" class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                            <div class="flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    wire:model.live="selectedLeadIds"
                                    value="{{ $lead->id }}"
                                    class="mt-1 size-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                    aria-label="Chọn Lead {{ $lead->full_name }}"
                                >
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <h3 class="font-semibold">{{ $lead->full_name }}</h3>
                                            <p class="mt-1 text-sm text-slate-500">{{ $lead->company_name ?: 'Chưa có công ty' }}</p>
                                        </div>
                                        <flux:badge :color="$lead->status->color()" size="sm">{{ $lead->status->label() }}</flux:badge>
                                    </div>

                                    <div class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                                        <p><span class="text-slate-500">Liên hệ:</span> {{ $lead->phone ?: $lead->email ?: 'Chưa có' }}</p>
                                        <p><span class="text-slate-500">Phụ trách:</span> {{ $lead->owner?->name ?? 'Chưa phân công' }}</p>
                                        <p><span class="text-slate-500">Phòng ban:</span> {{ $lead->department?->name ?? 'Chưa gán' }}</p>
                                        <p><span class="text-slate-500">Giá trị:</span> {{ $lead->estimated_value !== null ? number_format((float) $lead->estimated_value, 0, ',', '.').' ₫' : '—' }}</p>
                                    </div>

                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        <flux:badge :color="$lead->priority->color()" size="sm">{{ $lead->priority->label() }}</flux:badge>
                                        @if ($lead->source)
                                            <flux:badge size="sm">{{ $lead->source->name }}</flux:badge>
                                        @endif
                                        @foreach ($lead->tags as $leadTag)
                                            <flux:badge size="sm" color="zinc">{{ $leadTag->name }}</flux:badge>
                                        @endforeach
                                    </div>

                                    <div class="mt-4 flex justify-end gap-2 border-t border-slate-100 pt-3 dark:border-slate-800">
                                        <flux:button :href="route('leads.show', $lead)" wire:navigate size="sm" variant="ghost">Xem chi tiết</flux:button>
                                        @can('update', $lead)
                                            <flux:button :href="route('leads.edit', $lead)" wire:navigate size="sm" variant="ghost">Sửa</flux:button>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-5">
                    {{ $this->leads->onEachSide(1)->links() }}
                </div>
                </div>
            </div>
        @endif
    </section>
</div>
