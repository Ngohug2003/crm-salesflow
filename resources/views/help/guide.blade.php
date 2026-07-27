@extends('layouts.app')

@section('content')
    @php
        $user = auth()->user();
        $roleNames = $user->getRoleNames()->join(', ') ?: 'Chưa gán vai trò';
        $departmentName = $user->department->name ?? 'Chưa phân bổ';
    @endphp

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400" wire:navigate>Trang chủ</a>
                <span>/</span>
                <span class="text-slate-900 dark:text-white">Hướng dẫn thao tác</span>
            </div>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">Hướng dẫn sử dụng SalesFlow CRM</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-400">
                Tài liệu nhanh cho người dùng nội bộ: đi theo đúng luồng bán hàng, công việc, import/export và thông báo trong hệ thống.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('leads.index')" wire:navigate variant="primary" icon="funnel">Mở Lead</flux:button>
            <flux:button :href="route('tasks.index')" wire:navigate variant="ghost" icon="check-circle">Mở công việc</flux:button>
        </div>
    </div>

    <section class="crm-card mb-6" aria-labelledby="guide-context-title">
        <div class="grid gap-4 lg:grid-cols-[1.35fr_0.65fr] lg:items-center">
            <div>
                <div class="flex items-center gap-2">
                    <span class="grid size-10 place-items-center rounded-lg bg-emerald-100 text-sm font-semibold text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300">
                        {{ str($user->name)->substr(0, 1)->upper() }}
                    </span>
                    <div>
                        <h2 id="guide-context-title" class="text-base font-semibold text-slate-950 dark:text-white">{{ $user->name }}</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                    </div>
                </div>
                <p class="mt-4 text-sm text-slate-600 dark:text-slate-300">
                    Anh đang thao tác trong phạm vi phòng ban <strong>{{ $departmentName }}</strong>, vai trò <strong>{{ $roleNames }}</strong>.
                    Những nút không đủ quyền sẽ được ẩn trên giao diện và vẫn được kiểm tra lại ở backend.
                </p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                <div class="rounded-lg border border-slate-200 px-4 py-3 dark:border-slate-800">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Quy tắc dữ liệu</p>
                    <p class="mt-1 text-sm font-semibold text-slate-950 dark:text-white">Theo vai trò và phòng ban</p>
                </div>
                <div class="rounded-lg border border-slate-200 px-4 py-3 dark:border-slate-800">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Khi gặp lỗi</p>
                    <p class="mt-1 text-sm font-semibold text-slate-950 dark:text-white">Gửi request ID cho quản trị</p>
                </div>
            </div>
        </div>
    </section>

    <div x-data="{ activeTab: 'sales' }" class="space-y-6">
        <div class="overflow-x-auto border-b border-slate-200 dark:border-slate-800">
            <div class="flex min-w-max gap-1">
                <button type="button"
                    x-on:click="activeTab = 'sales'"
                    :class="activeTab === 'sales' ? 'border-emerald-500 text-emerald-700 dark:text-emerald-300' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'"
                    class="inline-flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition">
                    <flux:icon.arrow-path-rounded-square class="size-4" />
                    Quy trình bán hàng
                </button>
                <button type="button"
                    x-on:click="activeTab = 'tasks'"
                    :class="activeTab === 'tasks' ? 'border-emerald-500 text-emerald-700 dark:text-emerald-300' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'"
                    class="inline-flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition">
                    <flux:icon.check-circle class="size-4" />
                    Công việc
                </button>
                <button type="button"
                    x-on:click="activeTab = 'io'"
                    :class="activeTab === 'io' ? 'border-emerald-500 text-emerald-700 dark:text-emerald-300' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'"
                    class="inline-flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition">
                    <flux:icon.arrow-up-tray class="size-4" />
                    Import / Export
                </button>
                <button type="button"
                    x-on:click="activeTab = 'notify'"
                    :class="activeTab === 'notify' ? 'border-emerald-500 text-emerald-700 dark:text-emerald-300' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'"
                    class="inline-flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition">
                    <flux:icon.bell class="size-4" />
                    Thông báo & nhật ký
                </button>
            </div>
        </div>

        <section x-show="activeTab === 'sales'" class="grid gap-6 lg:grid-cols-[0.85fr_1.15fr]" aria-labelledby="sales-guide-title">
            <div class="crm-card">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Luồng chuẩn</p>
                <h2 id="sales-guide-title" class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">Từ Lead đến chốt đơn</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                    Mỗi khách hàng nên có owner rõ ràng, lịch chăm sóc rõ ràng và lịch sử trạng thái đầy đủ.
                </p>
                <div class="mt-5 space-y-3">
                    @foreach ([
                        ['Lead', 'Tạo hoặc import khách hàng tiềm năng, kiểm tra trùng email/số điện thoại.'],
                        ['Chăm sóc', 'Tạo task gọi điện, họp, gửi báo giá hoặc follow-up.'],
                        ['Chuyển đổi', 'Khi đủ điều kiện, chuyển Lead thành Company, Contact và Opportunity.'],
                        ['Pipeline', 'Cập nhật stage bằng danh sách hoặc Kanban để phản ánh tiến độ bán hàng.'],
                        ['Won/Lost', 'Chốt thắng hoặc thua, ghi lý do để báo cáo doanh thu và phễu chính xác.'],
                    ] as [$title, $description])
                        <div class="flex gap-3">
                            <span class="mt-1 size-2 rounded-full bg-emerald-500"></span>
                            <div>
                                <p class="text-sm font-semibold text-slate-950 dark:text-white">{{ $title }}</p>
                                <p class="text-sm text-slate-600 dark:text-slate-400">{{ $description }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="crm-card">
                <h3 class="text-sm font-semibold text-slate-950 dark:text-white">Thao tác thường dùng</h3>
                <div class="mt-4 divide-y divide-slate-200 rounded-lg border border-slate-200 dark:divide-slate-800 dark:border-slate-800">
                    @foreach ([
                        ['Tạo Lead mới', 'Nhập thông tin liên hệ, nguồn lead, owner và tags.', route('leads.create'), 'plus-circle'],
                        ['Xem danh sách Lead', 'Tìm kiếm, lọc trạng thái, lọc nguồn và kiểm tra dữ liệu trùng.', route('leads.index'), 'funnel'],
                        ['Mở Kanban cơ hội', 'Kéo thả cơ hội qua các stage khi chăm sóc bán hàng.', route('opportunities.kanban'), 'view-columns'],
                        ['Xem báo cáo phễu', 'Theo dõi số lượng lead/cơ hội theo từng trạng thái.', route('reports.funnel'), 'chart-bar'],
                    ] as [$title, $description, $url, $icon])
                        <a href="{{ $url }}" wire:navigate class="flex items-center justify-between gap-4 px-4 py-3 transition hover:bg-slate-50 dark:hover:bg-slate-900">
                            <span class="flex min-w-0 items-start gap-3">
                                <flux:icon :name="$icon" class="mt-0.5 size-4 shrink-0 text-slate-400" />
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium text-slate-950 dark:text-white">{{ $title }}</span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $description }}</span>
                                </span>
                            </span>
                            <flux:icon.chevron-right class="size-4 shrink-0 text-slate-400" />
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <section x-show="activeTab === 'tasks'" class="grid gap-6 lg:grid-cols-3" aria-labelledby="task-guide-title">
            <div class="crm-card lg:col-span-1">
                <h2 id="task-guide-title" class="text-lg font-semibold text-slate-950 dark:text-white">Cách làm việc với Task</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                    Task giúp biến việc chăm sóc khách hàng thành lịch làm việc có người chịu trách nhiệm, hạn xử lý và lịch sử trao đổi.
                </p>
                <div class="mt-5 flex flex-wrap gap-2">
                    <flux:button :href="route('tasks.create')" wire:navigate variant="primary" icon="plus">Tạo task</flux:button>
                    <flux:button :href="route('tasks.kanban')" wire:navigate variant="ghost" icon="view-columns">Kanban</flux:button>
                    <flux:button :href="route('tasks.calendar')" wire:navigate variant="ghost" icon="calendar">Lịch</flux:button>
                </div>
            </div>

            <div class="crm-card lg:col-span-2">
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <flux:icon.list-bullet class="size-5 text-slate-400" />
                        <h3 class="mt-3 text-sm font-semibold text-slate-950 dark:text-white">Danh sách</h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Dùng khi cần tìm, lọc theo trạng thái, ưu tiên hoặc người phụ trách.</p>
                    </div>
                    <div>
                        <flux:icon.view-columns class="size-5 text-slate-400" />
                        <h3 class="mt-3 text-sm font-semibold text-slate-950 dark:text-white">Kanban</h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Dùng để cập nhật tiến độ nhanh theo cột: chưa làm, đang làm, hoàn thành.</p>
                    </div>
                    <div>
                        <flux:icon.chat-bubble-left-right class="size-5 text-slate-400" />
                        <h3 class="mt-3 text-sm font-semibold text-slate-950 dark:text-white">Bình luận</h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Dùng @tên để nhắc đồng nghiệp, người được nhắc sẽ nhận thông báo.</p>
                    </div>
                </div>
            </div>
        </section>

        <section x-show="activeTab === 'io'" class="grid gap-6 lg:grid-cols-2" aria-labelledby="io-guide-title">
            <div class="crm-card">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-sky-600 dark:text-sky-400">Import</p>
                        <h2 id="io-guide-title" class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">Nhập Lead từ CSV</h2>
                    </div>
                    <flux:button :href="route('imports.leads')" wire:navigate variant="ghost" icon="arrow-up-tray">Mở import</flux:button>
                </div>
                <ol class="mt-5 space-y-4">
                    @foreach ([
                        ['Upload', 'Tải lên CSV, hệ thống kiểm tra định dạng và đọc trước một phần dữ liệu.'],
                        ['Mapping', 'Ghép cột trong file với trường của CRM như họ tên, email, điện thoại, nguồn.'],
                        ['Duplicate', 'Chọn cách xử lý dữ liệu trùng: bỏ qua, cập nhật hoặc tạo bản ghi mới.'],
                        ['Queue', 'Xử lý theo lô ở hàng đợi, theo dõi tiến độ và tải file lỗi khi có dòng sai.'],
                    ] as $index => [$title, $description])
                        <li class="flex gap-3">
                            <span class="grid size-6 shrink-0 place-items-center rounded-full bg-sky-100 text-xs font-semibold text-sky-700 dark:bg-sky-400/10 dark:text-sky-300">{{ $index + 1 }}</span>
                            <span>
                                <span class="block text-sm font-semibold text-slate-950 dark:text-white">{{ $title }}</span>
                                <span class="block text-sm text-slate-600 dark:text-slate-400">{{ $description }}</span>
                            </span>
                        </li>
                    @endforeach
                </ol>
            </div>

            <div class="crm-card">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">Export</p>
                <h2 class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">Xuất dữ liệu an toàn</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                    Export chạy nền để màn hình không bị đơ. Khi file sẵn sàng, hệ thống gửi thông báo kèm link tải có chữ ký và thời hạn.
                </p>
                <div class="mt-5 rounded-lg border border-slate-200 dark:border-slate-800">
                    <div class="flex items-start gap-3 border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                        <flux:icon.shield-check class="mt-0.5 size-4 text-emerald-500" />
                        <p class="text-sm text-slate-600 dark:text-slate-400">Dữ liệu export luôn áp dụng phạm vi quyền của người yêu cầu.</p>
                    </div>
                    <div class="flex items-start gap-3 px-4 py-3">
                        <flux:icon.clock class="mt-0.5 size-4 text-amber-500" />
                        <p class="text-sm text-slate-600 dark:text-slate-400">Link tải có thời hạn, không dùng đường dẫn public cố định.</p>
                    </div>
                </div>
            </div>
        </section>

        <section x-show="activeTab === 'notify'" class="grid gap-6 lg:grid-cols-2" aria-labelledby="notify-guide-title">
            <div class="crm-card">
                <h2 id="notify-guide-title" class="text-lg font-semibold text-slate-950 dark:text-white">Thông báo</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                    Vào trung tâm thông báo để xem việc được giao, kết quả import/export và các nhắc việc quan trọng.
                </p>
                <div class="mt-5 flex flex-wrap gap-2">
                    <flux:button :href="route('notifications.index')" wire:navigate variant="primary" icon="bell">Mở thông báo</flux:button>
                    <flux:button :href="route('help.roles')" wire:navigate variant="ghost" icon="question-mark-circle">Xem quyền của tôi</flux:button>
                </div>
            </div>

            <div class="crm-card">
                <h2 class="text-lg font-semibold text-slate-950 dark:text-white">Nhật ký kiểm toán</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                    Nhật ký lưu các thay đổi quan trọng như tạo/sửa/xóa, phân công, import/export và thao tác quản trị.
                    Chỉ Super Admin hoặc Admin thuộc phòng IT được xem.
                </p>
                @can('viewAny', \Spatie\Activitylog\Models\Activity::class)
                    <div class="mt-5">
                        <flux:button :href="route('audit-logs.index')" wire:navigate variant="ghost" icon="clipboard-document-list">Mở nhật ký kiểm toán</flux:button>
                    </div>
                @else
                    <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-400">
                        Tài khoản hiện tại không có quyền xem nhật ký kiểm toán.
                    </div>
                @endcan
            </div>
        </section>
    </div>
@endsection
