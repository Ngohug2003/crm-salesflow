{{-- Sidebar navigation --}}
{{-- Active state is handled client-side via Alpine because @persist prevents server re-evaluation --}}
<div class="flex h-full flex-col"
     x-data="{ path: window.location.pathname }"
     @popstate.window="path = window.location.pathname"
     x-init="document.addEventListener('livewire:navigated', () => { path = window.location.pathname })">
    <div class="flex items-center justify-between gap-3 px-2 py-2">
        <a href="{{ route('dashboard') }}" wire:navigate.hover class="flex min-w-0 items-center gap-3">
            <!-- <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-emerald-500 font-black text-white">SF</span> -->
            <span x-show="sidebar" x-cloak class="truncate font-semibold">SalesFlow CRM</span>
        </a>
        <button class="lg:hidden" @click="mobileNav=false" aria-label="Đóng menu">
            <flux:icon.x-mark class="size-5" />
        </button>
    </div>

    <nav class="mt-6 flex-1 space-y-6 overflow-y-auto px-1">
        {{-- CRM --}}
        <div>
            <p x-show="sidebar" class="nav-group-label">CRM</p>
            <div class="mt-1 space-y-0.5">
                <a href="{{ route('dashboard') }}" wire:navigate.hover
                   class="nav-link"
                   :class="path === '/dashboard' && 'nav-link-active'">
                    <flux:icon.home class="nav-icon" />
                    <span x-show="sidebar">Dashboard</span>
                </a>
                @can('viewAny', \App\Models\Lead::class)
                    <a href="{{ route('leads.index') }}" wire:navigate.hover
                       class="nav-link"
                       :class="path.startsWith('/leads') && 'nav-link-active'">
                        <flux:icon.funnel class="nav-icon" />
                        <span x-show="sidebar">Khách hàng tiềm năng</span>
                    </a>
                @endcan
                @can('viewAny', \App\Models\Company::class)
                    <a href="{{ route('companies.index') }}" wire:navigate.hover
                       class="nav-link"
                       :class="path.startsWith('/companies') && 'nav-link-active'">
                        <flux:icon.building-office-2 class="nav-icon" />
                        <span x-show="sidebar">Doanh nghiệp</span>
                    </a>
                @endcan
                @can('viewAny', \App\Models\Contact::class)
                    <a href="{{ route('contacts.index') }}" wire:navigate.hover
                       class="nav-link"
                       :class="path.startsWith('/contacts') && 'nav-link-active'">
                        <flux:icon.user-circle class="nav-icon" />
                        <span x-show="sidebar">Người liên hệ</span>
                    </a>
                @endcan
                @can('viewAny', \App\Models\Opportunity::class)
                    <a href="{{ route('opportunities.index') }}" wire:navigate.hover
                       class="nav-link"
                       :class="path.startsWith('/opportunities') && 'nav-link-active'">
                        <flux:icon.briefcase class="nav-icon" />
                        <span x-show="sidebar">Cơ hội bán hàng</span>
                    </a>
                @endcan
                @can('viewAny', \App\Models\Pipeline::class)
                    <a href="{{ route('pipelines.index') }}" wire:navigate.hover
                       class="nav-link"
                       :class="path.startsWith('/pipelines') && 'nav-link-active'">
                        <flux:icon.rectangle-stack class="nav-icon" />
                        <span x-show="sidebar">Quy trình bán hàng</span>
                    </a>
                @endcan
                @can('viewAny', \App\Models\Task::class)
                    <a href="{{ route('tasks.index') }}" wire:navigate.hover
                       class="nav-link"
                       :class="path.startsWith('/tasks') && 'nav-link-active'">
                        <flux:icon.check-circle class="nav-icon" />
                        <span x-show="sidebar">Công việc (Tasks)</span>
                    </a>
                @endcan
            </div>
        </div>

        {{-- Báo cáo & Thống kê --}}
        <div>
            <p x-show="sidebar" class="nav-group-label">Báo cáo & Thống kê</p>
            <div class="mt-1 space-y-0.5">
                @can('reports.view')
                    <a href="{{ route('reports.funnel') }}" wire:navigate.hover
                       class="nav-link"
                       :class="path.startsWith('/reports/funnel') && 'nav-link-active'">
                        <flux:icon.chart-bar class="nav-icon" />
                        <span x-show="sidebar">Báo cáo Phễu (Funnel)</span>
                    </a>
                    <a href="{{ route('reports.revenue') }}" wire:navigate.hover
                       class="nav-link"
                       :class="path.startsWith('/reports/revenue') && 'nav-link-active'">
                        <flux:icon.banknotes class="nav-icon" />
                        <span x-show="sidebar">Báo cáo Doanh thu</span>
                    </a>
                    <a href="{{ route('reports.performance') }}" wire:navigate.hover
                       class="nav-link"
                       :class="path.startsWith('/reports/performance') && 'nav-link-active'">
                        <flux:icon.trophy class="nav-icon" />
                        <span x-show="sidebar">Hiệu suất Sales</span>
                    </a>
                @endcan
            </div>
        </div>

        {{-- Quản trị --}}
        <div>
            <p x-show="sidebar" class="nav-group-label">Quản trị</p>
            <div class="mt-1 space-y-0.5">
                @can('viewAny', \App\Models\User::class)
                    <a href="{{ route('users.index') }}" wire:navigate.hover
                       class="nav-link"
                       :class="path.startsWith('/settings/users') && 'nav-link-active'">
                        <flux:icon.users class="nav-icon" />
                        <span x-show="sidebar">Người dùng</span>
                    </a>
                @endcan
                @can('viewAny', \App\Models\Department::class)
                    <a href="{{ route('departments.index') }}" wire:navigate.hover
                       class="nav-link"
                       :class="path.startsWith('/settings/departments') && 'nav-link-active'">
                        <flux:icon.building-office class="nav-icon" />
                        <span x-show="sidebar">Phòng ban</span>
                    </a>
                @endcan
                @can('viewAny', \Spatie\Activitylog\Models\Activity::class)
                    <a href="{{ route('audit-logs.index') }}" wire:navigate.hover
                       class="nav-link"
                       :class="path.startsWith('/settings/audit-logs') && 'nav-link-active'">
                        <flux:icon.clipboard-document-list class="nav-icon" />
                        <span x-show="sidebar">Nhật ký kiểm toán</span>
                    </a>
                @endcan
                @can('system-console.view')
                    <a href="{{ route('system-console.index') }}" wire:navigate.hover
                       class="nav-link"
                       :class="path.startsWith('/settings/system-console') && 'nav-link-active'">
                        <flux:icon.command-line class="nav-icon" />
                        <span x-show="sidebar">System Console</span>
                    </a>
                @endcan
            </div>
        </div>

        {{-- Cá nhân --}}
        <div>
            <p x-show="sidebar" class="nav-group-label">Cá nhân</p>
            <div class="mt-1 space-y-0.5">
                <a href="{{ route('sessions.index') }}" wire:navigate.hover
                   class="nav-link"
                   :class="path.startsWith('/settings/sessions') && 'nav-link-active'">
                    <flux:icon.device-phone-mobile class="nav-icon" />
                    <span x-show="sidebar">Phiên đăng nhập</span>
                </a>
            </div>
        </div>

        {{-- Trợ giúp --}}
        <div>
            <p x-show="sidebar" class="nav-group-label">Trợ giúp</p>
            <div class="mt-1 space-y-0.5">
                <a href="{{ route('help.guide') }}" wire:navigate.hover
                   class="nav-link"
                   :class="path === '/help/guide' && 'nav-link-active'">
                    <flux:icon.book-open class="nav-icon" />
                    <span x-show="sidebar">Hướng dẫn thao tác</span>
                </a>
                <a href="{{ route('help.roles') }}" wire:navigate.hover
                   class="nav-link"
                   :class="path === '/help/roles' && 'nav-link-active'">
                    <flux:icon.question-mark-circle class="nav-icon" />
                    <span x-show="sidebar">Vai trò & quyền</span>
                </a>
            </div>
        </div>
    </nav>

    {{-- Sidebar footer: user avatar --}}
    <div class="border-t border-slate-200 pt-3 dark:border-slate-800">
        <div class="flex items-center gap-3 rounded-xl px-3 py-2">
            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-emerald-100 text-sm font-semibold text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
            <div x-show="sidebar" class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium">{{ auth()->user()->name }}</p>
                <p class="truncate text-xs text-slate-500">{{ auth()->user()->getRoleNames()->first() ?? 'User' }}</p>
            </div>
        </div>
    </div>
</div>
