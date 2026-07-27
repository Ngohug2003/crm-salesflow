<div>
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">Cài đặt / Tổ chức</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">Người dùng</h1>
            <p class="mt-2 max-w-2xl text-slate-500">Tra cứu tài khoản theo phạm vi dữ liệu, phòng ban, vai trò và trạng thái hoạt động.</p>
        </div>
        @can('create', \App\Models\User::class)
            <div class="flex items-center gap-2">
                <flux:button variant="filled" icon="envelope" wire:click="openInviteModal">Mời thành viên</flux:button>
                <flux:button variant="primary" icon="plus" wire:click="openCreate">Tạo người dùng</flux:button>
            </div>
        @endcan
    </div>

    @if ($notice)
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
            {{ $notice }}
        </div>
    @endif

    <flux:modal name="user-invite-modal" class="w-full" style="width: 36rem; max-width: 95vw;" wire:close="closeInviteModal">
        @if ($showInviteModal)
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Mời thành viên mới tham gia CRM</flux:heading>
                    <flux:subheading class="mt-1">
                        Hệ thống sẽ gửi email chứa liên kết bảo mật để thành viên tự tạo mật khẩu.
                    </flux:subheading>
                </div>

                <form wire:submit="sendInvitation" class="space-y-4">
                    <flux:input wire:model.blur="inviteName" label="Họ và tên" placeholder="Ví dụ: Trần Văn Nam" required />
                    <flux:input wire:model.blur="inviteEmail" type="email" label="Email nhận lời mời" placeholder="nam@salesflow.test" required />

                    <flux:select wire:model="inviteDepartmentId" label="Phòng ban" placeholder="Chưa gán phòng ban">
                        <option value="">Chưa gán phòng ban</option>
                        @foreach ($this->departmentOptions as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->code }})</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="inviteRole" label="Vai trò">
                        @foreach ($this->roleOptions as $roleKey => $roleLabel)
                            <option value="{{ $roleKey }}">{{ $roleLabel }}</option>
                        @endforeach
                    </flux:select>

                    <div class="flex justify-end gap-3 pt-4">
                        <flux:button variant="ghost" wire:click="closeInviteModal">Hủy</flux:button>
                        <flux:button type="submit" variant="primary" icon="paper-airplane">Gửi email lời mời</flux:button>
                    </div>
                </form>
            </div>
        @endif
    </flux:modal>

    <flux:modal name="user-form" class="w-full" style="width: 56rem; max-width: 95vw;" wire:close="cancelForm">
        @if ($showForm)
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ $form->userId ? 'Chỉnh sửa người dùng' : 'Tạo người dùng' }}</flux:heading>
                    <flux:subheading class="mt-1">
                        {{ $form->userId ? 'Để trống mật khẩu nếu không muốn thay đổi.' : 'Tài khoản mới được xác thực email tự động.' }}
                    </flux:subheading>
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

                    <fieldset>
                        <div class="mb-3">
                            <legend class="font-medium">Vai trò <span class="text-red-500">*</span></legend>
                            <p class="mt-1 text-sm text-slate-500">Có thể chọn nhiều vai trò; data scope rộng nhất sẽ có hiệu lực.</p>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($this->roleAssignmentOptions as $roleKey => $roleDefinition)
                                <label class="flex cursor-pointer gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-emerald-300 dark:border-slate-800 dark:hover:border-emerald-800">
                                    <flux:checkbox wire:model="form.roles" value="{{ $roleKey }}" />
                                    <span class="min-w-0">
                                        <span class="flex flex-wrap items-center gap-2 font-medium">
                                            {{ $roleDefinition['label'] }}
                                            <flux:badge size="sm">{{ $roleDefinition['scope'] }}</flux:badge>
                                        </span>
                                        <span class="mt-1 block text-sm text-slate-500">{{ $roleDefinition['description'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        @error('form.roles')
                            <p class="mt-2 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 dark:border-slate-800 sm:flex-row sm:justify-end">
                        <flux:button type="button" variant="ghost" wire:click="cancelForm">Hủy</flux:button>
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                            {{ $form->userId ? 'Lưu thông tin và vai trò' : 'Tạo người dùng' }}
                        </flux:button>
                    </div>
                </form>
            </div>
        @endif
    </flux:modal>

    @if ($this->pendingInvitations->isNotEmpty())
        <div class="mb-8 rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Lời mời đang chờ kích hoạt ({{ $this->pendingInvitations->count() }})</h3>
                <p class="text-xs text-slate-500">Thành viên chưa nhấp link đặt mật khẩu trong email.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs font-semibold uppercase text-slate-500 dark:border-slate-800">
                        <tr>
                            <th class="py-2.5 px-3">Người được mời</th>
                            <th class="py-2.5 px-3">Phòng ban & Vai trò</th>
                            <th class="py-2.5 px-3">Người mời</th>
                            <th class="py-2.5 px-3">Hạn sử dụng</th>
                            <th class="py-2.5 px-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($this->pendingInvitations as $invitation)
                            <tr>
                                <td class="py-3 px-3">
                                    <p class="font-medium text-slate-900 dark:text-white">{{ $invitation->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $invitation->email }}</p>
                                </td>
                                <td class="py-3 px-3 text-slate-600 dark:text-slate-400">
                                    {{ $invitation->department?->name ?: 'Chưa gán' }}
                                    <span class="inline-block rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $this->roleOptions[$invitation->role] ?? $invitation->role }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-slate-600 dark:text-slate-400">
                                    {{ $invitation->inviter->name }}
                                </td>
                                <td class="py-3 px-3 text-slate-600 dark:text-slate-400">
                                    <span @class(['text-red-500 font-medium' => $invitation->isExpired()])>
                                        {{ $invitation->expires_at->format('H:i d/m/Y') }}
                                        @if ($invitation->isExpired())
                                            (Hết hạn)
                                        @endif
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <flux:button size="sm" variant="ghost" wire:click="resendInvitation({{ $invitation->id }})">Gửi lại</flux:button>
                                        <flux:button size="sm" variant="subtle" color="red" wire:click="revokeInvitation({{ $invitation->id }})">Hủy</flux:button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <section class="crm-card">
        <div class="mb-5">
            <div class="data-list-heading">
                <div>
                    <h2 class="font-semibold">Danh sách người dùng</h2>
                    <p class="mt-1 text-sm text-slate-500">Có {{ $this->users->total() }} tài khoản phù hợp trong phạm vi bạn được phép xem.</p>
                </div>
                @if ($search !== '' || $department !== 'all' || $role !== 'all' || $status !== 'all')
                    <flux:button size="sm" variant="ghost" wire:click="clearFilters">Xóa bộ lọc</flux:button>
                @endif
            </div>

            <div class="data-list-filters xl:grid-cols-[minmax(16rem,1fr)_14rem_13rem_12rem]">
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" label="Tìm kiếm" placeholder="Tên hoặc email" />

                <flux:select wire:model.live="department" label="Phòng ban">
                    <option value="all">Tất cả phòng ban</option>
                    <option value="unassigned">Chưa gán phòng ban</option>
                    @foreach ($this->departmentOptions as $departmentOption)
                        <option value="{{ $departmentOption->id }}">{{ $departmentOption->name }} ({{ $departmentOption->code }})</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="role" label="Vai trò">
                    <option value="all">Tất cả vai trò</option>
                    @foreach ($this->roleOptions as $roleKey => $roleLabel)
                        <option value="{{ $roleKey }}">{{ $roleLabel }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="status" label="Trạng thái">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="active">Đang hoạt động</option>
                    <option value="inactive">Ngừng hoạt động</option>
                </flux:select>
            </div>
        </div>

        <div class="data-list-content">
            <x-data-list.loading target="search,department,role,status,clearFilters,gotoPage,nextPage,previousPage" />

            @if ($this->users->isEmpty())
                <x-data-list.empty
                    title="Không tìm thấy người dùng"
                    description="Thử thay đổi từ khóa hoặc các bộ lọc hiện tại."
                    icon="users"
                >
                    @if ($search !== '' || $department !== 'all' || $role !== 'all' || $status !== 'all')
                        <x-slot:action>
                            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">Đặt lại bộ lọc</flux:button>
                        </x-slot:action>
                    @endif
                </x-data-list.empty>
            @else
                <flux:table>
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

                <x-data-list.pagination :paginator="$this->users" />
            @endif
        </div>
    </section>
</div>
