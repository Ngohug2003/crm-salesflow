<div>
    <!-- Header -->
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">CRM / Người liên hệ / Chi tiết</p>
            <div class="flex items-center gap-3">
                <h1 class="mt-1 text-3xl font-semibold tracking-tight">{{ $contact->full_name }}</h1>
                @if ($contact->is_primary)
                    <flux:badge color="emerald">Đại diện chính</flux:badge>
                @endif
            </div>
            <p class="mt-2 max-w-3xl text-slate-500">
                {{ $contact->job_title ?: 'Chức vụ không rõ' }} {{ $contact->department_name ? "({$contact->department_name})" : '' }}
                @if ($contact->company)
                    | Trực thuộc: <a href="{{ route('companies.show', $contact->company) }}" wire:navigate class="text-emerald-600 dark:text-emerald-400 font-medium hover:underline">{{ $contact->company->name }}</a>
                @endif
            </p>
        </div>

        <div class="flex items-center gap-2">
            <flux:button :href="route('contacts.index')" wire:navigate variant="ghost" icon="arrow-left">
                Về danh sách
            </flux:button>
            @can('update', $contact)
                <flux:button :href="route('contacts.edit', $contact)" wire:navigate variant="primary" icon="pencil-square">
                    Chỉnh sửa
                </flux:button>
            @endcan
            @can('delete', $contact)
                <flux:button wire:click="deleteContact" wire:confirm="Bạn có chắc chắn muốn xóa người liên hệ này không?" variant="danger" icon="trash">
                    Xóa
                </flux:button>
            @endcan
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Profile Info -->
        <div class="space-y-6 lg:col-span-2">
            <section class="crm-card">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold">Hồ sơ Cá nhân & Liên hệ</h2>
                    <p class="mt-1 text-sm text-slate-500">Thông tin chi tiết liên lạc và công tác.</p>
                </div>

                <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="font-medium text-slate-500">Họ và tên</dt>
                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $contact->full_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Doanh nghiệp trực thuộc</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">
                            @if ($contact->company)
                                <a href="{{ route('companies.show', $contact->company) }}" wire:navigate class="text-emerald-600 hover:underline dark:text-emerald-400 font-medium">{{ $contact->company->name }}</a>
                            @else
                                <span class="text-slate-400 font-normal">Cá nhân tự do</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Chức danh</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">{{ $contact->job_title ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Phòng ban làm việc</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">{{ $contact->department_name ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Số điện thoại chính</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">{{ $contact->phone ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Số điện thoại phụ</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">{{ $contact->secondary_phone ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Email</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">{{ $contact->email ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Ngày sinh</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">{{ $contact->birthday?->format('d/m/Y') ?: '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-slate-500">Địa chỉ</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">{{ $contact->address ?: '—' }}</dd>
                    </div>
                    @if ($contact->notes)
                        <div class="sm:col-span-2">
                            <dt class="font-medium text-slate-500">Ghi chú nội bộ</dt>
                            <dd class="mt-1 rounded-lg bg-slate-50 p-3 text-slate-700 dark:bg-slate-800/80 dark:text-slate-300">
                                {{ $contact->notes }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </section>
        </div>

        <!-- Sidebar Meta -->
        <div class="space-y-6">
            <section class="crm-card">
                <h2 class="text-base font-semibold border-b border-slate-200 pb-3 dark:border-slate-800">Phân công & Khởi tạo</h2>
                <dl class="mt-4 space-y-4 text-sm">
                    <div>
                        <dt class="font-medium text-slate-500">Người phụ trách</dt>
                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $contact->owner?->name ?: 'Chưa phân công' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Phòng ban quản lý</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">{{ $contact->department?->name ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Người khởi tạo</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">{{ $contact->createdBy?->name ?: 'Hệ thống' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Ngày khởi tạo</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">{{ $contact->created_at?->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>
</div>
