<div>
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">CRM / Lead / {{ $leadId === null ? 'Tạo mới' : 'Chỉnh sửa' }}</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">{{ $leadId === null ? 'Tạo Lead' : 'Chỉnh sửa Lead' }}</h1>
            <p class="mt-2 max-w-3xl text-slate-500">
                {{ $leadId === null ? 'Ghi nhận khách hàng tiềm năng và phân công đúng phạm vi.' : 'Cập nhật thông tin liên hệ, nguồn, tag và người phụ trách.' }}
            </p>
        </div>
        <flux:button :href="$leadId === null ? route('leads.index') : route('leads.show', $leadId)" wire:navigate variant="ghost" icon="arrow-left">
            {{ $leadId === null ? 'Về danh sách' : 'Về chi tiết' }}
        </flux:button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <section class="crm-card" aria-labelledby="lead-contact-title">
            <div class="mb-5">
                <h2 id="lead-contact-title" class="text-lg font-semibold">Thông tin liên hệ</h2>
                <p class="mt-1 text-sm text-slate-500">Họ tên là bắt buộc; email và số điện thoại có thể bổ sung sau.</p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <flux:input wire:model.blur="form.fullName" label="Họ và tên" placeholder="Ví dụ: Nguyễn Văn An" required />
                <flux:input wire:model.blur="form.email" type="email" label="Email" placeholder="name@example.com" />
                <flux:input wire:model.blur="form.phone" label="Số điện thoại" placeholder="0901234567" />
                <flux:input wire:model.blur="form.secondaryPhone" label="Số điện thoại phụ" />
                <flux:input wire:model.blur="form.companyName" label="Công ty" />
                <flux:input wire:model.blur="form.jobTitle" label="Chức danh" />
                <flux:input wire:model.blur="form.website" type="url" label="Website" placeholder="https://example.com" />
            </div>
        </section>

        <section class="crm-card" aria-labelledby="lead-classification-title">
            <div class="mb-5">
                <h2 id="lead-classification-title" class="text-lg font-semibold">Phân loại và phụ trách</h2>
                <p class="mt-1 text-sm text-slate-500">Phòng ban được tự động đồng bộ theo người phụ trách để bảo đảm data scope.</p>
            </div>

            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <flux:select wire:model="form.sourceId" label="Nguồn Lead">
                    <option value="">Chưa xác định nguồn</option>
                    @foreach ($this->sourceOptions as $sourceOption)
                        <option value="{{ $sourceOption->id }}">{{ $sourceOption->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="form.priority" label="Mức ưu tiên" required>
                    @foreach ($this->priorityOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                @if ($this->canAssign)
                    <flux:select wire:model="form.ownerId" label="Người phụ trách">
                        <option value="">Chưa phân công</option>
                        @foreach ($this->ownerOptions as $ownerOption)
                            <option value="{{ $ownerOption->id }}">{{ $ownerOption->name }} — {{ $ownerOption->email }}</option>
                        @endforeach
                    </flux:select>
                @else
                    <div class="rounded-xl border border-slate-200 px-4 py-3 dark:border-slate-800">
                        <p class="text-sm font-medium">Người phụ trách</p>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $leadId === null ? 'Lead được giữ cho chính bạn vì tài khoản không có quyền phân công.' : 'Thay đổi người phụ trách tại trang chi tiết để hệ thống lưu đầy đủ lịch sử.' }}
                        </p>
                    </div>
                @endif
            </div>

            <fieldset class="mt-6">
                <legend class="font-medium">Tag</legend>
                <p class="mt-1 text-sm text-slate-500">Chọn tối đa 20 tag để hỗ trợ tìm kiếm và phân nhóm.</p>
                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($this->tagOptions as $tagOption)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-3 transition hover:border-emerald-300 dark:border-slate-800 dark:hover:border-emerald-800">
                            <flux:checkbox wire:model="form.tagIds" value="{{ $tagOption->id }}" />
                            <span>{{ $tagOption->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('form.tagIds')
                    <p class="mt-2 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                @error('form.tagIds.*')
                    <p class="mt-2 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </fieldset>
        </section>

        <section class="crm-card" aria-labelledby="lead-address-title">
            <div class="mb-5">
                <h2 id="lead-address-title" class="text-lg font-semibold">Địa chỉ và giá trị</h2>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <flux:input wire:model.blur="form.address" label="Địa chỉ" />
                <flux:input wire:model.blur="form.city" label="Thành phố" />
                <flux:input wire:model.blur="form.province" label="Tỉnh / Thành" />
                <flux:input wire:model.blur="form.country" label="Quốc gia" />
                <flux:input wire:model.blur="form.estimatedValue" type="number" min="0" step="0.01" label="Giá trị dự kiến (VNĐ)" />
            </div>

            <div class="mt-5">
                <flux:textarea wire:model.blur="form.notes" label="Ghi chú" rows="5" placeholder="Thông tin nhu cầu, bối cảnh hoặc lưu ý khi liên hệ…" />
            </div>
        </section>

        <div class="sticky bottom-4 z-10 flex flex-col-reverse gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur dark:border-slate-800 dark:bg-slate-900/95 sm:flex-row sm:justify-end">
            <flux:button :href="$leadId === null ? route('leads.index') : route('leads.show', $leadId)" wire:navigate variant="ghost">Hủy</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $leadId === null ? 'Tạo Lead' : 'Lưu thay đổi' }}</span>
                <span wire:loading wire:target="save">Đang lưu…</span>
            </flux:button>
        </div>
    </form>
</div>
