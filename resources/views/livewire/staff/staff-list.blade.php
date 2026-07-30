<div>
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">Cài đặt / Tổ chức</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">Hồ sơ nhân viên</h1>
            <p class="mt-2 max-w-2xl text-slate-500">Quản lý thông tin nhân sự, phòng ban, địa bàn làm việc và tài khoản CRM liên kết.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <flux:button variant="ghost" icon="envelope" wire:click="openInviteModal">Mời nhân viên</flux:button>
            <flux:button variant="primary" icon="plus" wire:click="openCreate">Tạo hồ sơ nhân viên</flux:button>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
            {{ session('success') }}
        </div>
    @endif

    <section class="crm-card" aria-labelledby="staff-list-title">
        <div class="data-list-heading">
            <div>
                <h2 id="staff-list-title" class="font-semibold">Danh sách nhân viên</h2>
                <p class="mt-1 text-sm text-slate-500">Tìm thấy {{ $staffList->total() }} hồ sơ phù hợp.</p>
            </div>
        </div>

        <div class="mb-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                label="Tìm kiếm"
                placeholder="Tên, email, mã nhân viên, số điện thoại"
                class="xl:col-span-2"
            />

            <x-forms.smart-select wire:model.live="filterDepartmentId" label="Phòng ban">
                <option value="">Tất cả phòng ban</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </x-forms.smart-select>

            <x-forms.smart-select wire:model.live="filterActive" label="Trạng thái">
                <option value="">Tất cả trạng thái</option>
                <option value="1">Đang hoạt động</option>
                <option value="0">Tạm khóa</option>
            </x-forms.smart-select>
        </div>

        <div class="data-list-content overflow-x-auto">
            <flux:table class="w-full text-left text-sm">
                <flux:table.columns>
                    <flux:table.column class="w-32 whitespace-nowrap">Mã NV</flux:table.column>
                    <flux:table.column class="min-w-[14rem]">Họ và tên / Email</flux:table.column>
                    <flux:table.column class="w-36 whitespace-nowrap">Số điện thoại</flux:table.column>
                    <flux:table.column class="min-w-[13rem]">Phòng ban và chức danh</flux:table.column>
                    <flux:table.column class="min-w-[14rem]">Địa chỉ và địa bàn</flux:table.column>
                    <flux:table.column class="w-32 whitespace-nowrap">Trạng thái</flux:table.column>
                    <flux:table.column align="end" class="w-36 whitespace-nowrap text-right">Thao tác</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($staffList as $staff)
                        <flux:table.row class="align-middle">
                            <flux:table.cell class="w-32 whitespace-nowrap font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                {{ $staff->staff_code }}
                            </flux:table.cell>
                            <flux:table.cell class="min-w-[14rem]">
                                <p class="font-semibold text-slate-900 dark:text-white">{{ $staff->full_name }}</p>
                                <p class="text-xs text-slate-500">{{ $staff->email }}</p>
                                @if ($staff->birthday)
                                    <p class="mt-1 text-xs text-slate-500">Ngày sinh: {{ $staff->birthday->format('d/m/Y') }}</p>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="w-36 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400">
                                {{ $staff->phone ?: 'Chưa cập nhật' }}
                            </flux:table.cell>
                            <flux:table.cell class="min-w-[13rem]">
                                <p class="text-xs font-semibold text-slate-900 dark:text-white">{{ $staff->department?->name ?: 'Chưa gán' }}</p>
                                <p class="text-xs text-slate-500">{{ $staff->position ?: 'Nhân viên' }}</p>
                            </flux:table.cell>
                            <flux:table.cell class="min-w-[14rem] text-xs text-slate-600 dark:text-slate-400">
                                @if ($staff->ward || $staff->provinceUnit)
                                    <div class="font-medium text-slate-800 dark:text-slate-200">
                                        {{ $staff->ward?->name ? $staff->ward->name.', ' : '' }}{{ $staff->provinceUnit?->name }}
                                    </div>
                                @else
                                    <div class="text-slate-400">Chưa cập nhật địa chỉ</div>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="w-32 whitespace-nowrap">
                                <flux:badge color="{{ $staff->is_active ? 'emerald' : 'slate' }}" size="sm">
                                    {{ $staff->is_active ? 'Hoạt động' : 'Tạm dừng' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end" class="w-36 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                    <flux:button size="xs" variant="ghost" icon="pencil" wire:click="editStaff({{ $staff->id }})">Sửa</flux:button>
                                    <flux:button size="xs" variant="subtle" color="red" icon="trash" wire:click="deleteStaff({{ $staff->id }})">Xóa</flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="py-8 text-center text-slate-500">
                                Không tìm thấy hồ sơ nhân viên nào.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        <div class="data-list-pagination">
            {{ $staffList->links() }}
        </div>
    </section>

    <flux:modal name="staff-form-modal" wire:model="showForm" class="w-full" style="width: 44rem; max-width: 95vw;">
        <form wire:submit.prevent="saveStaff" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingStaffId ? 'Chỉnh sửa hồ sơ nhân viên' : 'Tạo hồ sơ nhân viên' }}</flux:heading>
                <flux:subheading class="mt-1">Cập nhật thông tin liên hệ, phòng ban và địa bàn làm việc.</flux:subheading>
            </div>

            <div class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <flux:field>
                        <flux:label>Họ và tên *</flux:label>
                        <flux:input wire:model="fullName" placeholder="Ví dụ: Nguyễn Văn An" />
                        <flux:error name="fullName" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Mã nhân viên</flux:label>
                        <flux:input wire:model="staffCode" readonly disabled placeholder="Tự động theo phòng ban" class="cursor-not-allowed bg-slate-100 font-mono dark:bg-slate-800" />
                        <flux:description>Mã được tạo tự động theo phòng ban.</flux:description>
                        <flux:error name="staffCode" />
                    </flux:field>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <flux:field>
                        <flux:label>Email *</flux:label>
                        <flux:input type="email" wire:model="email" placeholder="an.nguyen@salesflow.test" />
                        <flux:error name="email" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Số điện thoại</flux:label>
                        <flux:input wire:model="phone" placeholder="0912345678" />
                        <flux:error name="phone" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Ngày sinh</flux:label>
                        <flux:input type="date" wire:model="birthday" />
                        <flux:error name="birthday" />
                    </flux:field>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <flux:field>
                        <flux:label>Phòng ban</flux:label>
                        <x-forms.modal-searchable-select
                            wire:model.live="departmentId"
                            :options="$departments"
                            option-value="id"
                            option-label="name"
                            placeholder="Chưa gán phòng ban"
                            search-placeholder="Tìm phòng ban…"
                        />
                        <flux:error name="departmentId" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Chức danh</flux:label>
                        <flux:input wire:model="position" placeholder="Ví dụ: Chuyên viên Bán hàng" />
                        <flux:error name="position" />
                    </flux:field>
                </div>

                <div class="border-t border-slate-200 pt-4 dark:border-slate-800">
                    <h3 class="mb-3 text-sm font-semibold text-slate-900 dark:text-white">Địa chỉ và địa bàn</h3>
                    <div class="space-y-3">
                        <flux:field>
                            <flux:label>Địa chỉ chi tiết</flux:label>
                            <flux:input wire:model="address" placeholder="Tầng 5, Tòa nhà SalesFlow, Số 10 Cầu Giấy" />
                            <flux:error name="address" />
                        </flux:field>

                        <div class="grid gap-4 md:grid-cols-2">
                            <flux:field>
                                <flux:label>Tỉnh/Thành phố</flux:label>
                                <x-forms.modal-searchable-select
                                    wire:model.live="provinceId"
                                    :options="$provinces"
                                    option-value="id"
                                    option-label="name"
                                    placeholder="Chọn Tỉnh/Thành phố"
                                    search-placeholder="Tìm Tỉnh/Thành…"
                                />
                                <flux:error name="provinceId" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Phường/Xã</flux:label>
                                <x-forms.modal-searchable-select
                                    wire:model="wardId"
                                    :options="$wards"
                                    option-value="id"
                                    option-label="name"
                                    :placeholder="empty($provinceId) ? 'Chọn Tỉnh/Thành phố trước' : 'Chọn Phường/Xã'"
                                    search-placeholder="Tìm Phường/Xã…"
                                    :disabled="empty($provinceId)"
                                />
                                <flux:error name="wardId" />
                            </flux:field>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 border-t border-slate-200 pt-4 md:grid-cols-2 dark:border-slate-800">
                    <flux:field>
                        <flux:label>Ngày vào làm</flux:label>
                        <flux:input type="date" wire:model="joinDate" />
                        <flux:error name="joinDate" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Trạng thái hoạt động</flux:label>
                        <flux:checkbox wire:model="isActive" label="Cho phép hoạt động" />
                    </flux:field>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-800">
                <flux:button variant="ghost" wire:click="$set('showForm', false)">Hủy</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveStaff">Lưu hồ sơ</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="staff-invite-modal" wire:model="showInviteModal" class="w-full" style="width: 36rem; max-width: 95vw;">
        <form wire:submit.prevent="sendInvitation" class="space-y-6">
            <div>
                <flux:heading size="lg">Mời nhân viên</flux:heading>
                <flux:subheading class="mt-1">Gửi email mời tham gia CRM và tạo hồ sơ nhân viên tương ứng.</flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>Họ và tên *</flux:label>
                    <flux:input wire:model="inviteName" placeholder="Trần Văn Nam" />
                    <flux:error name="inviteName" />
                </flux:field>

                <flux:field>
                    <flux:label>Email *</flux:label>
                    <flux:input type="email" wire:model="inviteEmail" placeholder="nam.tran@salesflow.test" />
                    <flux:error name="inviteEmail" />
                </flux:field>

                <div class="grid gap-4 md:grid-cols-2">
                    <flux:field>
                        <flux:label>Phòng ban</flux:label>
                        <x-forms.modal-searchable-select
                            wire:model="inviteDepartmentId"
                            :options="$departments"
                            option-value="id"
                            option-label="name"
                            placeholder="Chưa gán phòng ban"
                            search-placeholder="Tìm phòng ban…"
                        />
                        <flux:error name="inviteDepartmentId" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Vai trò hệ thống</flux:label>
                        <flux:select wire:model="inviteRole">
                            <option value="sales">Nhân viên kinh doanh</option>
                            <option value="sales-manager">Quản lý kinh doanh</option>
                            <option value="viewer">Chỉ xem</option>
                        </flux:select>
                        <flux:error name="inviteRole" />
                    </flux:field>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-800">
                <flux:button variant="ghost" wire:click="$set('showInviteModal', false)">Hủy</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="sendInvitation">Gửi lời mời</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
