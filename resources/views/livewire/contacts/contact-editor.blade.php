<div>
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">CRM / Người liên hệ / {{ $contactId === null ? 'Tạo mới' : 'Chỉnh sửa' }}</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">
                {{ $contactId === null ? 'Tạo mới Người liên hệ' : 'Chỉnh sửa Người liên hệ' }}
            </h1>
            <p class="mt-2 max-w-3xl text-slate-500">
                {{ $contactId === null ? 'Nhập thông tin cá nhân đại diện và liên kết với doanh nghiệp trực thuộc.' : 'Cập nhật thông tin cá nhân, doanh nghiệp và phân công.' }}
            </p>
        </div>
        <flux:button :href="$contactId === null ? route('contacts.index') : route('contacts.show', $contactId)" wire:navigate variant="ghost" icon="arrow-left">
            {{ $contactId === null ? 'Về danh sách' : 'Về chi tiết' }}
        </flux:button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <!-- Thông tin cá nhân -->
        <section class="crm-card" aria-labelledby="contact-personal-title">
            <div class="mb-5">
                <h2 id="contact-personal-title" class="text-lg font-semibold">Thông tin cá nhân</h2>
                <p class="mt-1 text-sm text-slate-500">Họ và Tên là bắt buộc; thông tin chức danh và ngày sinh có thể bổ sung sau.</p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <flux:input
                    wire:model="lastName"
                    label="Họ và tên đệm *"
                    placeholder="Ví dụ: Nguyễn Văn"
                    required
                />
                @error('lastName') <span class="mt-1 text-xs text-red-500">{{ $message }}</span> @enderror

                <flux:input
                    wire:model="firstName"
                    label="Tên *"
                    placeholder="Ví dụ: An"
                    required
                />
                @error('firstName') <span class="mt-1 text-xs text-red-500">{{ $message }}</span> @enderror

                <flux:select wire:model="companyId" label="Doanh nghiệp trực thuộc">
                    <option value="">-- Chọn doanh nghiệp (Hoặc cá nhân tự do) --</option>
                    @foreach ($this->companies as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </flux:select>
                @error('companyId') <span class="mt-1 text-xs text-red-500">{{ $message }}</span> @enderror

                <div class="flex items-center pt-6">
                    <label class="relative flex items-center gap-2 cursor-pointer text-sm font-medium text-slate-700 dark:text-slate-300">
                        <input type="checkbox" wire:model="isPrimary" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800" />
                        <span>Đại diện chính của Doanh nghiệp</span>
                    </label>
                </div>

                <flux:input
                    wire:model="jobTitle"
                    label="Chức danh"
                    placeholder="Ví dụ: Giám đốc Kinh doanh"
                />

                <flux:input
                    wire:model="departmentName"
                    label="Phòng ban làm việc"
                    placeholder="Ví dụ: Phòng Kinh doanh"
                />

                <flux:input
                    wire:model="birthday"
                    type="date"
                    label="Ngày sinh"
                />
            </div>
        </section>

        <!-- Thông tin liên hệ & Địa chỉ -->
        <section class="crm-card" aria-labelledby="contact-communication-title">
            <div class="mb-5">
                <h2 id="contact-communication-title" class="text-lg font-semibold">Thông tin liên hệ & Địa chỉ</h2>
                <p class="mt-1 text-sm text-slate-500">Email, số điện thoại chính/phụ và địa chỉ cư trú.</p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <flux:input
                    wire:model="phone"
                    label="Số điện thoại"
                    placeholder="Ví dụ: 0901234567"
                />

                <flux:input
                    wire:model="secondaryPhone"
                    label="Số điện thoại phụ"
                    placeholder="Ví dụ: 0912345678"
                />

                <div class="md:col-span-2">
                    <flux:input
                        wire:model="email"
                        type="email"
                        label="Email"
                        placeholder="name@example.com"
                    />
                </div>

                <div class="md:col-span-2">
                    <flux:input
                        wire:model="address"
                        label="Địa chỉ"
                        placeholder="Ví dụ: 45 Đường Lê Lợi, Quận 1"
                    />
                </div>
            </div>
        </section>

        <!-- Phân công & Ghi chú -->
        <section class="crm-card" aria-labelledby="contact-assignment-title">
            <div class="mb-5">
                <h2 id="contact-assignment-title" class="text-lg font-semibold">Phân công & Ghi chú</h2>
                <p class="mt-1 text-sm text-slate-500">Gán người phụ trách và ghi chú nội bộ.</p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <flux:select wire:model="ownerId" label="Người phụ trách">
                    <option value="">-- Tự động phân công hoặc Chọn người phụ trách --</option>
                    @foreach ($this->users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                    @endforeach
                </flux:select>

                <div class="md:col-span-2">
                    <flux:textarea
                        wire:model="notes"
                        label="Ghi chú nội bộ"
                        rows="3"
                        placeholder="Nhập ghi chú thêm về người liên hệ..."
                    />
                </div>
            </div>
        </section>

        <!-- Nút thao tác -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <flux:button :href="$contactId === null ? route('contacts.index') : route('contacts.show', $contactId)" wire:navigate variant="ghost">
                Hủy
            </flux:button>
            <flux:button type="submit" variant="primary">
                {{ $contactId === null ? 'Tạo mới Người liên hệ' : 'Lưu thay đổi' }}
            </flux:button>
        </div>
    </form>
</div>
