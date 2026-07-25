<div>
    <!-- Header -->
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">CRM / Doanh nghiệp / Chi tiết</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">{{ $company->name }}</h1>
            <p class="mt-2 max-w-3xl text-slate-500">
                Mã số thuế: <span class="font-mono text-slate-700 dark:text-slate-300">{{ $company->tax_code ?: '—' }}</span> | Ngành nghề: <span class="font-medium text-slate-700 dark:text-slate-300">{{ $company->industry ?: '—' }}</span>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <flux:button :href="route('companies.index')" wire:navigate variant="ghost" icon="arrow-left">
                Về danh sách
            </flux:button>
            @can('update', $company)
                <flux:button :href="route('companies.edit', $company)" wire:navigate variant="primary" icon="pencil-square">
                    Chỉnh sửa
                </flux:button>
            @endcan
            @can('delete', $company)
                <flux:button wire:click="confirmDeleteCompany" variant="danger" icon="trash">
                    Xóa
                </flux:button>
            @endcan
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Hồ sơ, Tệp đính kèm, Timeline & Người liên hệ -->
        <div class="space-y-6 lg:col-span-2">
            <section class="crm-card">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold">Hồ sơ Doanh nghiệp</h2>
                    <p class="mt-1 text-sm text-slate-500">Thông tin pháp lý, ngành nghề, địa chỉ và liên hệ.</p>
                </div>

                <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="font-medium text-slate-500">Tên doanh nghiệp</dt>
                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $company->name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Mã số thuế</dt>
                        <dd class="mt-1 font-mono text-slate-900 dark:text-white">{{ $company->tax_code ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Ngành nghề</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">{{ $company->industry ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Quy mô</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">{{ $company->company_size ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Doanh thu hàng năm</dt>
                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">
                            {{ $company->annual_revenue ? number_format((float) $company->annual_revenue, 0, ',', '.') . ' VNĐ' : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Website</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">
                            @if ($company->website)
                                <a href="{{ $company->website }}" target="_blank" class="text-emerald-600 hover:underline dark:text-emerald-400 font-medium">{{ $company->website }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Số điện thoại</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">{{ $company->phone ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Email liên hệ</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">{{ $company->email ?: '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-slate-500">Địa chỉ trụ sở</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">{{ $company->address ?: '—' }}</dd>
                    </div>
                    @if ($company->notes)
                        <div class="sm:col-span-2">
                            <dt class="font-medium text-slate-500">Ghi chú nội bộ</dt>
                            <dd class="mt-1 rounded-lg bg-slate-50 p-3 text-slate-700 dark:bg-slate-800/80 dark:text-slate-300">
                                {{ $company->notes }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </section>

            <!-- Contacts Tab -->
            <section class="crm-card">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold">Người liên hệ ({{ $company->contacts->count() }})</h2>
                        <p class="mt-1 text-sm text-slate-500">Danh sách cá nhân đại diện thuộc doanh nghiệp.</p>
                    </div>
                </div>

                <div class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($company->contacts as $contact)
                        <div class="flex items-center justify-between py-3">
                            <div>
                                <div class="font-medium text-slate-900 dark:text-white flex items-center gap-2">
                                    {{ $contact->full_name }}
                                    @if ($contact->is_primary)
                                        <flux:badge color="emerald" size="sm">Đại diện chính</flux:badge>
                                    @endif
                                </div>
                                <div class="mt-0.5 text-xs text-slate-500">
                                    {{ $contact->job_title ?: 'Chức vụ không rõ' }} | {{ $contact->email ?: $contact->phone }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-slate-500">Chưa có người liên hệ nào được gắn với doanh nghiệp này.</p>
                    @endforelse
                </div>
            </section>

            <!-- Attachments Manager -->
            <livewire:customers.customer-attachment-manager :modelType="\App\Models\Company::class" :modelId="$company->id" />

            <!-- Timeline Feed -->
            <livewire:customers.customer-timeline-feed :modelType="\App\Models\Company::class" :modelId="$company->id" />
        </div>

        <!-- Sidebar Meta -->
        <div class="space-y-6">
            <section class="crm-card">
                <h2 class="text-base font-semibold border-b border-slate-200 pb-3 dark:border-slate-800">Phân công & Khởi tạo</h2>
                <dl class="mt-4 space-y-4 text-sm">
                    <div>
                        <dt class="font-medium text-slate-500">Người phụ trách</dt>
                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $company->owner?->name ?: 'Chưa phân công' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Phòng ban quản lý</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">{{ $company->department?->name ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Người khởi tạo</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">{{ $company->createdBy?->name ?: 'Hệ thống' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Ngày khởi tạo</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">{{ $company->created_at?->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>

    <!-- Modal Xác nhận xóa Doanh nghiệp -->
    <div
        x-data="{ open: @entangle('confirmingDeleteCompany') }"
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
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Xác nhận xóa Doanh nghiệp</h3>
                    <p class="text-xs text-slate-500">Hành động này sẽ chuyển doanh nghiệp vào thùng rác.</p>
                </div>
            </div>

            <p class="text-sm text-slate-600 dark:text-slate-300">
                Bạn có chắc chắn muốn xóa doanh nghiệp <strong class="text-slate-900 dark:text-white">{{ $company->name }}</strong> không?
            </p>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <flux:button wire:click="$set('confirmingDeleteCompany', false)" variant="ghost" size="sm">
                    Hủy bỏ
                </flux:button>
                <flux:button wire:click="deleteCompany" variant="danger" size="sm">
                    Xác nhận xóa
                </flux:button>
            </div>
        </div>
    </div>
</div>
