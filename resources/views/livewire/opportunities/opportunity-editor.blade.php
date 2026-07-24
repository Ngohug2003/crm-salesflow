<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                {{ $this->opportunityId ? 'Chỉnh sửa Cơ hội bán hàng' : 'Tạo mới Cơ hội bán hàng' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">Cấu hình thông tin thương mại, quy trình bán hàng và đối tác liên quan.</p>
        </div>

        <flux:button href="{{ route('opportunities.index') }}" variant="ghost" icon="arrow-left" size="sm">
            Quay lại danh sách
        </flux:button>
    </div>

    <form wire:submit="save" class="crm-card space-y-6">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <flux:input
                    wire:model="title"
                    label="Tên Cơ hội bán hàng *"
                    placeholder="VD: Dự án phần mềm CRM Doanh nghiệp Vin"
                />
                @error('title') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <flux:input
                    wire:model="code"
                    label="Mã Cơ hội (tự động tạo nếu để trống)"
                    placeholder="VD: OPP-2026-00001"
                />
                @error('code') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <flux:input
                    type="number"
                    wire:model="amount"
                    label="Giá trị Hợp đồng (VNĐ) *"
                    placeholder="0"
                    min="0"
                    step="1000000"
                />
                @error('amount') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <flux:select wire:model.live="pipeline_id" label="Quy trình Bán hàng *">
                    <option value="">-- Chọn Quy trình --</option>
                    @foreach ($this->pipelines as $pipe)
                        <option value="{{ $pipe->id }}">{{ $pipe->name }}</option>
                    @endforeach
                </flux:select>
                @error('pipeline_id') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <flux:select wire:model="stage_id" label="Giai đoạn Bán hàng (Stage) *">
                    <option value="">-- Chọn Giai đoạn --</option>
                    @foreach ($this->stages as $stg)
                        <option value="{{ $stg->id }}">{{ $stg->name }} ({{ $stg->probability }}%)</option>
                    @endforeach
                </flux:select>
                @error('stage_id') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <flux:select wire:model.live="company_id" label="Doanh nghiệp liên quan">
                    <option value="">-- Không chọn --</option>
                    @foreach ($this->companies as $comp)
                        <option value="{{ $comp->id }}">{{ $comp->name }}</option>
                    @endforeach
                </flux:select>
                @error('company_id') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <flux:select wire:model="contact_id" label="Người liên hệ chính">
                    <option value="">-- Không chọn --</option>
                    @foreach ($this->contacts as $cont)
                        <option value="{{ $cont->id }}">{{ $cont->full_name }} ({{ $cont->email ?: $cont->phone }})</option>
                    @endforeach
                </flux:select>
                @error('contact_id') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <flux:input
                    type="date"
                    wire:model="expected_close_date"
                    label="Ngày dự kiến chốt hợp đồng"
                />
                @error('expected_close_date') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <flux:select wire:model="owner_id" label="Người phụ trách">
                    <option value="">-- Tự động gán --</option>
                    @foreach ($this->users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                    @endforeach
                </flux:select>
                @error('owner_id') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="sm:col-span-2">
                <flux:textarea
                    wire:model="notes"
                    label="Ghi chú chi tiết"
                    rows="3"
                    placeholder="Mô tả nhu cầu khách hàng, yêu cầu kỹ thuật, mốc thanh toán..."
                />
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
            <flux:button href="{{ route('opportunities.index') }}" variant="ghost">
                Hủy bỏ
            </flux:button>

            <flux:button type="submit" variant="primary" icon="check">
                {{ $this->opportunityId ? 'Cập nhật Cơ hội' : 'Lưu Cơ hội mới' }}
            </flux:button>
        </div>
    </form>
</div>
