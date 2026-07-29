<div>
    <div class="mb-6 flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
        <div>
            <p class="text-sm text-slate-500">Cài đặt / Vai trò và phân quyền</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">Cấu hình quyền theo vai trò</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-500 dark:text-slate-400">
                Điều chỉnh quyền nghiệp vụ của từng vai trò. Thay đổi được áp dụng ngay cho route, sidebar và Policy sau khi lưu.
            </p>
        </div>

        @if (($selectedRoleDefinition['editable'] ?? false) === true)
            <div class="flex flex-wrap items-center gap-2">
                <flux:button
                    type="button"
                    variant="ghost"
                    wire:click="openResetConfirmation"
                    wire:loading.attr="disabled"
                >
                    Khôi phục mặc định
                </flux:button>
                <flux:button
                    type="button"
                    variant="primary"
                    wire:click="openSaveConfirmation"
                    wire:loading.attr="disabled"
                    :disabled="! $hasUnsavedChanges"
                >
                    Lưu thay đổi
                </flux:button>
            </div>
        @endif
    </div>

    @if (session('success'))
        <div role="status" class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    @if ($hasUnsavedChanges)
        <div role="status" class="mb-5 flex flex-col gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 sm:flex-row sm:items-center sm:justify-between dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">
            <span>Vai trò này đang có thay đổi chưa được lưu.</span>
            <button type="button" wire:click="openSaveConfirmation" class="font-medium underline underline-offset-2">
                Xem và lưu thay đổi
            </button>
        </div>
    @endif

    <section class="crm-card mb-6" aria-labelledby="role-selector-title">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 id="role-selector-title" class="text-base font-semibold text-slate-950 dark:text-white">Chọn vai trò</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Chỉ vai trò thấp hơn vai trò hiện tại mới có thể chỉnh sửa. Super Admin luôn có toàn quyền và không thể thay đổi.
                </p>
            </div>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ($roles as $role)
                <button
                    type="button"
                    wire:key="role-selector-{{ $role['name'] }}"
                    wire:click="selectRole('{{ $role['name'] }}')"
                    @class([
                        'rounded-lg border p-4 text-left transition',
                        'border-blue-500 bg-blue-50 ring-1 ring-blue-500 dark:border-blue-400 dark:bg-blue-950/30' => $role['selected'],
                        'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700' => ! $role['selected'],
                    ])
                    aria-pressed="{{ $role['selected'] ? 'true' : 'false' }}"
                >
                    <span class="flex items-center justify-between gap-2">
                        <span class="text-sm font-semibold text-slate-950 dark:text-white">{{ $role['label'] }}</span>
                        <flux:badge :color="$role['editable'] ? 'blue' : 'zinc'" size="sm">
                            {{ $role['editable'] ? 'Có thể sửa' : 'Chỉ xem' }}
                        </flux:badge>
                    </span>
                    <span class="mt-2 block text-xs font-medium text-emerald-700 dark:text-emerald-400">{{ $role['data_scope'] }}</span>
                    <span class="mt-1 line-clamp-2 block text-xs text-slate-500 dark:text-slate-400">{{ $role['description'] }}</span>
                </button>
            @endforeach
        </div>
    </section>

    <section class="crm-card" aria-labelledby="permission-editor-title">
        <div class="mb-5 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <h2 id="permission-editor-title" class="text-base font-semibold text-slate-950 dark:text-white">
                    Quyền của {{ $selectedRoleDefinition['label'] ?? $selectedRole }}
                </h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    {{ ($selectedRoleDefinition['editable'] ?? false) ? 'Bật hoặc tắt quyền rồi lưu để áp dụng.' : 'Vai trò này được khóa để tránh tự nâng quyền hoặc làm mất quyền quản trị.' }}
                </p>
            </div>

            <div class="grid w-full gap-3 sm:grid-cols-2 xl:w-auto xl:min-w-[38rem]">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    label="Tìm Permission"
                    placeholder="Ví dụ: companies.update"
                />
                <x-forms.smart-select wire:model.live="module" label="Phân hệ">
                    <option value="all">Tất cả phân hệ ({{ count($allModuleKeys) }})</option>
                    @foreach ($allModuleKeys as $label => $key)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </x-forms.smart-select>
            </div>
        </div>

        @if ($search !== '' || $module !== 'all')
            <div class="mb-4 flex items-center justify-between border-y border-slate-200 py-2.5 text-xs text-slate-500 dark:border-slate-800">
                <span>Đang lọc danh sách Permission</span>
                <button type="button" wire:click="clearFilters" class="font-medium text-blue-600 hover:underline dark:text-blue-400">
                    Xóa bộ lọc
                </button>
            </div>
        @endif

        @if ($modules === [])
            <div class="grid min-h-48 place-items-center rounded-lg border border-dashed border-slate-300 text-center dark:border-slate-700">
                <div class="px-6">
                    <p class="font-medium text-slate-900 dark:text-white">Không tìm thấy Permission phù hợp</p>
                    <p class="mt-1 text-sm text-slate-500">Hãy xóa từ khóa hoặc chọn lại phân hệ.</p>
                </div>
            </div>
        @else
            <div
                wire:key="permission-accordion-{{ md5($search.'|'.$module) }}"
                class="space-y-3"
                x-data="{ openModule: @js($modules[0]['key'] ?? null) }"
            >
                @foreach ($modules as $permissionModule)
                    @php
                        $modulePermissionNames = array_column($permissionModule['permissions'], 'name');
                        $assignedPermissionCount = count(array_intersect($modulePermissionNames, $selectedPermissions));
                    @endphp
                    <section
                        wire:key="permission-module-{{ $permissionModule['key'] }}"
                        class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-800"
                    >
                        <button
                            type="button"
                            id="permission-module-heading-{{ $permissionModule['key'] }}"
                            class="flex w-full items-center justify-between gap-4 bg-slate-50 px-4 py-3 text-left transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 dark:bg-slate-900/70 dark:hover:bg-slate-800"
                            x-on:click="openModule = openModule === @js($permissionModule['key']) ? null : @js($permissionModule['key'])"
                            x-bind:aria-expanded="openModule === @js($permissionModule['key'])"
                            aria-controls="permission-module-panel-{{ $permissionModule['key'] }}"
                        >
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ $permissionModule['label'] }}
                                </span>
                                <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">
                                    Đã cấp {{ $assignedPermissionCount }}/{{ count($permissionModule['permissions']) }} quyền
                                </span>
                            </span>
                            <flux:icon.chevron-down
                                class="size-4 shrink-0 text-slate-500 transition-transform duration-200"
                                x-bind:class="{ 'rotate-180': openModule === @js($permissionModule['key']) }"
                                aria-hidden="true"
                            />
                        </button>

                        <div
                            x-cloak
                            x-show="openModule === @js($permissionModule['key'])"
                            x-collapse.duration.200ms
                            id="permission-module-panel-{{ $permissionModule['key'] }}"
                            role="region"
                            aria-labelledby="permission-module-heading-{{ $permissionModule['key'] }}"
                        >
                            <div class="divide-y divide-slate-200 border-t border-slate-200 dark:divide-slate-800 dark:border-slate-800">
                                @foreach ($permissionModule['permissions'] as $permission)
                                    @php
                                        $isAssigned = in_array($permission['name'], $selectedPermissions, true);
                                        $isEditable = in_array($permission['name'], $editablePermissionNames, true);
                                    @endphp
                                    <div wire:key="permission-{{ $permission['name'] }}" class="grid gap-3 px-4 py-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <code class="text-xs font-medium text-slate-900 dark:text-white">{{ $permission['name'] }}</code>
                                                @if (! $isEditable && ($selectedRoleDefinition['editable'] ?? false))
                                                    <flux:badge color="amber" size="sm">Được bảo vệ</flux:badge>
                                                @endif
                                            </div>
                                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $permission['label'] }}</p>
                                        </div>

                                        <button
                                            type="button"
                                            role="switch"
                                            aria-checked="{{ $isAssigned ? 'true' : 'false' }}"
                                            aria-label="{{ $isAssigned ? 'Thu hồi' : 'Cấp' }} quyền {{ $permission['name'] }}"
                                            @if ($isEditable)
                                                wire:click="togglePermission('{{ $permission['name'] }}')"
                                            @else
                                                disabled
                                            @endif
                                            @class([
                                                'relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2',
                                                'cursor-pointer bg-blue-600' => $isAssigned && $isEditable,
                                                'cursor-pointer bg-slate-300 dark:bg-slate-700' => ! $isAssigned && $isEditable,
                                                'cursor-not-allowed bg-emerald-500/50' => $isAssigned && ! $isEditable,
                                                'cursor-not-allowed bg-slate-200 dark:bg-slate-800' => ! $isAssigned && ! $isEditable,
                                            ])
                                        >
                                            <span
                                                aria-hidden="true"
                                                @class([
                                                    'pointer-events-none inline-block size-5 rounded-full bg-white shadow-sm transition',
                                                    'translate-x-5' => $isAssigned,
                                                    'translate-x-0' => ! $isAssigned,
                                                ])
                                            ></span>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </section>

    <flux:modal name="discard-role-permission-changes" wire:model="showDiscardConfirmation" class="md:w-[32rem]">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Bỏ thay đổi chưa lưu?</flux:heading>
                <flux:text class="mt-2">Các Permission vừa thay đổi của vai trò hiện tại sẽ không được lưu.</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="cancelRoleSelection">Tiếp tục chỉnh sửa</flux:button>
                <flux:button type="button" variant="danger" wire:click="discardAndSelectRole">Bỏ thay đổi</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="save-role-permissions" wire:model="showSaveConfirmation" class="md:w-[32rem]">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Lưu cấu hình quyền?</flux:heading>
                <flux:text class="mt-2">
                    Quyền mới của <strong>{{ $selectedRoleDefinition['label'] ?? $selectedRole }}</strong> sẽ có hiệu lực ngay với các tài khoản đang sử dụng vai trò này.
                </flux:text>
            </div>
            <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm dark:bg-slate-900">
                <p>Cấp thêm: <strong>{{ count(array_diff($selectedPermissions, $initialPermissions)) }}</strong> quyền</p>
                <p class="mt-1">Thu hồi: <strong>{{ count(array_diff($initialPermissions, $selectedPermissions)) }}</strong> quyền</p>
            </div>
            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showSaveConfirmation', false)">Hủy</flux:button>
                <flux:button type="button" variant="primary" wire:click="savePermissions" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="savePermissions">Lưu cấu hình</span>
                    <span wire:loading wire:target="savePermissions">Đang lưu...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="reset-role-permissions" wire:model="showResetConfirmation" class="md:w-[32rem]">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Khôi phục quyền mặc định?</flux:heading>
                <flux:text class="mt-2">
                    Mọi tùy chỉnh của <strong>{{ $selectedRoleDefinition['label'] ?? $selectedRole }}</strong> sẽ được thay bằng cấu hình mặc định trong hệ thống.
                </flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showResetConfirmation', false)">Hủy</flux:button>
                <flux:button type="button" variant="danger" wire:click="resetToDefaults" wire:loading.attr="disabled">
                    Khôi phục mặc định
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
