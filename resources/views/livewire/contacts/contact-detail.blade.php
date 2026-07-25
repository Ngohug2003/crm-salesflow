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
                <flux:button wire:click="confirmDeleteContact" variant="danger" icon="trash">
                    Xóa
                </flux:button>
            @endcan
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Profile Info, Attachments & Timeline -->
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

            <!-- Attachments Manager -->
            <livewire:customers.customer-attachment-manager :modelType="\App\Models\Contact::class" :modelId="$contact->id" />

            <!-- Timeline Feed -->
            <livewire:customers.customer-timeline-feed :modelType="\App\Models\Contact::class" :modelId="$contact->id" />
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

    <!-- Modal Xác nhận xóa Người liên hệ -->
    <div
        x-data="{ open: @entangle('confirmingDeleteContact') }"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
    >
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200 transform"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150 transform"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-4"
        >
            <div class="flex items-center gap-3 text-red-600 dark:text-red-400">
                <div class="rounded-full bg-red-100 p-2.5 dark:bg-red-950/60">
                    <flux:icon.exclamation-triangle class="size-6" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Xác nhận xóa Người liên hệ</h3>
                    <p class="text-xs text-slate-500">Hành động này sẽ chuyển người liên hệ vào thùng rác.</p>
                </div>
            </div>

            <p class="text-sm text-slate-600 dark:text-slate-300">
                Bạn có chắc chắn muốn xóa người liên hệ <strong class="text-slate-900 dark:text-white">{{ $contact->full_name }}</strong> không?
            </p>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <flux:button wire:click="$set('confirmingDeleteContact', false)" variant="ghost" size="sm">
                    Hủy bỏ
                </flux:button>
                <flux:button wire:click="deleteContact" variant="danger" size="sm">
                    Xác nhận xóa
                </flux:button>
            </div>
        </div>
    </div>
</div>
