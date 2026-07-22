<div>
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">Cài đặt / Tổ chức</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">Người dùng</h1>
            <p class="mt-2 max-w-2xl text-slate-500">Tra cứu tài khoản theo phạm vi dữ liệu, phòng ban, vai trò và trạng thái hoạt động.</p>
        </div>
        @can('create', \App\Models\User::class)
            <flux:button variant="primary" icon="plus" wire:click="openCreate">Tạo người dùng</flux:button>
        @endcan
    </div>

    @if ($notice)
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
            {{ $notice }}
        </div>
    @endif

    @if ($showForm)
        <section class="crm-card mb-6" aria-labelledby="user-form-title">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <h2 id="user-form-title" class="text-lg font-semibold">{{ $form->userId ? 'Chỉnh sửa người dùng' : 'Tạo người dùng' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $form->userId ? 'Để trống mật khẩu nếu không muốn thay đổi.' : 'Tài khoản mới được xác thực email tự động và chưa được gán vai trò.' }}
                    </p>
                </div>
                <flux:button variant="ghost" icon="x-mark" square wire:click="cancelForm" aria-label="Đóng biểu mẫu" />
            </div>

            <form wire:submit="save" class="space-y-5">
                <div class="grid gap-5 md:grid-cols-2">
                    <flux:input wire:model.blur="form.name" label="Họ và tên" placeholder="Ví dụ: Nguyễn Văn An" required />
                    <flux:input wire:model.blur="form.email" type="email" label="Email đăng nhập" placeholder="name@salesflow.test" required />

                    <flux:select wire:model="form.departmentId" label="Phòng ban" placeholder="Chưa gán phòng ban">
                        <option value="">Chưa gán phòng ban</option>
                        @foreach ($this->formDepartmentOptions as $departmentOption)
                            <option value="{{ $departmentOption->id }}">{{ $departmentOption->name }} ({{ $departmentOption->code }})</option>
                        @endforeach
                    </flux:select>

                    <div class="rounded-xl border border-slate-200 px-4 py-3 dark:border-slate-800">
                        <flux:switch
                            wire:model="form.isActive"
                            label="Tài khoản đang hoạt động"
                            description="Tắt tùy chọn này để khóa đăng nhập của tài khoản."
                        />
                    </div>

                    <flux:input
                        wire:model="form.password"
                        type="password"
                        label="{{ $form->userId ? 'Mật khẩu mới (không bắt buộc)' : 'Mật khẩu' }}"
                        autocomplete="new-password"
                        :required="$form->userId === null"
                        viewable
                    />
                    <flux:input
                        wire:model="form.passwordConfirmation"
                        type="password"
                        label="Xác nhận mật khẩu"
                        autocomplete="new-password"
                        :required="$form->userId === null"
                        viewable
                    />
                </div>

                <flux:callout icon="information-circle" heading="Vai trò được quản lý riêng">
                    P2-06 chỉ lưu thông tin tài khoản. Việc gán hoặc thay đổi vai trò sẽ được thực hiện ở P2-07.
                </flux:callout>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 dark:border-slate-800 sm:flex-row sm:justify-end">
                    <flux:button type="button" variant="ghost" wire:click="cancelForm">Hủy</flux:button>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                        {{ $form->userId ? 'Lưu thay đổi' : 'Tạo người dùng' }}
                    </flux:button>
                </div>
            </form>
        </section>
    @endif

    <section class="crm-card">
        <div class="mb-5">
            <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
                <div>
                    <h2 class="font-semibold">Danh sách người dùng</h2>
                    <p class="mt-1 text-sm text-slate-500">Có {{ $this->users->total() }} tài khoản phù hợp trong phạm vi bạn được phép xem.</p>
                </div>
                @if ($search !== '' || $department !== 'all' || $role !== 'all' || $status !== 'all')
                    <flux:button size="sm" variant="ghost" wire:click="clearFilters">Xóa bộ lọc</flux:button>
                @endif
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(16rem,1fr)_14rem_13rem_12rem]">
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Tìm theo tên hoặc email" aria-label="Tìm người dùng" />

                <flux:select wire:model.live="department" aria-label="Lọc phòng ban">
                    <option value="all">Tất cả phòng ban</option>
                    <option value="unassigned">Chưa gán phòng ban</option>
                    @foreach ($this->departmentOptions as $departmentOption)
                        <option value="{{ $departmentOption->id }}">{{ $departmentOption->name }} ({{ $departmentOption->code }})</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="role" aria-label="Lọc vai trò">
                    <option value="all">Tất cả vai trò</option>
                    @foreach ($this->roleOptions as $roleKey => $roleLabel)
                        <option value="{{ $roleKey }}">{{ $roleLabel }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="status" aria-label="Lọc trạng thái">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="active">Đang hoạt động</option>
                    <option value="inactive">Ngừng hoạt động</option>
                </flux:select>
            </div>
        </div>

        @if ($this->users->isEmpty())
            <div class="grid min-h-52 place-items-center rounded-xl border border-dashed border-slate-300 text-center dark:border-slate-700">
                <div class="px-6">
                    <p class="font-medium">Không tìm thấy người dùng</p>
                    <p class="mt-1 text-sm text-slate-500">Thử thay đổi từ khóa hoặc các bộ lọc hiện tại.</p>
                </div>
            </div>
        @else
            <flux:table :paginate="$this->users">
                <flux:table.columns>
                    <flux:table.column>Người dùng</flux:table.column>
                    <flux:table.column>Phòng ban</flux:table.column>
                    <flux:table.column>Vai trò</flux:table.column>
                    <flux:table.column>Trạng thái</flux:table.column>
                    <flux:table.column>Xác thực email</flux:table.column>
                    <flux:table.column align="end">Thao tác</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->users as $user)
                        <flux:table.row :key="$user->id">
                            <flux:table.cell variant="strong">
                                <div class="flex min-w-60 items-center gap-3">
                                    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-slate-200 text-sm font-semibold dark:bg-slate-700">
                                        {{ str($user->name)->substr(0, 1)->upper() }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate">{{ $user->name }}</p>
                                        <p class="truncate text-xs font-normal text-slate-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($user->department)
                                    <div class="min-w-40">
                                        <p>{{ $user->department->name }}</p>
                                        <p class="font-mono text-xs text-slate-400">{{ $user->department->code }}</p>
                                    </div>
                                @else
                                    <span class="text-slate-400">Chưa gán</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex min-w-36 flex-wrap gap-1.5">
                                    @forelse ($user->roles as $assignedRole)
                                        <flux:badge size="sm">{{ $this->roleOptions[$assignedRole->name] ?? $assignedRole->name }}</flux:badge>
                                    @empty
                                        <span class="text-slate-400">Chưa gán</span>
                                    @endforelse
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$user->is_active ? 'emerald' : 'red'" size="sm">
                                    {{ $user->is_active ? 'Hoạt động' : 'Ngừng hoạt động' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <span @class([
                                    'text-sm font-medium',
                                    'text-emerald-600 dark:text-emerald-400' => $user->email_verified_at !== null,
                                    'text-amber-600 dark:text-amber-400' => $user->email_verified_at === null,
                                ])>
                                    {{ $user->email_verified_at ? 'Đã xác thực' : 'Chưa xác thực' }}
                                </span>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                @can('update', $user)
                                    <flux:button size="sm" variant="ghost" wire:click="openEdit({{ $user->id }})">Sửa</flux:button>
                                @endcan
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </section>
</div>
