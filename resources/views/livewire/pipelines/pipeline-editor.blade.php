<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                {{ $this->pipelineId ? 'Chỉnh sửa Quy trình bán hàng' : 'Tạo mới Quy trình bán hàng' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">Cấu hình tên, trạng thái và thiết lập các giai đoạn (Stages) trên sơ đồ Kanban.</p>
        </div>

        <flux:button href="{{ route('pipelines.index') }}" variant="ghost" icon="arrow-left" size="sm">
            Quay lại danh sách
        </flux:button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <!-- Section 1: Thông tin Quy trình -->
        <div class="crm-card">
            <h2 class="text-lg font-semibold mb-4 text-slate-900 dark:text-white">Thông tin Quy trình</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <flux:input
                        wire:model="name"
                        label="Tên Quy trình *"
                        placeholder="VD: Quy trình Bán hàng Doanh nghiệp"
                    />
                    @error('name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <flux:input
                        wire:model="code"
                        label="Mã Quy trình (Slug)"
                        placeholder="Tự động tạo từ tên nếu để trống"
                    />
                    @error('code') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="sm:col-span-2">
                    <flux:textarea
                        wire:model="description"
                        label="Mô tả chi tiết"
                        rows="2"
                        placeholder="Ghi chú mục đích sử dụng quy trình này..."
                    />
                </div>

                <div class="flex items-center gap-6 sm:col-span-2 pt-2">
                    <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-slate-700 dark:text-slate-300">
                        <input type="checkbox" wire:model="is_default" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900" />
                        <span>Đặt làm Quy trình Mặc định (Default)</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-slate-700 dark:text-slate-300">
                        <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900" />
                        <span>Kích hoạt hoạt động</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Section 2: Cấu hình các Giai đoạn (Stages) -->
        <div class="crm-card">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Cấu hình các Giai đoạn (Stages)</h2>
                    <p class="text-xs text-slate-500">Giữ biểu tượng <span class="inline-block px-1 font-semibold text-slate-700 dark:text-slate-300">☰</span> và kéo thả trực tiếp để sắp xếp vị trí các giai đoạn. Tỷ lệ % đại diện cho khả năng chốt đơn thành công.</p>
                </div>

                <flux:button wire:click="addStage" variant="subtle" icon="plus" size="sm" type="button">
                    Thêm Giai đoạn
                </flux:button>
            </div>

            @error('stages')
                <div class="mb-4 text-xs font-semibold text-red-500 bg-red-50 p-2.5 rounded dark:bg-red-950/40">
                    {{ $message }}
                </div>
            @enderror

            <div
                x-data="{
                    dragIndex: null,
                    dropIndex: null,
                    handleDragStart(e, index) {
                        this.dragIndex = index;
                        e.dataTransfer.effectAllowed = 'move';
                    },
                    handleDragOver(e, index) {
                        e.preventDefault();
                        this.dropIndex = index;
                    },
                    handleDrop(index) {
                        if (this.dragIndex !== null && this.dragIndex !== index) {
                            $wire.reorderStages(this.dragIndex, index);
                        }
                        this.dragIndex = null;
                        this.dropIndex = null;
                    }
                }"
                class="space-y-3"
            >
                @foreach ($stages as $index => $stage)
                    <div
                        wire:key="stage-row-{{ $index }}-{{ $stage['id'] ?? 'new' }}"
                        draggable="true"
                        @dragstart="handleDragStart($event, {{ $index }})"
                        @dragover="handleDragOver($event, {{ $index }})"
                        @drop="handleDrop({{ $index }})"
                        :class="{
                            'ring-2 ring-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/30': dragIndex === {{ $index }},
                            'ring-2 ring-emerald-500 bg-emerald-50/30 dark:bg-emerald-950/20': dropIndex === {{ $index }} && dragIndex !== {{ $index }}
                        }"
                        class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-slate-50/50 p-3 sm:flex-row sm:items-center dark:border-slate-800 dark:bg-slate-900/50 transition-all"
                    >
                        <div class="flex items-center gap-2 shrink-0">
                            <!-- Drag Handle -->
                            <div title="Giữ và kéo thả để đổi vị trí" class="cursor-grab active:cursor-grabbing text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 pr-1">
                                <flux:icon.bars-3 class="size-5" />
                            </div>

                            <span class="flex size-7 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                {{ $index + 1 }}
                            </span>

                            <!-- <div class="flex flex-col gap-0.5">
                                <button type="button" wire:click="moveStageUp({{ $index }})" @disabled($index === 0) class="text-slate-400 hover:text-slate-600 disabled:opacity-30 dark:hover:text-slate-200">
                                    <flux:icon.chevron-up class="size-4" />
                                </button>
                                <button type="button" wire:click="moveStageDown({{ $index }})" @disabled($index === count($stages) - 1) class="text-slate-400 hover:text-slate-600 disabled:opacity-30 dark:hover:text-slate-200">
                                    <flux:icon.chevron-down class="size-4" />
                                </button>
                            </div> -->
                        </div>

                        <div class="grid flex-1 grid-cols-1 gap-2 sm:grid-cols-4">
                            <div class="sm:col-span-2">
                                <flux:input
                                    wire:model="stages.{{ $index }}.name"
                                    placeholder="Tên giai đoạn *"
                                />
                            </div>

                            <div>
                                <flux:input
                                    type="number"
                                    wire:model="stages.{{ $index }}.probability"
                                    placeholder="Xác suất (%) *"
                                    min="0"
                                    max="100"
                                />
                            </div>

                            <div class="flex items-center gap-2">
                                <input
                                    type="color"
                                    wire:model="stages.{{ $index }}.color"
                                    class="size-9 rounded cursor-pointer border border-slate-300 bg-transparent p-0.5"
                                />

                                @if (! empty($stage['is_won']))
                                    <flux:badge color="emerald" size="sm">Won</flux:badge>
                                @elseif (! empty($stage['is_lost']))
                                    <flux:badge color="red" size="sm">Lost</flux:badge>
                                @endif

                                @if (! empty($stage['is_system']))
                                    <flux:badge color="amber" size="sm">Hệ thống</flux:badge>
                                @endif
                            </div>
                        </div>

                        <div class="shrink-0">
                            @if (empty($stage['is_system']))
                                <flux:button wire:click="removeStage({{ $index }})" variant="danger" icon="trash" size="sm" type="button" />
                            @else
                                <span class="text-xs text-slate-400 italic">Không thể xóa</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <flux:button href="{{ route('pipelines.index') }}" variant="ghost">
                Hủy bỏ
            </flux:button>

            <flux:button type="submit" variant="primary" icon="check">
                {{ $this->pipelineId ? 'Cập nhật Quy trình' : 'Lưu Quy trình' }}
            </flux:button>
        </div>
    </form>
</div>
