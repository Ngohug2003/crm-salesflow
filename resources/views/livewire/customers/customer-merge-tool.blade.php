<div>
    <!-- Header -->
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">CRM / Khách hàng / Hợp nhất dữ liệu</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">Hợp nhất bản ghi trùng lặp</h1>
            <p class="mt-2 max-w-3xl text-slate-500">
                Phát hiện và hợp nhất các doanh nghiệp hoặc người liên hệ trùng tên, email, SĐT hoặc mã số thuế. Dữ liệu liên quan sẽ được tự động chuyển giao an toàn.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <flux:button :href="route('companies.index')" wire:navigate variant="ghost" icon="arrow-left">
                Về Doanh nghiệp
            </flux:button>
            <flux:button :href="route('contacts.index')" wire:navigate variant="ghost" icon="users">
                Về Người liên hệ
            </flux:button>
        </div>
    </div>

    @if ($feedbackMessage)
        <div class="mb-6 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300" role="status">
            <span>{{ $feedbackMessage }}</span>
            <button type="button" class="text-xs font-semibold hover:underline" wire:click="$set('feedbackMessage', null)">Ẩn</button>
        </div>
    @endif

    <!-- Mode Selector Tabs -->
    <div class="mb-6 border-b border-slate-200 dark:border-slate-800">
        <nav class="-mb-px flex space-x-6 text-sm font-medium">
            <button
                type="button"
                wire:click="setMode('company')"
                @class([
                    'pb-3 border-b-2 transition',
                    'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400 font-semibold' => $mode === 'company',
                    'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' => $mode !== 'company',
                ])
            >
                Hợp nhất Doanh nghiệp (Company)
            </button>
            <button
                type="button"
                wire:click="setMode('contact')"
                @class([
                    'pb-3 border-b-2 transition',
                    'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400 font-semibold' => $mode === 'contact',
                    'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' => $mode !== 'contact',
                ])
            >
                Hợp nhất Người liên hệ (Contact)
            </button>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Candidate List -->
        <div class="space-y-6 lg:col-span-2">
            <section class="crm-card">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Cặp bản ghi nghi ngờ trùng lặp</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Tự động phát hiện dựa trên quy tắc khớp trùng dữ liệu CRM.</p>
                    </div>
                    <flux:badge color="zinc">{{ count($duplicateGroups) }} nhóm trùng</flux:badge>
                </div>

                @if (empty($duplicateGroups))
                    <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center dark:border-slate-700">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Không phát hiện bản ghi trùng lặp nào</p>
                        <p class="mt-1 text-xs text-slate-500">Dữ liệu CRM hiện tại của bạn rất sạch sẽ và không có nghi vấn nhân bản.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($duplicateGroups as $group)
                            <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800 space-y-3 bg-slate-50/50 dark:bg-slate-900/50">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-semibold text-amber-700 dark:text-amber-400">{{ $group['reason'] }}</p>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    <!-- Master item -->
                                    <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
                                        <p class="text-[11px] font-semibold uppercase text-slate-400">Bản ghi gốc (Master Candidate)</p>
                                        <p class="mt-1 font-semibold text-slate-900 dark:text-white">
                                            {{ $mode === 'company' ? $group['master']->name : $group['master']->full_name }}
                                        </p>
                                        <p class="text-xs text-slate-500 mt-1">
                                            ID: #{{ $group['master']->id }} | {{ $mode === 'company' ? ($group['master']->tax_code ?: $group['master']->website ?: 'Chưa có MST') : ($group['master']->email ?: $group['master']->phone) }}
                                        </p>
                                    </div>

                                    <!-- Duplicate source items -->
                                    @foreach ($group['duplicates'] as $dup)
                                        <div class="rounded-lg border border-amber-200 bg-amber-50/40 p-3 dark:border-amber-900/40 dark:bg-amber-950/20">
                                            <p class="text-[11px] font-semibold uppercase text-amber-700 dark:text-amber-300">Bản ghi trùng phụ (Source)</p>
                                            <p class="mt-1 font-semibold text-slate-900 dark:text-white">
                                                {{ $mode === 'company' ? $dup->name : $dup->full_name }}
                                            </p>
                                            <p class="text-xs text-slate-500 mt-1">
                                                ID: #{{ $dup->id }} | {{ $mode === 'company' ? ($dup->tax_code ?: $dup->website ?: 'Chưa có MST') : ($dup->email ?: $dup->phone) }}
                                            </p>
                                            <div class="mt-2 flex justify-end">
                                                <flux:button
                                                    wire:click="selectPair({{ $group['master']->id }}, {{ $dup->id }})"
                                                    size="xs"
                                                    variant="filled"
                                                >
                                                    Chọn hợp nhất cặp này
                                                </flux:button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <!-- Merge Configuration Panel -->
        <div class="space-y-6">
            <section class="crm-card">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white border-b border-slate-200 pb-3 dark:border-slate-800">
                    Cấu hình hợp nhất
                </h2>

                <form wire:submit.prevent="{{ $mode === 'company' ? 'executeCompanyMerge' : 'executeContactMerge' }}" class="mt-4 space-y-4">
                    <flux:input wire:model="masterId" type="number" label="ID Bản ghi chính (Master Keep)" placeholder="ID bản ghi sẽ giữ lại" required />
                    <flux:input wire:model="sourceId" type="number" label="ID Bản ghi phụ (Source Merge)" placeholder="ID bản ghi sẽ gộp và xóa" required />

                    @error('merge')
                        <p class="text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 space-y-1 leading-relaxed">
                        <p class="font-semibold text-slate-900 dark:text-white">Lưu ý nghiệp vụ hợp nhất:</p>
                        <p>• Toàn bộ Cơ hội bán hàng, Contact, Công việc, Hoạt động và Tệp đính kèm của bản ghi phụ sẽ tự động chuyển sang bản ghi chính.</p>
                        <p>• Bản ghi phụ sẽ được chuyển vào Thùng rác (Soft Delete) và lưu vết Audit log.</p>
                    </div>

                    <div class="pt-2">
                        <flux:button type="submit" variant="primary" color="emerald" class="w-full" wire:loading.attr="disabled">
                            Xác nhận Hợp nhất bản ghi
                        </flux:button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>
