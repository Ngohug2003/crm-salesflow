<div>
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">CRM / Doanh nghiệp / {{ $companyId === null ? 'Tạo mới' : 'Chỉnh sửa' }}</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">
                {{ $companyId === null ? 'Tạo mới Doanh nghiệp' : 'Chỉnh sửa Doanh nghiệp' }}
            </h1>
            <p class="mt-2 max-w-3xl text-slate-500">
                {{ $companyId === null ? 'Nhập thông tin hồ sơ doanh nghiệp khách hàng hoặc đối tác vào hệ thống.' : 'Cập nhật thông tin chi tiết hồ sơ doanh nghiệp, liên hệ và phân công.' }}
            </p>
        </div>
        <flux:button :href="$companyId === null ? route('companies.index') : route('companies.show', $companyId)" wire:navigate variant="ghost" icon="arrow-left">
            {{ $companyId === null ? 'Về danh sách' : 'Về chi tiết' }}
        </flux:button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <!-- Thông tin cơ bản -->
        <section class="crm-card" aria-labelledby="company-basic-title">
            <div class="mb-5">
                <h2 id="company-basic-title" class="text-lg font-semibold">Thông tin cơ bản</h2>
                <p class="mt-1 text-sm text-slate-500">Tên doanh nghiệp là bắt buộc; MST, ngành nghề và quy mô có thể cập nhật sau.</p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <flux:input
                        wire:model="name"
                        label="Tên doanh nghiệp *"
                        placeholder="Ví dụ: Công ty Cổ phần Công nghệ Ánh Dương"
                        required
                    />
                    @error('name') <span class="mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <flux:input
                    wire:model="taxCode"
                    label="Mã số thuế (MST)"
                    placeholder="Ví dụ: 0312345678"
                />

                <flux:select wire:model="industry" label="Ngành nghề">
                    <option value="">-- Chọn ngành nghề --</option>
                    @foreach ($this->industries as $ind)
                        <option value="{{ $ind }}">{{ $ind }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="companySize" label="Quy mô doanh nghiệp">
                    <option value="">-- Chọn quy mô --</option>
                    @foreach ($this->sizes as $s)
                        <option value="{{ $s }}">{{ $s }}</option>
                    @endforeach
                </flux:select>

                <flux:input
                    wire:model="annualRevenue"
                    type="number"
                    step="0.01"
                    label="Doanh thu hàng năm (VNĐ)"
                    placeholder="Ví dụ: 5000000000"
                />
            </div>
        </section>

        <!-- Liên hệ & Địa chỉ -->
        <section class="crm-card" aria-labelledby="company-contact-title">
            <div class="mb-5">
                <h2 id="company-contact-title" class="text-lg font-semibold">Liên hệ & Địa chỉ</h2>
                <p class="mt-1 text-sm text-slate-500">Thông tin liên lạc trụ sở và trang thông tin doanh nghiệp.</p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <flux:input
                    wire:model="phone"
                    label="Số điện thoại"
                    placeholder="Ví dụ: 02839998888"
                />

                <flux:input
                    wire:model="email"
                    type="email"
                    label="Email liên hệ"
                    placeholder="name@company.test"
                />

                <div class="md:col-span-2">
                    <flux:input
                        wire:model="website"
                        type="url"
                        label="Website"
                        placeholder="https://example.com"
                    />
                </div>

                <div class="md:col-span-2">
                    <flux:input
                        wire:model="address"
                        label="Địa chỉ trụ sở"
                        placeholder="Ví dụ: 123 Đường Nguyễn Huệ, Phường Bến Nghé, Quận 1"
                    />
                </div>
            </div>
        </section>

        <!-- Phân công & Ghi chú -->
        <section class="crm-card" aria-labelledby="company-assignment-title">
            <div class="mb-5">
                <h2 id="company-assignment-title" class="text-lg font-semibold">Phân công & Ghi chú</h2>
                <p class="mt-1 text-sm text-slate-500">Gán người phụ trách quản lý hồ sơ và ghi chú nội bộ.</p>
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
                        placeholder="Nhập ghi chú thêm về doanh nghiệp..."
                    />
                </div>
            </div>
        </section>

        <!-- Nút thao tác -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <flux:button :href="$companyId === null ? route('companies.index') : route('companies.show', $companyId)" wire:navigate variant="ghost">
                Hủy
            </flux:button>
            <flux:button type="submit" variant="primary">
                {{ $companyId === null ? 'Tạo mới Doanh nghiệp' : 'Lưu thay đổi' }}
            </flux:button>
        </div>
    </form>
</div>
