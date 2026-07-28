<div>
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">Cài đặt / Tổ chức</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">Phòng ban</h1>
            <p class="mt-2 max-w-2xl text-slate-500">Quản lý cấu trúc phòng ban, cấp cha và trạng thái sử dụng trong SalesFlow CRM.</p>
        </div>
        @can('create', \App\Models\Department::class)
            <flux:button variant="primary" icon="plus" wire:click="openCreate">Tạo phòng ban</flux:button>
        @endcan
    </div>

    @if ($notice)
        <div @class([
            'mb-6 rounded-xl border px-4 py-3 text-sm',
            'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200' => $noticeType === 'success',
            'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200' => $noticeType === 'error',
        ]) role="status">
            {{ $notice }}
        </div>
    @endif

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Tổng phòng ban', $this->stats['total']],
            ['Đang hoạt động', $this->stats['active']],
            ['Ngừng hoạt động', $this->stats['inactive']],
            ['Phòng ban cấp gốc', $this->stats['roots']],
        ] as [$label, $value])
            <div class="crm-card">
                <p class="text-sm text-slate-500">{{ $label }}</p>
                <p class="mt-3 text-2xl font-semibold">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <flux:modal name="department-form" class="md:w-[35rem]" wire:close="cancelForm">
        @if ($showForm)
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ $form->departmentId ? 'Chỉnh sửa phòng ban' : 'Tạo phòng ban' }}</flux:heading>
                    <flux:subheading class="mt-1">Mã phòng ban được chuẩn hóa thành chữ in hoa và không được trùng.</flux:subheading>
                </div>

                <form wire:submit="save" class="space-y-5">
                    <div class="grid gap-5 md:grid-cols-2">
                        <flux:input wire:model.blur="form.name" label="Tên phòng ban" placeholder="Ví dụ: Phòng Kinh doanh" required />
                        <flux:input wire:model.blur="form.code" label="Mã phòng ban" placeholder="Ví dụ: SALES-HCM" required />
                        <x-forms.smart-select wire:model="form.parentId" label="Phòng ban cha" placeholder="Không có — phòng ban cấp gốc">
                            <option value="">Không có — phòng ban cấp gốc</option>
                            @foreach ($this->parentOptions as $department)
                                <option value="{{ $department->id }}">{{ $department->name }} ({{ $department->code }})</option>
                            @endforeach
                        </x-forms.smart-select>
                        <flux:input wire:model="form.sortOrder" type="number" min="0" max="32767" label="Thứ tự hiển thị" required />
                    </div>

                    <flux:textarea wire:model.blur="form.description" label="Mô tả" rows="3" placeholder="Mô tả ngắn về chức năng của phòng ban" />

                    <flux:switch wire:model="form.isActive" label="Đang hoạt động" description="Phòng ban hoạt động có thể được chọn khi gán người dùng và dữ liệu CRM." />

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 dark:border-slate-800 sm:flex-row sm:justify-end">
                        <flux:button type="button" variant="ghost" wire:click="cancelForm">Hủy</flux:button>
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                            {{ $form->departmentId ? 'Lưu thay đổi' : 'Tạo phòng ban' }}
                        </flux:button>
                    </div>
                </form>
            </div>
        @endif
    </flux:modal>

    <section class="crm-card">
        <div class="data-list-heading">
            <div>
                <h2 class="font-semibold">Danh sách phòng ban</h2>
                <p class="mt-1 text-sm text-slate-500">Sắp xếp theo cấp cha, thứ tự hiển thị và tên.</p>
            </div>
            @if ($search !== '' || $status !== 'all')
                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">Xóa bộ lọc</flux:button>
            @endif
        </div>

        <div class="mb-5 grid gap-3 md:grid-cols-2 xl:max-w-2xl">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" label="Tìm kiếm" placeholder="Tìm theo tên hoặc mã" />
            <x-forms.smart-select wire:model.live="status" label="Trạng thái">
                <option value="all">Tất cả trạng thái</option>
                <option value="active">Đang hoạt động</option>
                <option value="inactive">Ngừng hoạt động</option>
            </x-forms.smart-select>
        </div>

        <div class="data-list-content">
            <x-data-list.loading target="search,status,clearFilters" />

            @if ($this->departments->isEmpty())
                <x-data-list.empty
                    title="Không tìm thấy phòng ban"
                    description="Thử thay đổi từ khóa hoặc bộ lọc trạng thái."
                    icon="building-office"
                >
                    @if ($search !== '' || $status !== 'all')
                        <x-slot:action>
                            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">Đặt lại bộ lọc</flux:button>
                        </x-slot:action>
                    @endif
                </x-data-list.empty>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Phòng ban</flux:table.column>
                        <flux:table.column>Cấp cha</flux:table.column>
                        <flux:table.column>Người dùng</flux:table.column>
                        <flux:table.column>Trạng thái</flux:table.column>
                        <flux:table.column align="end">Thao tác</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->departments as $department)
                            <flux:table.row :key="$department->id">
                                <flux:table.cell variant="strong">
                                    <div class="min-w-48">
                                        <p>{{ $department->name }}</p>
                                        <p class="mt-0.5 font-mono text-xs font-normal text-slate-400">{{ $department->code }}</p>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>{{ $department->parent?->name ?? 'Cấp gốc' }}</flux:table.cell>
                                <flux:table.cell>{{ $department->users_count }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge :color="$department->is_active ? 'emerald' : null" size="sm">
                                        {{ $department->is_active ? 'Hoạt động' : 'Ngừng hoạt động' }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <div class="flex justify-end gap-2">
                                        @can('update', $department)
                                            <flux:button size="sm" variant="ghost" wire:click="openEdit({{ $department->id }})" wire:loading.attr="disabled">Sửa</flux:button>
                                            <flux:button size="sm" variant="ghost" wire:click="toggleActive({{ $department->id }})" wire:loading.attr="disabled" wire:target="toggleActive({{ $department->id }})">
                                                {{ $department->is_active ? 'Tắt' : 'Bật' }}
                                            </flux:button>
                                        @endcan
                                        @can('delete', $department)
                                            <flux:button size="sm" variant="ghost" class="text-red-600! hover:text-red-700! dark:text-red-400!" wire:click="openDelete({{ $department->id }})" wire:loading.attr="disabled" wire:target="openDelete({{ $department->id }})">
                                                Xóa
                                            </flux:button>
                                        @endcan
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </div>
    </section>

    <flux:modal name="delete-department" class="md:w-[30rem]" wire:close="dismissDelete">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Xóa phòng ban?</flux:heading>
                <flux:text class="mt-2">
                    Bạn sắp xóa vĩnh viễn phòng ban <strong>{{ $pendingDeleteName }}</strong>. Thao tác này không thể hoàn tác.
                </flux:text>
            </div>

            @if ($deleteError)
                <flux:callout variant="danger" heading="Không thể xóa phòng ban">
                    {{ $deleteError }}
                </flux:callout>
            @endif

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">Hủy</flux:button>
                <flux:button variant="danger" wire:click="confirmDelete" wire:loading.attr="disabled" wire:target="confirmDelete">
                    Xác nhận xóa
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
