<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
        <div>
            <p class="text-sm text-slate-500">Cài đặt / Tự động hóa bán hàng</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">Sales Playbook</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-500 dark:text-slate-400">
                Chuẩn hóa hướng dẫn, checklist và công việc tự động khi cơ hội đi vào từng giai đoạn.
            </p>
        </div>
        <flux:button type="button" variant="primary" wire:click="newPlaybook">Tạo playbook</flux:button>
    </div>

    @if (session('success'))
        <div role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[19rem_minmax(0,1fr)]">
        <aside class="crm-card h-fit p-0! xl:sticky xl:top-6">
            <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                <h2 class="text-sm font-semibold text-slate-950 dark:text-white">Các phiên bản</h2>
            </div>
            <div class="max-h-[65vh] divide-y divide-slate-200 overflow-y-auto dark:divide-slate-800">
                @forelse ($playbooks as $playbook)
                    <button
                        type="button"
                        wire:key="playbook-{{ $playbook->id }}"
                        wire:click="selectPlaybook({{ $playbook->id }})"
                        @class([
                            'block w-full px-4 py-3 text-left transition hover:bg-slate-50 dark:hover:bg-slate-800/60',
                            'bg-blue-50 dark:bg-blue-950/30' => $selectedId === $playbook->id,
                        ])
                    >
                        <span class="flex items-center justify-between gap-2">
                            <span class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ $playbook->name }}</span>
                            <flux:badge size="sm" :color="$playbook->status->value === 'published' ? 'emerald' : ($playbook->status->value === 'draft' ? 'amber' : 'zinc')">
                                v{{ $playbook->version }}
                            </flux:badge>
                        </span>
                        <span class="mt-1 block text-xs text-slate-500">{{ $playbook->status->label() }} · {{ $playbook->steps->count() }} bước</span>
                    </button>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-slate-500">Chưa có Sales Playbook.</p>
                @endforelse
            </div>
        </aside>

        <section class="crm-card space-y-6" aria-labelledby="playbook-editor-title">
            <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                <div>
                    <h2 id="playbook-editor-title" class="text-base font-semibold text-slate-950 dark:text-white">
                        {{ $selected ? $selected->name.' · v'.$selected->version : 'Playbook mới' }}
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ $isDraft ? 'Bản nháp có thể chỉnh sửa trước khi phát hành.' : 'Phiên bản đã phát hành là bất biến để bảo toàn các run đang hoạt động.' }}
                    </p>
                </div>
                @if ($selected && ! $isDraft)
                    <div class="flex flex-wrap gap-2">
                        <flux:button type="button" wire:click="createNextVersion" variant="subtle" size="sm">Chỉnh sửa (tạo phiên bản mới)</flux:button>
                        @if ($selected->status->value === 'published')
                            <flux:button type="button" wire:click="$set('showArchiveConfirmation', true)" variant="danger" size="sm">Lưu trữ</flux:button>
                        @endif
                    </div>
                @endif
            </div>

            <fieldset class="space-y-5" @disabled(! $isDraft)>
                <div class="grid gap-4 lg:grid-cols-2">
                    <flux:input wire:model="name" label="Tên playbook *" placeholder="Ví dụ: Qualification cơ hội B2B" />
                    <x-forms.smart-select wire:model="repeatPolicy" label="Chính sách chạy lại">
                        @foreach ($repeatPolicies as $policy)
                            <option value="{{ $policy->value }}">{{ $policy->label() }}</option>
                        @endforeach
                    </x-forms.smart-select>
                </div>
                <flux:textarea wire:model="description" label="Mục tiêu" rows="3" placeholder="Kết quả Sale cần đạt được khi hoàn tất playbook" />
                <x-forms.smart-select wire:model="stageId" label="Gán vào giai đoạn khi phát hành">
                    <option value="">Chưa gán giai đoạn</option>
                    @foreach ($pipelines as $pipeline)
                        <optgroup label="{{ $pipeline->name }}">
                            @foreach ($pipeline->stages as $stage)
                                <option value="{{ $stage->id }}">{{ $stage->position }}. {{ $stage->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </x-forms.smart-select>

                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-950 dark:text-white">Các bước thực hiện</h3>
                            <p class="mt-1 text-xs text-slate-500">Sắp xếp theo đúng trình tự Sales cần làm.</p>
                        </div>
                        @if ($isDraft)
                            <flux:button type="button" wire:click="addStep" variant="subtle" size="sm">Thêm bước</flux:button>
                        @endif
                    </div>

                    <div class="space-y-3" x-data="{ openStep: 0 }">
                        @foreach ($steps as $index => $step)
                            <section wire:key="playbook-step-{{ $index }}" class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-800">
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between gap-3 bg-slate-50 px-4 py-3 text-left dark:bg-slate-900/70"
                                    x-on:click="openStep = openStep === {{ $index }} ? null : {{ $index }}"
                                    x-bind:aria-expanded="openStep === {{ $index }}"
                                >
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium text-slate-900 dark:text-white">
                                            {{ $index + 1 }}. {{ $step['title'] !== '' ? $step['title'] : 'Bước chưa đặt tên' }}
                                        </span>
                                        <span class="mt-0.5 block text-xs text-slate-500">
                                            {{ collect($stepTypes)->firstWhere('value', $step['type'])?->label() }}
                                        </span>
                                    </span>
                                    <flux:icon.chevron-down class="size-4 shrink-0 transition-transform" x-bind:class="{ 'rotate-180': openStep === {{ $index }} }" />
                                </button>

                                <div x-show="openStep === {{ $index }}" x-collapse.duration.200ms>
                                    <div class="space-y-4 border-t border-slate-200 p-4 dark:border-slate-800">
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <x-forms.smart-select wire:model.live="steps.{{ $index }}.type" label="Loại bước">
                                                @foreach ($stepTypes as $type)
                                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                                @endforeach
                                            </x-forms.smart-select>
                                            <flux:input wire:model.live.debounce.300ms="steps.{{ $index }}.title" label="Tiêu đề *" />
                                        </div>

                                        <flux:textarea wire:model="steps.{{ $index }}.instructions" label="Hướng dẫn thực hiện" rows="2" />

                                        @if ($step['type'] === 'required_field')
                                            <x-forms.smart-select wire:model="steps.{{ $index }}.field_name" label="Trường Opportunity cần có">
                                                <option value="">Chọn trường</option>
                                                @foreach ($fieldOptions as $field => $label)
                                                    <option value="{{ $field }}">{{ $label }}</option>
                                                @endforeach
                                            </x-forms.smart-select>
                                        @endif

                                        @if ($step['type'] === 'document')
                                            <flux:input type="url" wire:model="steps.{{ $index }}.document_url" label="Đường dẫn tài liệu" placeholder="https://..." />
                                        @endif

                                        @if (in_array($step['type'], ['task', 'reminder'], true))
                                            <flux:input type="number" min="0" max="365" wire:model="steps.{{ $index }}.due_business_days" label="Hạn sau số ngày làm việc" />
                                        @endif

                                        <div class="flex flex-wrap gap-5">
                                            <flux:checkbox wire:model="steps.{{ $index }}.is_required" label="Bước bắt buộc" />
                                            <flux:checkbox wire:model="steps.{{ $index }}.blocks_stage_exit" label="Chặn rời stage khi chưa hoàn tất" />
                                        </div>

                                        @if ($isDraft)
                                            <div class="flex flex-wrap justify-between gap-2 border-t border-slate-200 pt-3 dark:border-slate-800">
                                                <div class="flex gap-2">
                                                    <flux:button type="button" wire:click="moveStep({{ $index }}, -1)" variant="ghost" size="sm" :disabled="$index === 0">Lên</flux:button>
                                                    <flux:button type="button" wire:click="moveStep({{ $index }}, 1)" variant="ghost" size="sm" :disabled="$index === count($steps) - 1">Xuống</flux:button>
                                                </div>
                                                <flux:button type="button" wire:click="removeStep({{ $index }})" variant="danger" size="sm">Xóa bước</flux:button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </section>
                        @endforeach
                    </div>
                </div>
            </fieldset>

            @if ($isDraft)
                <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-800">
                    <flux:button type="button" wire:click="save" wire:loading.attr="disabled" variant="subtle">Lưu bản nháp</flux:button>
                    <flux:button type="button" wire:click="publish" wire:loading.attr="disabled" variant="primary">Phát hành</flux:button>
                </div>
            @endif
        </section>
    </div>

    <flux:modal name="archive-sales-playbook" wire:model="showArchiveConfirmation" class="md:w-[32rem]">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Lưu trữ playbook?</flux:heading>
                <flux:text class="mt-2">Assignment hiện tại sẽ ngừng hoạt động nhưng lịch sử Opportunity vẫn được giữ nguyên.</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="$set('showArchiveConfirmation', false)" variant="ghost">Hủy</flux:button>
                <flux:button type="button" wire:click="archive" variant="danger">Lưu trữ</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
