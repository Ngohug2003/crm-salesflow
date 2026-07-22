@extends('layouts.app')

@section('content')
    <div class="mb-8">
        <p class="text-sm text-slate-500">Trợ giúp / Phân quyền</p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight">Vai trò và quyền</h1>
        <p class="mt-2 max-w-3xl text-slate-500">Tra cứu vai trò của bạn, phạm vi dữ liệu và những thao tác mỗi vai trò được phép thực hiện trong SalesFlow CRM.</p>
    </div>

    <section class="crm-card mb-6 border-emerald-200! bg-emerald-50/60! dark:border-emerald-900! dark:bg-emerald-950/20!" aria-labelledby="current-role-title">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Quyền hiện tại của bạn</p>
                <h2 id="current-role-title" class="mt-1 text-xl font-semibold">{{ auth()->user()->name }}</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse ($currentUserSummary['roles'] as $role)
                        <flux:badge color="emerald">{{ $role['label'] }}</flux:badge>
                    @empty
                        <flux:badge color="amber">Chưa được gán vai trò</flux:badge>
                    @endforelse
                </div>
            </div>

            <dl class="grid gap-4 sm:grid-cols-2 lg:min-w-[30rem]">
                <div class="rounded-xl bg-white/80 px-4 py-3 dark:bg-slate-900/70">
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Phạm vi dữ liệu</dt>
                    <dd class="mt-1 font-medium">{{ $currentUserSummary['scope'] }}</dd>
                </div>
                <div class="rounded-xl bg-white/80 px-4 py-3 dark:bg-slate-900/70">
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Số quyền hiệu lực</dt>
                    <dd class="mt-1 font-medium">
                        {{ $currentUserSummary['permission_count'] }} quyền
                        @if ($currentUserSummary['is_super_admin'])
                            <span class="text-xs font-normal text-slate-500">+ quyền tương lai</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </section>

    <div class="mb-5">
        <h2 class="text-lg font-semibold">Hướng dẫn theo từng vai trò</h2>
        <p class="mt-1 text-sm text-slate-500">Chọn một vai trò để xem chi tiết các nhóm quyền. Quyền luôn được kiểm tra ở backend cùng với phạm vi dữ liệu.</p>
    </div>

    @php($currentRoleName = collect($currentUserSummary['roles'])->pluck('name')->first())

    <div class="space-y-4" x-data="{ activeRole: @js($currentRoleName) }">
        @foreach ($roleCatalog as $role)
            @php($isCurrentRole = collect($currentUserSummary['roles'])->contains('name', $role['name']))
            <section @class([
                'crm-card overflow-hidden p-0!',
                'border-emerald-300! dark:border-emerald-800!' => $isCurrentRole,
            ])>
                <button
                    type="button"
                    class="flex w-full cursor-pointer flex-col gap-4 p-5 text-left sm:flex-row sm:items-center sm:justify-between"
                    x-on:click="activeRole = activeRole === @js($role['name']) ? null : @js($role['name'])"
                    x-bind:aria-expanded="activeRole === @js($role['name'])"
                    aria-controls="role-panel-{{ $role['name'] }}"
                    id="role-heading-{{ $role['name'] }}"
                >
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-semibold">{{ $role['label'] }}</h3>
                            @if ($isCurrentRole)
                                <flux:badge color="emerald" size="sm">Vai trò của bạn</flux:badge>
                            @endif
                            <flux:badge size="sm">{{ $role['permission_count'] }} quyền</flux:badge>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">{{ $role['description'] }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-3 text-sm">
                        <span class="rounded-lg bg-slate-100 px-3 py-2 font-medium dark:bg-slate-800">{{ $role['scope'] }}</span>
                        <span
                            class="transition-transform duration-200"
                            x-bind:class="{ 'rotate-180': activeRole === @js($role['name']) }"
                            aria-hidden="true"
                        >⌄</span>
                    </div>
                </button>

                <div
                    x-cloak
                    x-show="activeRole === @js($role['name'])"
                    x-collapse.duration.200ms
                    id="role-panel-{{ $role['name'] }}"
                    role="region"
                    aria-labelledby="role-heading-{{ $role['name'] }}"
                >
                    <div class="border-t border-slate-200 p-5 dark:border-slate-800">
                        @if ($role['is_super_admin'])
                            <flux:callout variant="success" heading="Toàn quyền hệ thống">
                                Super Admin vượt qua Laravel Gate cho mọi quyền hiện tại và những quyền được bổ sung trong tương lai.
                            </flux:callout>
                        @endif

                        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($role['groups'] as $group)
                                <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                                    <h4 class="text-sm font-semibold">{{ $group['label'] }}</h4>
                                    <ul class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                                        @foreach ($group['permissions'] as $permission)
                                            <li class="flex gap-2"><span class="text-emerald-500">✓</span><span>{{ $permission }}</span></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        @endforeach
    </div>
@endsection
