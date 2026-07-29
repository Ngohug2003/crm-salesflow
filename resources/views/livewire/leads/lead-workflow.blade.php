<section class="mt-6 space-y-6" aria-labelledby="lead-workflow-title">
    <div>
        <h2 id="lead-workflow-title" class="text-xl font-semibold">Quy trình và lịch sử Lead</h2>
        <p class="mt-1 text-sm text-slate-500">Mọi lần phân công và chuyển trạng thái đều được kiểm tra ở backend và lưu thành lịch sử không thể chỉnh sửa.</p>
    </div>

    @if ($this->canAssign || $this->canChangeStatus || $this->canConvert)
        <div class="flex flex-wrap gap-3">
            @if ($this->canConvert)
                <flux:button variant="primary" color="emerald" icon="sparkles" wire:click="openConvert">Chuyển đổi Lead</flux:button>
            @endif
            @if ($this->canAssign)
                <flux:button variant="filled" icon="user-plus" wire:click="openAssign">Gán người phụ trách</flux:button>
            @endif
            @if ($this->canChangeStatus)
                <flux:button variant="filled" icon="arrow-path" wire:click="openChangeStatus">Chuyển trạng thái</flux:button>
            @endif
        </div>

        <flux:modal name="convert-lead-modal" class="md:w-[42rem]" wire:close="cancelConvert">
            @if ($showConvertModal)
                <form wire:submit.prevent="convertLead" class="space-y-6">
                    <div>
                        <flux:heading size="lg">Xem trước và ghép nối chuyển đổi Lead</flux:heading>
                        <flux:text class="mt-1">Lead <strong>{{ $this->lead->full_name }}</strong> sẽ được chuyển sang trạng thái <strong>Đã chuyển đổi (Converted)</strong>. Kiểm tra các gợi ý ghép nối bên dưới để tránh trùng lặp dữ liệu.</flux:text>
                    </div>

                    <div class="space-y-5">
                        {{-- 1. Ghép nối Công ty --}}
                        <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800 space-y-3 bg-slate-50/50 dark:bg-slate-900/50">
                            <div class="flex items-center justify-between">
                                <h4 class="font-semibold text-slate-900 dark:text-white">1. Doanh nghiệp (Company)</h4>
                                @if (! empty($conversionPreview['matchedCompanies']) && $conversionPreview['matchedCompanies']->count() > 0)
                                    <flux:badge color="amber" size="sm">Phát hiện {{ $conversionPreview['matchedCompanies']->count() }} công ty có thể trùng</flux:badge>
                                @endif
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 p-3 bg-white dark:border-slate-800 dark:bg-slate-900">
                                    <input type="radio" wire:model.live="convertCompanyMode" value="existing" class="text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-sm font-medium">Ghép vào Công ty có sẵn</span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 p-3 bg-white dark:border-slate-800 dark:bg-slate-900">
                                    <input type="radio" wire:model.live="convertCompanyMode" value="create" class="text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-sm font-medium">Tạo Doanh nghiệp mới</span>
                                </label>
                            </div>

                            @if ($convertCompanyMode === 'existing')
                                <x-forms.smart-select wire:model="convertCompanyId" label="Chọn Công ty có sẵn">
                                    <option value="">-- Chọn Công ty --</option>
                                    @if (! empty($conversionPreview['matchedCompanies']))
                                        <optgroup label="Gợi ý trùng khớp">
                                            @foreach ($conversionPreview['matchedCompanies'] as $matchedComp)
                                                <option value="{{ $matchedComp->id }}">{{ $matchedComp->name }} (Website: {{ $matchedComp->website ?: 'Chưa có' }})</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    @if (! empty($conversionPreview['allCompanies']))
                                        <optgroup label="Tất cả Công ty khác">
                                            @foreach ($conversionPreview['allCompanies'] as $compItem)
                                                <option value="{{ $compItem->id }}">{{ $compItem->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </x-forms.smart-select>
                            @else
                                <flux:input wire:model="convertCompanyName" label="Tên Doanh nghiệp mới *" required />
                            @endif
                        </div>

                        {{-- 2. Ghép nối Người liên hệ --}}
                        <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800 space-y-3 bg-slate-50/50 dark:bg-slate-900/50">
                            <div class="flex items-center justify-between">
                                <h4 class="font-semibold text-slate-900 dark:text-white">2. Người liên hệ (Contact)</h4>
                                @if (! empty($conversionPreview['matchedContacts']) && $conversionPreview['matchedContacts']->count() > 0)
                                    <flux:badge color="amber" size="sm">Phát hiện {{ $conversionPreview['matchedContacts']->count() }} Contact trùng Email/SĐT</flux:badge>
                                @endif
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 p-3 bg-white dark:border-slate-800 dark:bg-slate-900">
                                    <input type="radio" wire:model.live="convertContactMode" value="existing" class="text-emerald-600 focus:ring-emerald-500">
                                    <span class="text-sm font-medium">Ghép vào Contact có sẵn</span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 p-3 bg-white dark:border-slate-800 dark:bg-slate-900">
                                    <input type="radio" wire:model.live="convertContactMode" value="create" class="text-emerald-600 focus:ring-emerald-500">
                                    <span class="text-sm font-medium">Tạo Contact mới ({{ $this->lead->full_name }})</span>
                                </label>
                            </div>

                            @if ($convertContactMode === 'existing')
                                <x-forms.smart-select wire:model="convertContactId" label="Chọn Người liên hệ có sẵn">
                                    <option value="">-- Chọn Contact --</option>
                                    @if (! empty($conversionPreview['matchedContacts']))
                                        <optgroup label="Gợi ý trùng email/SĐT">
                                            @foreach ($conversionPreview['matchedContacts'] as $matchedContact)
                                                <option value="{{ $matchedContact->id }}">{{ $matchedContact->full_name }} ({{ $matchedContact->email ?: $matchedContact->phone }})</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    @if (! empty($conversionPreview['allContacts']))
                                        <optgroup label="Tất cả Contact khác">
                                            @foreach ($conversionPreview['allContacts'] as $contactItem)
                                                <option value="{{ $contactItem->id }}">{{ $contactItem->full_name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </x-forms.smart-select>
                            @endif
                        </div>

                        {{-- 3. Cơ hội bán hàng --}}
                        <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800 space-y-3 bg-slate-50/50 dark:bg-slate-900/50">
                            <div class="flex items-center justify-between">
                                <h4 class="font-semibold text-slate-900 dark:text-white">3. Cơ hội bán hàng (Opportunity)</h4>
                                <flux:checkbox wire:model.live="convertCreateOpportunity" label="Tạo Cơ hội mới" />
                            </div>

                            @if ($convertCreateOpportunity)
                                <div class="grid gap-3 sm:grid-cols-2 pt-2">
                                    <div class="sm:col-span-2">
                                        <flux:input wire:model="convertOpportunityName" label="Tên Cơ hội bán hàng *" required />
                                    </div>
                                    <flux:input wire:model="convertEstimatedValue" type="number" label="Giá trị dự kiến (VNĐ)" />
                                    @if (! empty($conversionPreview['pipelines']))
                                        <x-forms.smart-select wire:model.live="convertPipelineId" label="Quy trình bán hàng (Pipeline)">
                                            @foreach ($conversionPreview['pipelines'] as $pipe)
                                                <option value="{{ $pipe->id }}">{{ $pipe->name }}</option>
                                            @endforeach
                                        </x-forms.smart-select>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Tóm tắt xem trước --}}
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs dark:border-slate-800 dark:bg-slate-900/60">
                            <h5 class="font-semibold text-slate-900 dark:text-white uppercase tracking-wider">Xem trước kết quả sau khi chuyển đổi</h5>
                            <ul class="mt-2 space-y-1.5 text-slate-700 dark:text-slate-300">
                                <li>
                                    <strong>Doanh nghiệp:</strong>
                                    {{ $convertCompanyMode === 'create' ? 'Khởi tạo mới "'.$convertCompanyName.'"' : 'Ghép nối vào Công ty có sẵn (#'.$convertCompanyId.')' }}
                                </li>
                                <li>
                                    <strong>Người liên hệ:</strong>
                                    {{ $convertContactMode === 'create' ? 'Khởi tạo mới "'.$this->lead->full_name.'"' : 'Ghép nối vào Contact có sẵn (#'.$convertContactId.')' }}
                                </li>
                                <li>
                                    <strong>Cơ hội bán hàng:</strong>
                                    {{ $convertCreateOpportunity ? 'Khởi tạo mới "'.$convertOpportunityName.'" (Giá trị: '.($convertEstimatedValue ? number_format($convertEstimatedValue, 0, ',', '.').' ₫' : 'Chưa nhập').')' : 'Không tạo' }}
                                </li>
                            </ul>
                        </div>

                        @error('convert')
                            <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <flux:button variant="ghost" x-on:click="$flux.modal('convert-lead-modal').close()">Hủy</flux:button>
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="convertLead">Xác nhận Chuyển đổi</flux:button>
                    </div>
                </form>
            @endif
        </flux:modal>

        <flux:modal name="assign-owner-modal" class="md:w-[32rem]" wire:close="cancelAssign">
            @if ($showAssignModal)
                <form wire:submit.prevent="assign" class="space-y-5">
                    <div>
                        <flux:heading size="lg">Gán người phụ trách</flux:heading>
                        <flux:text class="mt-2">Phòng ban sẽ tự động đồng bộ theo owner mới.</flux:text>
                    </div>

                    <div class="space-y-4">
                        <flux:field>
                            <flux:label>Người phụ trách mới</flux:label>
                            <x-forms.modal-searchable-select
                                wire:model.live="ownerId"
                                :options="collect($this->ownerOptions)->map(fn($u) => ['id' => $u->id, 'name' => $u->name . ' — ' . $u->email])->all()"
                                option-value="id"
                                option-label="name"
                                placeholder="Chưa phân công"
                                search-placeholder="Tìm người phụ trách…"
                            />
                        </flux:field>
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
                        <x-forms.smart-select wire:model="targetStatus" label="Trạng thái tiếp theo" required>
                            <option value="">Chọn trạng thái</option>
                            @foreach ($this->statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-forms.smart-select>
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

    {{-- Form Tạo Ghi chú mới --}}
    <div class="crm-card">
        <form wire:submit.prevent="addNote" class="space-y-4">
            <div>
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Thêm ghi chú chăm sóc Lead</h3>
                <p class="mt-0.5 text-xs text-slate-500">Lưu lại thông tin trao đổi, phản hồi của khách hàng hoặc các lưu ý quan trọng.</p>
            </div>
            <div>
                <flux:textarea wire:model="noteContent" placeholder="Nhập ghi chú mới tại đây..." rows="3" required />
                @error('noteContent')
                    <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <flux:checkbox wire:model="noteIsPinned" label="Ghim ghi chú này lên đầu Dòng thời gian" />
                <flux:button type="submit" variant="primary" icon="paper-airplane" size="sm" wire:loading.attr="disabled" wire:target="addNote">
                    Lưu ghi chú
                </flux:button>
            </div>
        </form>
    </div>

    <div class="crm-card">
        <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
            <div>
                <h3 class="font-semibold text-slate-900 dark:text-white">Dòng thời gian tích hợp (Complete Timeline)</h3>
                <p class="mt-1 text-sm text-slate-500">Tổng hợp Ghi chú, Hoạt động chăm sóc, Chuyển trạng thái và Lịch sử phân công (UTC+7).</p>
            </div>
            <flux:badge color="zinc">{{ $this->timeline->count() }} nhật ký</flux:badge>
        </div>

        @if ($this->timeline->isEmpty())
            <div class="mt-5 rounded-xl border border-dashed border-slate-300 px-5 py-10 text-center dark:border-slate-700">
                <p class="font-medium text-slate-900 dark:text-white">Chưa có nhật ký nào</p>
                <p class="mt-1 text-sm text-slate-500">Tạo ghi chú hoặc thực hiện chuyển đổi trạng thái để ghi nhận dòng thời gian tại đây.</p>
            </div>
        @else
            <ol class="relative mt-6 space-y-0 before:absolute before:bottom-3 before:left-[0.6875rem] before:top-3 before:w-px before:bg-slate-200 dark:before:bg-slate-800">
                @foreach ($this->timeline as $entry)
                    <li wire:key="timeline-{{ $entry->type }}-{{ $entry->noteId ?? $loop->index }}" class="relative flex gap-4 pb-6 last:pb-0">
                        <span @class([
                            'relative z-10 mt-1 size-6 shrink-0 rounded-full border-4 border-white dark:border-slate-900',
                            'bg-amber-500 ring-2 ring-amber-300' => $entry->type === 'note' && $entry->isPinned,
                            'bg-purple-500' => $entry->type === 'note' && ! $entry->isPinned,
                            'bg-emerald-500' => $entry->type === 'activity',
                            'bg-blue-500' => $entry->type === 'status',
                            'bg-orange-500' => $entry->type === 'assignment',
                        ])></span>

                        <div @class([
                            'min-w-0 flex-1 rounded-xl border p-4 transition',
                            'border-amber-300 bg-amber-50/50 dark:border-amber-900/50 dark:bg-amber-950/20' => $entry->type === 'note' && $entry->isPinned,
                            'border-purple-200 bg-purple-50/30 dark:border-purple-900/30 dark:bg-purple-950/10' => $entry->type === 'note' && ! $entry->isPinned,
                            'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900' => in_array($entry->type, ['activity', 'status', 'assignment'], true),
                        ])>
                            <div class="flex flex-col justify-between gap-1 sm:flex-row sm:items-start">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ $entry->title }}</p>
                                    @if ($entry->type === 'note' && $entry->isPinned)
                                        <flux:badge color="amber" size="sm">Đã ghim</flux:badge>
                                    @endif
                                    @if ($entry->type === 'activity')
                                        <flux:badge color="emerald" size="sm">Hoạt động</flux:badge>
                                    @endif
                                    @if ($entry->type === 'status')
                                        <flux:badge color="blue" size="sm">Trạng thái</flux:badge>
                                    @endif
                                    @if ($entry->type === 'assignment')
                                        <flux:badge color="orange" size="sm">Phân công</flux:badge>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2">
                                    <time class="shrink-0 text-xs text-slate-500">
                                        {{ $entry->occurredAt->timezone(config('crm.display_timezone', 'Asia/Ho_Chi_Minh'))->format('d/m/Y H:i:s') }}
                                    </time>
                                    @if ($entry->type === 'note' && $entry->noteId)
                                        <div class="flex items-center gap-1">
                                            <flux:button wire:click="togglePinNote({{ $entry->noteId }})" size="xs" variant="ghost" icon="bookmark" title="{{ $entry->isPinned ? 'Bỏ ghim' : 'Ghim ở đầu' }}" />
                                            <flux:button wire:click="deleteNote({{ $entry->noteId }})" size="xs" variant="ghost" icon="trash" class="text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40" title="Xóa ghi chú" />
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <p class="mt-2 text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line leading-relaxed">{{ $entry->description }}</p>

                            @if ($entry->reason)
                                <p class="mt-3 rounded-lg bg-slate-100 px-3 py-2 text-xs text-slate-600 dark:bg-slate-950/60 dark:text-slate-400">Lý do: {{ $entry->reason }}</p>
                            @endif

                            <p class="mt-3 text-xs text-slate-400">Tác giả: <span class="font-medium text-slate-600 dark:text-slate-300">{{ $entry->actorName }}</span></p>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</section>
