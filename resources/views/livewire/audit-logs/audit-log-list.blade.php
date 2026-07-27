<div
    x-data="{ realtime: window.salesflowRealtimeState ?? 'connecting' }"
    x-on:salesflow-realtime-state.window="realtime = $event.detail.state"
>
    <div class="mb-8">
        <p class="text-sm text-slate-500">Cài đặt / Bảo mật</p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight">Nhật ký kiểm toán</h1>
        <p class="mt-2 max-w-3xl text-slate-500">Theo dõi ai đã thay đổi dữ liệu, thời điểm thực hiện và giá trị trước–sau. Nhật ký này chỉ có thể đọc, không thể sửa hoặc xóa trên giao diện.</p>
    </div>

    @php
        $hasFilters = $search !== '' || $module !== 'all' || $event !== 'all' || $actor !== 'all' || $dateFrom !== '' || $dateTo !== '' || $requestId !== '';
        $activeFilterCount = collect([$search !== '', $module !== 'all', $event !== 'all', $actor !== 'all', $dateFrom !== '', $dateTo !== '', $requestId !== ''])
            ->filter()
            ->count();
        $selectedActor = $actor !== 'all' ? $this->options['actors']->firstWhere('id', (int) $actor) : null;
    @endphp

    <section class="crm-card">
        <div class="data-list-heading">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="font-semibold">Hoạt động hệ thống</h2>
                    <flux:badge size="sm" color="blue">Giờ Việt Nam · UTC+7</flux:badge>
                    <span x-cloak x-show="realtime === 'connected'" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                        <span class="size-1.5 rounded-full bg-emerald-500"></span> Realtime đã kết nối
                    </span>
                    <span x-cloak x-show="realtime === 'connecting'" class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                        <span class="size-1.5 animate-pulse rounded-full bg-amber-500"></span> Đang kết nối realtime
                    </span>
                    <span x-cloak x-show="!['connected', 'connecting'].includes(realtime)" class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-950 dark:text-red-300">
                        <span class="size-1.5 rounded-full bg-red-500"></span> Realtime mất kết nối
                    </span>
                </div>
                <p class="mt-1 text-sm text-slate-500">Có {{ $this->activities->total() }} bản ghi phù hợp.</p>
            </div>
            @if ($hasFilters)
                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">Xóa bộ lọc</flux:button>
            @endif
        </div>

        @if ($realtimeNotice)
            <div class="mb-5 flex items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
                <span class="flex items-center gap-2"><span class="size-2 rounded-full bg-emerald-500"></span>{{ $realtimeNotice }}</span>
                <button type="button" class="font-medium hover:underline" wire:click="$set('realtimeNotice', null)">Ẩn</button>
            </div>
        @endif

        <div class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/80 dark:border-slate-800 dark:bg-slate-900/50">
            <div class="flex flex-col justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-slate-800 sm:flex-row sm:items-center">
                <div class="flex items-center gap-3">
                    <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-white text-slate-500 shadow-sm ring-1 ring-slate-200 dark:bg-slate-800 dark:ring-slate-700">⌁</span>
                    <div>
                        <p class="text-sm font-semibold">Bộ lọc nhật ký</p>
                        <p class="text-xs text-slate-500">
                            {{ $hasFilters ? "Đang áp dụng {$activeFilterCount} điều kiện" : 'Thu hẹp kết quả theo nội dung, người thực hiện và thời gian' }}
                        </p>
                    </div>
                </div>
                @if ($hasFilters)
                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">Đặt lại</flux:button>
                @endif
            </div>

            <div class="grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-12">
                <div class="md:col-span-2 xl:col-span-5">
                    <label class="mb-1.5 block text-xs font-medium text-slate-600 dark:text-slate-300" for="audit-search">Tìm kiếm</label>
                    <flux:input id="audit-search" wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Nội dung, tên hoặc email người thực hiện..." />
                </div>

                <div class="xl:col-span-3">
                    <label class="mb-1.5 block text-xs font-medium text-slate-600 dark:text-slate-300" for="audit-actor">Người thực hiện</label>
                    <flux:select id="audit-actor" wire:model.live="actor">
                        <option value="all">Tất cả người thực hiện</option>
                        @foreach ($this->options['actors'] as $actorOption)
                            <option value="{{ $actorOption->id }}">{{ $actorOption->name }} — {{ $actorOption->email }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="xl:col-span-2">
                    <label class="mb-1.5 block text-xs font-medium text-slate-600 dark:text-slate-300" for="audit-module">Phân hệ</label>
                    <flux:select id="audit-module" wire:model.live="module">
                        <option value="all">Tất cả phân hệ</option>
                        @foreach ($this->options['modules'] as $moduleOption)
                            <option value="{{ $moduleOption }}">{{ str($moduleOption)->headline() }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="xl:col-span-2">
                    <label class="mb-1.5 block text-xs font-medium text-slate-600 dark:text-slate-300" for="audit-event">Sự kiện</label>
                    <flux:select id="audit-event" wire:model.live="event">
                        <option value="all">Tất cả sự kiện</option>
                        @foreach ($this->options['events'] as $eventOption)
                            <option value="{{ $eventOption }}">{{ str($eventOption)->headline() }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="xl:col-span-3">
                    <flux:input wire:model.live="dateFrom" type="date" label="Từ ngày" max="{{ $dateTo !== '' ? $dateTo : null }}" />
                </div>
                <div class="xl:col-span-3">
                    <flux:input wire:model.live="dateTo" type="date" label="Đến ngày" min="{{ $dateFrom !== '' ? $dateFrom : null }}" />
                </div>
                <div class="xl:col-span-3">
                    <flux:input id="audit-request-id" wire:model.live.debounce.300ms="requestId" label="Request ID" placeholder="UUID hoặc mã request..." />
                </div>
                <div class="flex items-end md:col-span-2 xl:col-span-3">
                    <p class="pb-2 text-xs text-slate-500">Các thay đổi được áp dụng ngay và đồng bộ vào URL để có thể chia sẻ kết quả lọc.</p>
                </div>
            </div>

            @if ($hasFilters)
                <div class="flex flex-wrap items-center gap-2 border-t border-slate-200 px-4 py-3 dark:border-slate-800">
                    <span class="mr-1 text-xs font-medium text-slate-500">Đang lọc:</span>
                    @if ($search !== '')
                        <flux:badge size="sm">Từ khóa: {{ str($search)->limit(28) }}</flux:badge>
                    @endif
                    @if ($selectedActor)
                        <flux:badge size="sm">Người dùng: {{ $selectedActor->name }}</flux:badge>
                    @endif
                    @if ($module !== 'all')
                        <flux:badge size="sm">Phân hệ: {{ str($module)->headline() }}</flux:badge>
                    @endif
                    @if ($event !== 'all')
                        <flux:badge size="sm">Sự kiện: {{ str($event)->headline() }}</flux:badge>
                    @endif
                    @if ($dateFrom !== '')
                        <flux:badge size="sm">Từ: {{ $dateFrom }}</flux:badge>
                    @endif
                    @if ($dateTo !== '')
                        <flux:badge size="sm">Đến: {{ $dateTo }}</flux:badge>
                    @endif
                    @if ($requestId !== '')
                        <flux:badge size="sm">Request ID: {{ str($requestId)->limit(12) }}</flux:badge>
                    @endif
                </div>
            @endif
        </div>

        <div class="mb-5 flex items-center justify-between gap-4 border-b border-slate-200 pb-3 dark:border-slate-800">
            <div class="inline-flex rounded-lg bg-slate-100 p-1 dark:bg-slate-800" role="tablist" aria-label="Chế độ xem nhật ký">
                <button
                    type="button"
                    role="tab"
                    wire:click="$set('viewMode', 'table')"
                    @class([
                        'rounded-md px-3 py-1.5 text-sm font-medium transition',
                        'bg-white text-slate-950 shadow-sm dark:bg-slate-700 dark:text-white' => $viewMode === 'table',
                        'text-slate-500 hover:text-slate-900 dark:hover:text-white' => $viewMode !== 'table',
                    ])
                    aria-selected="{{ $viewMode === 'table' ? 'true' : 'false' }}"
                >▦ Bảng</button>
                <button
                    type="button"
                    role="tab"
                    wire:click="$set('viewMode', 'log')"
                    @class([
                        'rounded-md px-3 py-1.5 text-sm font-medium transition',
                        'bg-white text-slate-950 shadow-sm dark:bg-slate-700 dark:text-white' => $viewMode === 'log',
                        'text-slate-500 hover:text-slate-900 dark:hover:text-white' => $viewMode !== 'log',
                    ])
                    aria-selected="{{ $viewMode === 'log' ? 'true' : 'false' }}"
                >⌘ Dòng log</button>
            </div>
            <p class="hidden text-xs text-slate-500 sm:block">Chế độ xem được lưu trên URL</p>
        </div>

        <div class="data-list-content">
            <x-data-list.loading target="search,module,event,actor,dateFrom,dateTo,requestId,viewMode,clearFilters,gotoPage,nextPage,previousPage" />

            @if ($this->activities->isEmpty())
                <x-data-list.empty
                    title="Chưa có nhật ký phù hợp"
                    description="Thử thay đổi bộ lọc hoặc thực hiện một thao tác cập nhật dữ liệu."
                    icon="document-magnifying-glass"
                >
                    @if ($hasFilters)
                        <x-slot:action>
                            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">Đặt lại bộ lọc</flux:button>
                        </x-slot:action>
                    @endif
                </x-data-list.empty>
            @else
                @if ($viewMode === 'table')
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Thời gian (Việt Nam)</flux:table.column>
                            <flux:table.column>Người thực hiện</flux:table.column>
                            <flux:table.column>Phân hệ / Sự kiện</flux:table.column>
                            <flux:table.column>Nội dung</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($this->activities as $activity)
                                <flux:table.row :key="$activity->id" @class([
                                    'bg-emerald-50/80 dark:bg-emerald-950/20' => $latestRealtimeActivityId === $activity->id,
                                    'cursor-pointer hover:bg-slate-100/80 dark:hover:bg-slate-800/80' => true,
                                ]) wire:click="selectActivity({{ $activity->id }})">
                                    <flux:table.cell>
                                        <p class="min-w-36 font-medium">{{ $activity->created_at?->timezone(config('crm.display_timezone'))->format('d/m/Y H:i:s') }}</p>
                                        <p class="text-xs text-slate-500">Log #{{ $activity->id }}</p>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <p class="min-w-48 font-medium">{{ $activity->causer?->name ?? 'Hệ thống' }}</p>
                                        <p class="text-xs text-slate-500">{{ $activity->causer?->email ?? 'Không có tài khoản' }}</p>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <div class="flex min-w-40 flex-wrap gap-2">
                                            <flux:badge size="sm">{{ str($activity->log_name)->headline() }}</flux:badge>
                                            <flux:badge size="sm" color="blue">{{ str($activity->event)->headline() }}</flux:badge>
                                        </div>
                                        <p class="mt-1 font-mono text-xs text-slate-400">{{ class_basename((string) $activity->subject_type) }} #{{ $activity->subject_id ?? '—' }}</p>
                                        @if ($activity->request_id)
                                            <p class="mt-1 font-mono text-xs text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 cursor-pointer" 
                                               title="Lọc theo Request ID"
                                               wire:click.stop="$set('requestId', '{{ $activity->request_id }}')">
                                                Req: {{ str($activity->request_id)->limit(12) }}
                                            </p>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <div class="min-w-80 max-w-2xl flex items-center justify-between gap-4">
                                            <div>
                                                <p>{{ $activity->description }}</p>
                                                @include('livewire.audit-logs.partials.details', ['activity' => $activity])
                                            </div>
                                            <flux:button size="xs" variant="subtle" icon="eye" wire:click.stop="selectActivity({{ $activity->id }})">Chi tiết</flux:button>
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @else
                    <div class="overflow-hidden rounded-xl border border-blue-950 bg-[#050f27] shadow-inner shadow-black/30">
                        <div class="flex items-center gap-2 border-b border-blue-950 bg-[#071632] px-4 py-3 font-mono text-xs font-semibold uppercase tracking-wider text-blue-300">
                            <span class="text-amber-400">⚠</span>
                            Nhật ký hoạt động hệ thống
                        </div>
                        <div class="max-h-[38rem] overflow-auto p-4 font-mono text-xs leading-6 sm:text-sm">
                            @foreach ($this->activities as $activity)
                                <details wire:key="audit-log-{{ $activity->id }}" @class([
                                    'group border-l-2 pl-2 hover:border-emerald-500 hover:bg-white/[0.025]',
                                    'border-emerald-400 bg-emerald-500/10' => $latestRealtimeActivityId === $activity->id,
                                    'border-transparent' => $latestRealtimeActivityId !== $activity->id,
                                ])>
                                    <summary class="grid cursor-pointer list-none gap-x-3 lg:grid-cols-[10.75rem_1fr]" wire:click.prevent="selectActivity({{ $activity->id }})">
                                        <span class="select-none whitespace-nowrap text-blue-500">[{{ $activity->created_at?->timezone(config('crm.display_timezone'))->format('d/m/Y H:i:s') }}]</span>
                                        <span class="text-emerald-400">
                                            <strong>{{ $activity->causer?->name ?? 'Hệ thống' }}</strong>
                                            <span class="text-emerald-300">{{ $activity->description }}</span>
                                            <span class="text-slate-500">[{{ $activity->log_name }}/{{ $activity->event }} · {{ class_basename((string) $activity->subject_type) }}#{{ $activity->subject_id ?? '—' }}@if($activity->request_id) · Req:{{ str($activity->request_id)->limit(8) }}@endif]</span>
                                        </span>
                                    </summary>
                                    <div class="ml-0 mt-2 rounded-lg border border-slate-800 bg-black/30 p-3 text-slate-300 lg:ml-[11.5rem]">
                                        @include('livewire.audit-logs.partials.details', ['activity' => $activity, 'console' => true])
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </div>
                @endif

                <x-data-list.pagination :paginator="$this->activities" />
            @endif
        </div>
    </section>

    {{-- Slide-over Detail Drawer --}}
    @if ($selectedActivityId !== null)
        @php
            $auditDetail = app(\App\Services\Audit\AuditLogDetailService::class)->getAuditDetail($selectedActivityId);
        @endphp
        @if ($auditDetail)
            <div class="fixed inset-0 z-50 overflow-hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" wire:click="closeDrawer"></div>
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div class="pointer-events-auto w-screen max-w-xl bg-white shadow-2xl dark:bg-slate-900 flex flex-col">
                        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-base font-semibold text-slate-900 dark:text-white" id="slide-over-title">Chi tiết Nhật ký kiểm toán #{{ $auditDetail['id'] }}</h2>
                                    <flux:badge :color="$auditDetail['event_color']" size="sm">{{ $auditDetail['event_label'] }}</flux:badge>
                                </div>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Tạo lúc {{ $auditDetail['created_at'] }} (UTC+7)</p>
                            </div>
                            <flux:button wire:click="closeDrawer" icon="x-mark" variant="ghost" size="sm" />
                        </div>

                        <div class="flex-1 overflow-y-auto p-6 space-y-6">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/50">
                                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Mô tả hành động</h3>
                                <p class="mt-1 text-sm font-medium text-slate-900 dark:text-white">{{ $auditDetail['description'] }}</p>
                                <div class="mt-3 grid grid-cols-2 gap-3 text-xs border-t border-slate-200 pt-3 dark:border-slate-800">
                                    <div>
                                        <span class="text-slate-500">Người thực hiện:</span>
                                        <p class="font-medium text-slate-900 dark:text-white">{{ $auditDetail['causer_name'] }}</p>
                                        <p class="text-[11px] text-slate-500">{{ $auditDetail['causer_email'] }}</p>
                                    </div>
                                    <div>
                                        <span class="text-slate-500">Đối tượng tác động:</span>
                                        <p class="font-medium text-slate-900 dark:text-white">{{ $auditDetail['subject_type'] }}</p>
                                        <p class="text-[11px] text-slate-500">ID: {{ $auditDetail['subject_id'] }}</p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">So sánh biến động dữ liệu (Diff)</h3>
                                @if ($auditDetail['changes'] === [])
                                    <div class="rounded-lg border border-dashed border-slate-300 p-4 text-center text-xs text-slate-500 dark:border-slate-700">
                                        Không có biến động thuộc tính được ghi nhận.
                                    </div>
                                @else
                                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950/50">
                                        <flux:table class="w-full text-left text-xs">
                                            <thead class="border-b border-slate-200 bg-slate-100/50 text-slate-500 dark:border-slate-800 dark:bg-slate-900/50 dark:text-slate-400">
                                                <tr>
                                                    <th class="py-2.5 pl-3 pr-2 font-medium">Trường dữ liệu</th>
                                                    <th class="px-2 py-2.5 font-medium">Giá trị cũ (Old)</th>
                                                    <th class="py-2.5 pl-2 pr-3 font-medium">Giá trị mới (New)</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                                @foreach ($auditDetail['changes'] as $change)
                                                    <tr>
                                                        <td class="py-2.5 pl-3 pr-2 font-medium text-slate-900 dark:text-white">
                                                            <div>{{ $change['label'] }}</div>
                                                            <div class="font-mono text-[10px] text-slate-400">{{ $change['field'] }}</div>
                                                        </td>
                                                        <td class="px-2 py-2.5 text-red-700 bg-red-50/50 dark:text-red-300 dark:bg-red-950/20 font-mono break-all">
                                                            {{ $change['old'] }}
                                                        </td>
                                                        <td class="py-2.5 pl-2 pr-3 text-emerald-700 bg-emerald-50/50 dark:text-emerald-300 dark:bg-emerald-950/20 font-mono break-all">
                                                            {{ $change['new'] }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </flux:table>
                                    </div>
                                @endif
                            </div>

                            <div class="space-y-3">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Thông tin kỹ thuật (Request Metadata)</h3>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs space-y-2 dark:border-slate-800 dark:bg-slate-950/50">
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Request ID:</span>
                                        <code class="font-mono text-slate-900 dark:text-white">{{ $auditDetail['request_id'] }}</code>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Địa chỉ IP:</span>
                                        <code class="font-mono text-slate-900 dark:text-white">{{ $auditDetail['ip_address'] }}</code>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block mb-1">User Agent:</span>
                                        <code class="font-mono text-[11px] text-slate-700 dark:text-slate-300 break-all block bg-white p-2 rounded border border-slate-200 dark:bg-slate-900 dark:border-slate-800">{{ $auditDetail['user_agent'] }}</code>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-slate-200 bg-slate-50 px-6 py-3 dark:border-slate-800 dark:bg-slate-950/50 flex justify-end">
                            <flux:button wire:click="closeDrawer" variant="ghost" size="sm">Đóng</flux:button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
