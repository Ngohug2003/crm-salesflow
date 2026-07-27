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
            <flux:button wire:click="exportCsv" variant="outline" icon="arrow-down-tray">Xuất CSV</flux:button>
            @can('create', \App\Models\Lead::class)
                <flux:button :href="route('imports.leads')" wire:navigate variant="outline" icon="arrow-up-tray">Nhập Lead</flux:button>
                <flux:button :href="route('leads.create')" wire:navigate variant="primary" icon="plus">Tạo Lead</flux:button>
            @endcan
        </div>
    </div>

    @if ($exportDownloadUrl !== null)
        <div class="mb-4 rounded-xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-900/50 dark:bg-indigo-950/40">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex size-9 items-center justify-center rounded-lg bg-indigo-600 text-white">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 dark:text-white">Yêu cầu xuất dữ liệu Queued Export #{{ $exportBatchId }} đã được gửi thành công!</h4>
                        <p class="text-[11px] text-slate-500">Tệp CSV đang được khởi tạo. Đường dẫn Signed Download bảo mật có hiệu lực trong 24 giờ.</p>
                    </div>
                </div>

                <a
                    href="{{ $exportDownloadUrl }}"
                    target="_blank"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500 shadow-xs"
                >
                    <span>Tải file về máy (.CSV)</span>
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            </div>
        </div>
    @endif

    <section class="crm-card relative" aria-labelledby="lead-list-title">
        <div class="mb-5">
            <div class="data-list-heading">
                <div>
                    <h2 id="lead-list-title" class="font-semibold">Danh sách Lead</h2>
                    <p class="mt-1 text-sm text-slate-500">Tìm thấy {{ $this->leads->total() }} Lead phù hợp.</p>
                </div>
                @if ($this->hasActiveFilters)
                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">Xóa bộ lọc</flux:button>
                @endif
            </div>

            <div class="data-list-filters">
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

            <details class="group mt-4 rounded-xl border border-slate-200 bg-slate-50/70 dark:border-slate-800 dark:bg-slate-950/30" @if ($scoreLevel !== 'all' || $tag !== 'all' || $owner !== 'all' || $department !== 'all' || $dateFrom !== '' || $dateTo !== '' || $sort !== 'created_at' || $direction !== 'desc' || $perPage !== 15) open @endif>
                <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 px-4 text-sm font-medium">
                    <span>Bộ lọc nâng cao và sắp xếp</span>
                    <span class="text-slate-400 transition group-open:rotate-180">⌄</span>
                </summary>
                <div class="grid gap-3 border-t border-slate-200 p-4 md:grid-cols-2 xl:grid-cols-4 dark:border-slate-800">
                    <flux:select wire:model.live="scoreLevel" label="Cấp độ Điểm Lead">
                        <option value="all">Tất cả điểm</option>
                        <option value="hot">Hot Lead (≥ 70đ)</option>
                        <option value="warm">Warm Lead (40-69đ)</option>
                        <option value="cold">Cold Lead (< 40đ)</option>
                    </flux:select>

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

    @if ($bulkFeedback)
        <div class="mb-4 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300" role="status">
            <span>{{ $bulkFeedback }}</span>
            <button type="button" class="text-xs font-semibold hover:underline" wire:click="$set('bulkFeedback', null)">Ẩn</button>
        </div>
    @endif

    @if ($selectedLeadIds !== [])
        <div class="mb-4 flex flex-col justify-between gap-3 rounded-2xl border border-indigo-200 bg-indigo-50/90 p-4 shadow-sm dark:border-indigo-900/60 dark:bg-indigo-950/50 sm:flex-row sm:items-center">
            <div class="flex items-center gap-2">
                <flux:badge color="indigo" size="lg">Đã chọn {{ count($selectedLeadIds) }} Lead</flux:badge>
                <p class="text-xs text-indigo-700 dark:text-indigo-300">Thao tác sẽ tự động re-authorize kiểm tra quyền trên từng Lead ở backend.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <flux:button size="sm" variant="filled" icon="user-plus" wire:click="openBulkAssign">Gán phụ trách</flux:button>
                <flux:button size="sm" variant="filled" icon="arrow-path" wire:click="openBulkStatus">Đổi trạng thái</flux:button>
                <flux:button size="sm" variant="filled" icon="tag" wire:click="openBulkTag">Gán Tag</flux:button>
                <flux:button size="sm" variant="danger" icon="trash" wire:click="openBulkDelete">Xóa hàng loạt</flux:button>
                <flux:button size="sm" variant="ghost" wire:click="clearSelection">Bỏ chọn</flux:button>
            </div>
        </div>
    @endif

        @if ($this->leads->isEmpty())
            @if ($this->visibleTotal === 0)
                <x-data-list.empty
                    title="Chưa có Lead trong phạm vi của bạn"
                    description="Lead mới sẽ xuất hiện tại đây sau khi được tạo hoặc phân công."
                    icon="user-plus"
                />
            @else
                <x-data-list.empty
                    title="Không có Lead phù hợp bộ lọc"
                    description="Thử thay đổi từ khóa, trạng thái hoặc xóa các bộ lọc hiện tại."
                    icon="magnifying-glass"
                >
                    <x-slot:action>
                        <flux:button class="mt-4" size="sm" variant="ghost" wire:click="clearFilters">Đặt lại bộ lọc</flux:button>
                    </x-slot:action>
                </x-data-list.empty>
            @endif
        @else
            <div class="data-list-content">
                <x-data-list.loading target="search,status,priority,source,tag,owner,department,dateFrom,dateTo,sort,direction,perPage,clearFilters,gotoPage,nextPage,previousPage" />
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
                                        <div class="flex min-w-36 flex-col items-start gap-1.5">
                                            <flux:badge :color="$lead->score_badge_color" size="sm">
                                                {{ $lead->score }}đ — {{ $lead->score_level_label }}
                                            </flux:badge>
                                            <div class="flex flex-wrap gap-1">
                                                <flux:badge :color="$lead->status->color()" size="sm">{{ $lead->status->label() }}</flux:badge>
                                                <flux:badge :color="$lead->priority->color()" size="sm">{{ $lead->priority->label() }}</flux:badge>
                                            </div>
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

                <x-data-list.pagination :paginator="$this->leads" />
                </div>
            </div>
        @endif
    </section>

    {{-- Modal Phân công hàng loạt --}}
    <flux:modal name="bulk-assign-modal" class="md:w-[32rem]" wire:close="cancelBulkAssign">
        @if ($showBulkAssignModal)
            <form wire:submit.prevent="executeBulkAssign" class="space-y-5">
                <div>
                    <flux:heading size="lg">Phân công Lead hàng loạt</flux:heading>
                    <flux:text class="mt-2">Đang chọn {{ count($selectedLeadIds) }} Lead. Phòng ban sẽ tự động đồng bộ theo NVKD phụ trách mới.</flux:text>
                </div>
                <div class="space-y-4">
                    <flux:select wire:model="bulkOwnerId" label="Người phụ trách mới">
                        <option value="">Chưa phân công</option>
                        @foreach ($this->ownerOptions as $ownerOption)
                            <option value="{{ $ownerOption->id }}">{{ $ownerOption->name }} — {{ $ownerOption->email }}</option>
                        @endforeach
                    </flux:select>
                    <flux:textarea wire:model="bulkAssignReason" label="Lý do phân công" rows="3" maxlength="500" placeholder="Ví dụ: Phân bổ khách hàng khu vực Miền Bắc" />
                </div>
                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" x-on:click="$flux.modal('bulk-assign-modal').close()">Hủy</flux:button>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="executeBulkAssign">Lưu phân công</flux:button>
                </div>
            </form>
        @endif
    </flux:modal>

    {{-- Modal Đổi trạng thái hàng loạt --}}
    <flux:modal name="bulk-status-modal" class="md:w-[32rem]" wire:close="cancelBulkStatus">
        @if ($showBulkStatusModal)
            <form wire:submit.prevent="executeBulkStatus" class="space-y-5">
                <div>
                    <flux:heading size="lg">Chuyển trạng thái hàng loạt</flux:heading>
                    <flux:text class="mt-2">Đang chọn {{ count($selectedLeadIds) }} Lead.</flux:text>
                </div>
                <div class="space-y-4">
                    <flux:select wire:model="bulkStatus" label="Trạng thái mục tiêu *" required>
                        <option value="">Chọn trạng thái</option>
                        @foreach ($this->statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:textarea wire:model="bulkStatusReason" label="Lý do thay đổi" rows="3" maxlength="500" placeholder="Nhập lý do đổi trạng thái..." />
                </div>
                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" x-on:click="$flux.modal('bulk-status-modal').close()">Hủy</flux:button>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="executeBulkStatus">Cập nhật trạng thái</flux:button>
                </div>
            </form>
        @endif
    </flux:modal>

    {{-- Modal Gán Tag hàng loạt --}}
    <flux:modal name="bulk-tag-modal" class="md:w-[28rem]" wire:close="cancelBulkTag">
        @if ($showBulkTagModal)
            <form wire:submit.prevent="executeBulkTag" class="space-y-5">
                <div>
                    <flux:heading size="lg">Gán Thẻ (Tag) hàng loạt</flux:heading>
                    <flux:text class="mt-2">Chọn thẻ để đính kèm cho {{ count($selectedLeadIds) }} Lead đang chọn.</flux:text>
                </div>
                <div class="space-y-4">
                    <flux:select wire:model="bulkTagId" label="Thẻ cần gán *" required>
                        <option value="">Chọn thẻ</option>
                        @foreach ($this->tagOptions as $tagOption)
                            <option value="{{ $tagOption->id }}">{{ $tagOption->name }}</option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" x-on:click="$flux.modal('bulk-tag-modal').close()">Hủy</flux:button>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="executeBulkTag">Xác nhận gán thẻ</flux:button>
                </div>
            </form>
        @endif
    </flux:modal>

    {{-- Modal Xóa hàng loạt --}}
    <flux:modal name="bulk-delete-modal" class="md:w-[28rem]" wire:close="cancelBulkDelete">
        @if ($showBulkDeleteModal)
            <form wire:submit.prevent="executeBulkDelete" class="space-y-5">
                <div>
                    <flux:heading size="lg" class="text-red-600 dark:text-red-400">Xóa Lead hàng loạt</flux:heading>
                    <flux:text class="mt-2">Bạn có chắc chắn muốn chuyển <strong>{{ count($selectedLeadIds) }} Lead</strong> vào Thùng rác không? Hành động này có thể khôi phục lại từ Thùng rác.</flux:text>
                </div>
                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" x-on:click="$flux.modal('bulk-delete-modal').close()">Hủy</flux:button>
                    <flux:button type="submit" variant="danger" wire:loading.attr="disabled" wire:target="executeBulkDelete">Xác nhận Xóa</flux:button>
                </div>
            </form>
        @endif
    </flux:modal>
</div>
