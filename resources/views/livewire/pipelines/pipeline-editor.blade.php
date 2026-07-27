<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400" wire:navigate>Trang chủ</a>
                <span>/</span>
                <a href="{{ route('pipelines.index') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400" wire:navigate>Quy trình bán hàng</a>
                <span>/</span>
                <span class="text-slate-900 dark:text-white">{{ $this->pipelineId ? 'Chỉnh sửa' : 'Tạo mới' }}</span>
            </div>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">
                {{ $this->pipelineId ? 'Chỉnh sửa Quy trình bán hàng' : 'Tạo mới Quy trình bán hàng' }}
            </h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-400">
                Cấu hình pipeline, trạng thái sử dụng và thứ tự các giai đoạn bán hàng.
            </p>
        </div>

        <flux:button :href="route('pipelines.index')" wire:navigate variant="ghost">
            Danh sách quy trình
        </flux:button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <section class="crm-card space-y-5" aria-labelledby="pipeline-basic-title">
            <div>
                <h2 id="pipeline-basic-title" class="text-base font-semibold text-slate-950 dark:text-white">Thông tin quy trình</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Tên, mã định danh và trạng thái áp dụng.</p>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <flux:input wire:model="name" label="Tên quy trình *" placeholder="VD: Quy trình bán hàng doanh nghiệp" />
                    @error('name') <span class="mt-1 block text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <flux:input wire:model="code" label="Mã quy trình" placeholder="Tự động tạo từ tên nếu để trống" />
                    @error('code') <span class="mt-1 block text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <div class="lg:col-span-2">
                    <flux:textarea wire:model="description" label="Mô tả" rows="2" placeholder="Mục đích sử dụng hoặc nhóm bán hàng áp dụng..." />
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4 transition hover:border-emerald-300 dark:border-slate-800 dark:hover:border-emerald-800">
                    <input type="checkbox" wire:model="is_default" class="mt-1 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-900" />
                    <span>
                        <span class="block text-sm font-medium text-slate-950 dark:text-white">Đặt làm quy trình mặc định</span>
                        <span class="mt-1 block text-sm text-slate-500 dark:text-slate-400">Cơ hội mới sẽ ưu tiên dùng quy trình này nếu không chọn khác.</span>
                    </span>
                </label>

                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4 transition hover:border-emerald-300 dark:border-slate-800 dark:hover:border-emerald-800">
                    <input type="checkbox" wire:model="is_active" class="mt-1 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-900" />
                    <span>
                        <span class="block text-sm font-medium text-slate-950 dark:text-white">Cho phép sử dụng</span>
                        <span class="mt-1 block text-sm text-slate-500 dark:text-slate-400">Khi tạm ngừng, quy trình không xuất hiện trong form tạo cơ hội.</span>
                    </span>
                </label>
            </div>
        </section>

        <section class="crm-card space-y-5" aria-labelledby="pipeline-stage-title">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 id="pipeline-stage-title" class="text-base font-semibold text-slate-950 dark:text-white">Các giai đoạn bán hàng</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Kéo thả để đổi thứ tự. Xác suất dùng để tính doanh thu dự báo.</p>
                </div>

                <flux:button wire:click="addStage" variant="subtle" size="sm" type="button">
                    Thêm giai đoạn
                </flux:button>
            </div>

            @error('stages')
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200">
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
                    <article
                        wire:key="stage-row-{{ $index }}-{{ $stage['id'] ?? 'new' }}"
                        draggable="true"
                        @dragstart="handleDragStart($event, {{ $index }})"
                        @dragover="handleDragOver($event, {{ $index }})"
                        @drop="handleDrop({{ $index }})"
                        :class="{
                            'ring-2 ring-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/20': dragIndex === {{ $index }},
                            'ring-2 ring-emerald-400 bg-emerald-50/40 dark:bg-emerald-950/10': dropIndex === {{ $index }} && dragIndex !== {{ $index }}
                        }"
                        class="rounded-lg border border-slate-200 bg-slate-50 p-3 transition dark:border-slate-800 dark:bg-slate-900/50"
                    >
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                            <div class="flex shrink-0 items-center gap-3">
                                <button type="button" title="Giữ và kéo để đổi vị trí" class="cursor-grab text-slate-400 transition hover:text-slate-700 active:cursor-grabbing dark:hover:text-slate-200">
                                    <flux:icon.bars-3 class="size-5" />
                                </button>

                                <span class="grid size-8 place-items-center rounded-full bg-white text-xs font-semibold text-slate-700 shadow-xs dark:bg-slate-950 dark:text-slate-300">
                                    {{ $index + 1 }}
                                </span>
                            </div>

                            <div class="grid flex-1 gap-3 sm:grid-cols-12 items-center">
                                <div class="sm:col-span-8">
                                    <flux:input wire:model="stages.{{ $index }}.name" placeholder="Tên giai đoạn *" />
                                </div>

                                <div class="sm:col-span-4">
                                    <flux:input
                                        type="number"
                                        wire:model="stages.{{ $index }}.probability"
                                        placeholder="Xác suất %"
                                        min="0"
                                        max="100"
                                    />
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-2.5 border-t border-slate-200 pt-2 lg:border-t-0 lg:pt-0">
                                <input
                                    type="color"
                                    wire:model="stages.{{ $index }}.color"
                                    class="size-9 shrink-0 cursor-pointer rounded-lg border border-slate-300 bg-transparent p-1 dark:border-slate-700"
                                    aria-label="Màu giai đoạn"
                                />

                                @if (empty($stage['is_system']))
                                    <flux:button wire:click="removeStage({{ $index }})" variant="danger" size="sm" type="button">
                                        Xóa
                                    </flux:button>
                                @else
                                    <flux:button variant="danger" size="sm" type="button" disabled title="Giai đoạn hệ thống không thể xóa" class="opacity-50 cursor-not-allowed">
                                        Xóa
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
            <flux:button :href="route('pipelines.index')" wire:navigate variant="ghost">
                Hủy
            </flux:button>

            <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled">
                {{ $this->pipelineId ? 'Cập nhật quy trình' : 'Lưu quy trình' }}
            </flux:button>
        </div>
    </form>
</div>
