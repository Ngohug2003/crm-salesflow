<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400" wire:navigate>Trang chủ</a>
                <span>/</span>
                <a href="{{ route('opportunities.index') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400" wire:navigate>Cơ hội bán hàng</a>
                <span>/</span>
                <span class="text-slate-900 dark:text-white">{{ $this->opportunityId ? 'Chỉnh sửa' : 'Tạo mới' }}</span>
            </div>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">
                {{ $this->opportunityId ? 'Chỉnh sửa Cơ hội bán hàng' : 'Tạo mới Cơ hội bán hàng' }}
            </h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-400">
                Cập nhật thông tin thương mại, pipeline, khách hàng liên quan và người phụ trách.
            </p>
        </div>

        <flux:button :href="route('opportunities.index')" wire:navigate variant="ghost">
            Danh sách cơ hội
        </flux:button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <section class="crm-card space-y-5" aria-labelledby="opportunity-basic-title">
            <div>
                <h2 id="opportunity-basic-title" class="text-base font-semibold text-slate-950 dark:text-white">Thông tin thương vụ</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Tên cơ hội, mã, giá trị hợp đồng và ngày dự kiến chốt.</p>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="lg:col-span-2">
                    <flux:input wire:model="title" label="Tên cơ hội bán hàng *" placeholder="VD: Dự án CRM cho khách hàng doanh nghiệp" />
                    @error('title') <span class="mt-1 block text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <flux:input wire:model="code" label="Mã cơ hội" placeholder="Tự động tạo nếu để trống" />
                    @error('code') <span class="mt-1 block text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-forms.money-input model="amount" label="Giá trị hợp đồng" required />
                </div>

                <div>
                    <flux:input type="date" wire:model="expected_close_date" label="Ngày dự kiến chốt" />
                    @error('expected_close_date') <span class="mt-1 block text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-forms.smart-select wire:model="owner_id" label="Người phụ trách">
                        <option value="">Tự động gán</option>
                        @foreach ($this->users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </x-forms.smart-select>
                    @error('owner_id') <span class="mt-1 block text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
            </div>
        </section>

        <section class="crm-card space-y-5" aria-labelledby="opportunity-pipeline-title">
            <div>
                <h2 id="opportunity-pipeline-title" class="text-base font-semibold text-slate-950 dark:text-white">Quy trình và khách hàng</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Chọn pipeline, giai đoạn hiện tại và khách hàng liên quan.</p>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <x-forms.smart-select wire:model.live="pipeline_id" label="Quy trình bán hàng *">
                        <option value="">Chọn quy trình</option>
                        @foreach ($this->pipelines as $pipe)
                            <option value="{{ $pipe->id }}">{{ $pipe->name }}</option>
                        @endforeach
                    </x-forms.smart-select>
                    @error('pipeline_id') <span class="mt-1 block text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-forms.smart-select wire:model="stage_id" label="Giai đoạn bán hàng *">
                        <option value="">Chọn giai đoạn</option>
                        @foreach ($this->stages as $stg)
                            <option value="{{ $stg->id }}">{{ $stg->name }} ({{ $stg->probability }}%)</option>
                        @endforeach
                    </x-forms.smart-select>
                    @error('stage_id') <span class="mt-1 block text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-forms.smart-select wire:model="forecast_category" label="Danh mục dự báo (Forecast Category) *">
                        @foreach (\App\Enums\ForecastCategory::cases() as $fc)
                            <option value="{{ $fc->value }}">{{ $fc->label() }}</option>
                        @endforeach
                    </x-forms.smart-select>
                    @error('forecast_category') <span class="mt-1 block text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-forms.smart-select wire:model.live="company_id" label="Doanh nghiệp liên quan">
                        <option value="">Không chọn</option>
                        @foreach ($this->companies as $comp)
                            <option value="{{ $comp->id }}">{{ $comp->name }}</option>
                        @endforeach
                    </x-forms.smart-select>
                    @error('company_id') <span class="mt-1 block text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-forms.smart-select wire:model="contact_id" label="Người liên hệ chính">
                        <option value="">Không chọn</option>
                        @foreach ($this->contacts as $cont)
                            <option value="{{ $cont->id }}">{{ $cont->full_name }} ({{ $cont->email ?: $cont->phone }})</option>
                        @endforeach
                    </x-forms.smart-select>
                    @error('contact_id') <span class="mt-1 block text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
            </div>
        </section>

        <section class="crm-card space-y-4" aria-labelledby="opportunity-note-title">
            <div>
                <h2 id="opportunity-note-title" class="text-base font-semibold text-slate-950 dark:text-white">Ghi chú</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Nhu cầu khách hàng, điều kiện thương mại, mốc thanh toán hoặc rủi ro cần theo dõi.</p>
            </div>

            <flux:textarea wire:model="notes" label="Ghi chú chi tiết" rows="4" placeholder="Nhập ghi chú nội bộ cho cơ hội này..." />
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
            <flux:button :href="route('opportunities.index')" wire:navigate variant="ghost">
                Hủy
            </flux:button>

            <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled">
                {{ $this->opportunityId ? 'Cập nhật cơ hội' : 'Lưu cơ hội mới' }}
            </flux:button>
        </div>
    </form>
</div>
