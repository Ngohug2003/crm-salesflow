<section
    class="crm-card space-y-4"
    aria-labelledby="opportunity-playbook-title"
    @if ($awaitingAutomaticActivation) wire:poll.1500ms @endif
>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h3 id="opportunity-playbook-title" class="text-base font-semibold text-slate-950 dark:text-white">Sales Playbook</h3>
            @if ($run)
                <p class="mt-1 text-xs text-slate-500">
                    {{ $run->playbook_snapshot['name'] }} · Phiên bản {{ $run->playbook_snapshot['version'] }}
                </p>
            @else
                <p class="mt-1 text-xs text-slate-500">Giai đoạn hiện tại chưa được gán playbook.</p>
            @endif
        </div>
        @if ($run)
            <div class="flex items-center gap-2">
                <flux:badge :color="$progress === 100 ? 'emerald' : 'blue'" size="sm">{{ $completed }}/{{ $total }} bước</flux:badge>
                @can('update', $opportunity)
                    <flux:button type="button" wire:click="$set('showRestartConfirmation', true)" size="sm" variant="ghost">
                        Chạy lại
                    </flux:button>
                @endcan
            </div>
        @endif
    </div>

    @if (session('playbook_success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
            {{ session('playbook_success') }}
        </div>
    @endif

    @error('response')
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200" role="alert">
            {{ $message }}
        </div>
    @enderror

    @if ($run)
        <div>
            <div class="mb-1 flex justify-between text-xs text-slate-500">
                <span>Tiến độ</span>
                <span>{{ $progress }}%</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                <div class="h-full rounded-full bg-blue-600 transition-all duration-300" style="width: {{ $progress }}%"></div>
            </div>
        </div>

        @if ($nextStep)
            <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 dark:border-blue-900 dark:bg-blue-950/30">
                <p class="text-xs font-medium uppercase tracking-wide text-blue-700 dark:text-blue-300">Việc cần làm tiếp theo</p>
                <p class="mt-1 text-sm font-semibold text-slate-950 dark:text-white">{{ $nextStep->title }}</p>
                @if ($nextStep->due_at)
                    <p class="mt-1 text-xs text-slate-500">Hạn: {{ $nextStep->due_at->timezone(config('crm.display_timezone'))->format('d/m/Y H:i') }}</p>
                @endif
            </div>
        @endif

        <div class="divide-y divide-slate-200 rounded-lg border border-slate-200 dark:divide-slate-800 dark:border-slate-800">
            @foreach ($run->steps as $step)
                <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span @class([
                                'inline-flex size-5 items-center justify-center rounded-full text-xs font-semibold',
                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' => $step->status === 'completed',
                                'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300' => $step->status !== 'completed',
                            ])>{{ $step->status === 'completed' ? '✓' : $step->position }}</span>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $step->title }}</p>
                            @if ($step->blocks_stage_exit)
                                <flux:badge color="amber" size="sm">Điều kiện thoát</flux:badge>
                            @endif
                        </div>
                        @if ($step->instructions)
                            <p class="mt-1 pl-7 text-xs text-slate-500">{{ $step->instructions }}</p>
                        @endif
                        @if ($step->response_text)
                            <p class="mt-2 ml-7 rounded-md bg-slate-50 px-3 py-2 text-xs text-slate-700 dark:bg-slate-900 dark:text-slate-300">
                                {{ $step->response_text }}
                            </p>
                        @endif
                        @if ($step->task)
                            <a href="{{ route('tasks.show', $step->task->id) }}" wire:navigate class="mt-1 inline-block pl-7 text-xs font-medium text-blue-600 hover:underline dark:text-blue-400">
                                Mở công việc liên quan
                            </a>
                        @endif
                    </div>

                    @if ($step->status !== 'completed' && ! in_array($step->type->value, ['required_field', 'guidance', 'document'], true))
                        <div class="flex min-w-0 flex-col gap-2 sm:w-64 sm:items-end">
                            @if ($step->type->value === 'question')
                                <flux:input wire:model="responses.{{ $step->id }}" placeholder="Nhập câu trả lời..." />
                            @endif
                            @can('update', $run->opportunity)
                                <flux:button type="button" wire:click="completeStep({{ $step->id }})" wire:loading.attr="disabled" size="sm" variant="subtle">
                                    Hoàn tất
                                </flux:button>
                            @endcan
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-700">
            @if ($awaitingAutomaticActivation)
                <div class="mx-auto mb-3 size-5 animate-spin rounded-full border-2 border-slate-300 border-t-emerald-600" aria-hidden="true"></div>
                <p class="font-medium text-slate-700 dark:text-slate-200">Đang khởi tạo Playbook cho giai đoạn mới...</p>
                <p class="mt-1 text-xs">Danh sách bước sẽ tự xuất hiện sau khi automation hoàn tất.</p>
            @else
                <p>Khi Opportunity vào stage có assignment, checklist và công việc sẽ xuất hiện tại đây.</p>
            @endif
            @if ($canStart && ! $awaitingAutomaticActivation)
                @can('update', $opportunity)
                    <flux:button type="button" wire:click="start" wire:loading.attr="disabled" variant="primary" size="sm" class="mt-3">
                        Khởi chạy playbook
                    </flux:button>
                @endcan
            @endif
        </div>
    @endif

    <flux:modal name="restart-opportunity-playbook" wire:model="showRestartConfirmation" class="md:w-[32rem]">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Chạy lại Playbook?</flux:heading>
                <flux:text class="mt-2">
                    Hệ thống sẽ tạo một lượt thực hiện mới theo phiên bản đang gán cho giai đoạn này. Lịch sử và công việc của lượt trước vẫn được giữ lại.
                </flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="$set('showRestartConfirmation', false)" variant="ghost">Hủy</flux:button>
                <flux:button type="button" wire:click="restart" wire:loading.attr="disabled" variant="primary">
                    Xác nhận chạy lại
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
