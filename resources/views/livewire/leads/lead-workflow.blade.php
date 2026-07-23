<section class="mt-6 space-y-6" aria-labelledby="lead-workflow-title">
    <div>
        <h2 id="lead-workflow-title" class="text-xl font-semibold">Quy trình và lịch sử Lead</h2>
        <p class="mt-1 text-sm text-slate-500">Mọi lần phân công và chuyển trạng thái đều được kiểm tra ở backend và lưu thành lịch sử không thể chỉnh sửa.</p>
    </div>

    @if ($this->canAssign || $this->canChangeStatus)
        <div class="flex flex-wrap gap-3">
            @if ($this->canAssign)
                <flux:button variant="primary" icon="user-plus" wire:click="openAssign">Gán người phụ trách</flux:button>
            @endif
            @if ($this->canChangeStatus)
                <flux:button variant="primary" icon="arrow-path" wire:click="openChangeStatus">Chuyển trạng thái</flux:button>
            @endif
        </div>

        <flux:modal name="assign-owner-modal" class="md:w-[32rem]" wire:close="cancelAssign">
            @if ($showAssignModal)
                <form wire:submit.prevent="assign" class="space-y-5">
                    <div>
                        <flux:heading size="lg">Gán người phụ trách</flux:heading>
                        <flux:text class="mt-2">Phòng ban sẽ tự động đồng bộ theo owner mới.</flux:text>
                    </div>

                    <div class="space-y-4">
                        <flux:select wire:model.live="ownerId" label="Người phụ trách mới">
                            <option value="">Chưa phân công</option>
                            @foreach ($this->ownerOptions as $ownerOption)
                                <option value="{{ $ownerOption->id }}">{{ $ownerOption->name }} — {{ $ownerOption->email }}</option>
                            @endforeach
                        </flux:select>
                        <flux:textarea wire:model="assignmentReason" label="Lý do cho lần phân công mới" rows="3" maxlength="500" placeholder="Ví dụ: Phân bổ theo khu vực phụ trách" />
                        @if (! $this->hasAssignmentChange)
                            <p class="text-sm text-slate-500">Hãy chọn người phụ trách khác để tạo một lần phân công mới. Lý do của lịch sử cũ không thể chỉnh sửa.</p>
                        @endif
                        @error('ownerId')
                            <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <flux:button variant="ghost" x-on:click="$flux.modal('assign-owner-modal').close()">Hủy</flux:button>
                        <flux:button type="submit" variant="primary" :disabled="! $this->hasAssignmentChange" wire:loading.attr="disabled" wire:target="assign">Lưu phân công</flux:button>
                    </div>
                </form>
            @endif
        </flux:modal>

        <flux:modal name="change-status-modal" class="md:w-[32rem]" wire:close="cancelChangeStatus">
            @if ($showStatusModal)
                <form wire:submit.prevent="changeStatus" class="space-y-5">
                    <div>
                        <flux:heading size="lg">Chuyển trạng thái</flux:heading>
                        <flux:text class="mt-2">Hiện tại: <strong>{{ $this->lead->status->label() }}</strong>. Chỉ các bước hợp lệ mới được hiển thị.</flux:text>
                    </div>

                    <div class="space-y-4">
                        <flux:select wire:model="targetStatus" label="Trạng thái tiếp theo" required>
                            <option value="">Chọn trạng thái</option>
                            @foreach ($this->statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                        <flux:textarea wire:model="statusReason" label="Lý do thay đổi" rows="3" maxlength="500" placeholder="Bắt buộc khi chuyển sang Không đủ điều kiện hoặc Đã mất" />
                        @error('targetStatus')
                            <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        @error('statusReason')
                            <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <flux:button variant="ghost" x-on:click="$flux.modal('change-status-modal').close()">Hủy</flux:button>
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="changeStatus">Chuyển trạng thái</flux:button>
                    </div>
                </form>
            @endif
        </flux:modal>
    @endif

    <div class="crm-card">
        <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
            <div>
                <h3 class="font-semibold">Timeline nghiệp vụ</h3>
                <p class="mt-1 text-sm text-slate-500">Sắp xếp mới nhất trước, thời gian hiển thị theo giờ Việt Nam.</p>
            </div>
            <flux:badge color="zinc">{{ $this->timeline->count() }} sự kiện</flux:badge>
        </div>

        @if ($this->timeline->isEmpty())
            <div class="mt-5 rounded-xl border border-dashed border-slate-300 px-5 py-10 text-center dark:border-slate-700">
                <p class="font-medium">Chưa có lịch sử workflow</p>
                <p class="mt-1 text-sm text-slate-500">Lần phân công hoặc chuyển trạng thái tiếp theo sẽ xuất hiện tại đây.</p>
            </div>
        @else
            <ol class="relative mt-6 space-y-0 before:absolute before:bottom-3 before:left-[0.6875rem] before:top-3 before:w-px before:bg-slate-200 dark:before:bg-slate-800">
                @foreach ($this->timeline as $entry)
                    <li class="relative flex gap-4 pb-6 last:pb-0">
                        <span @class([
                            'relative z-10 mt-1 size-6 shrink-0 rounded-full border-4 border-white dark:border-slate-900',
                            'bg-blue-500' => $entry->type === 'status',
                            'bg-emerald-500' => $entry->type === 'assignment',
                        ])></span>
                        <div class="min-w-0 flex-1 rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                            <div class="flex flex-col justify-between gap-1 sm:flex-row sm:items-start">
                                <div>
                                    <p class="font-medium">{{ $entry->title }}</p>
                                    <p class="mt-1 text-sm">{{ $entry->description }}</p>
                                </div>
                                <time class="shrink-0 text-xs text-slate-500">
                                    {{ $entry->occurredAt->timezone(config('crm.display_timezone'))->format('d/m/Y H:i:s') }}
                                </time>
                            </div>
                            @if ($entry->reason)
                                <p class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600 dark:bg-slate-950/60 dark:text-slate-300">{{ $entry->reason }}</p>
                            @endif
                            <p class="mt-3 text-xs text-slate-500">Thực hiện bởi {{ $entry->actorName }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</section>
