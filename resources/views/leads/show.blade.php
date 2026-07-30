@extends('layouts.app')

@section('content')
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">CRM / Lead / #{{ $lead->id }}</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">{{ $lead->full_name }}</h1>
            <div class="mt-3 flex flex-wrap gap-2">
                <flux:badge :color="$lead->score_badge_color">Điểm Lead: {{ $lead->score }}đ ({{ $lead->score_level_label }})</flux:badge>
                <flux:badge :color="$lead->status->color()">{{ $lead->status->label() }}</flux:badge>
                <flux:badge :color="$lead->priority->color()">Ưu tiên {{ mb_strtolower($lead->priority->label()) }}</flux:badge>
                @if ($lead->source)
                    <flux:badge>{{ $lead->source->name }}</flux:badge>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('leads.index')" wire:navigate variant="ghost" icon="arrow-left">Danh sách</flux:button>
            <livewire:leads.lead-lifecycle :lead-id="$lead->id" />
            @can('update', $lead)
                <flux:button :href="route('leads.edit', $lead)" wire:navigate variant="primary" icon="pencil-square">Chỉnh sửa</flux:button>
            @endcan
        </div>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(20rem,1fr)]">
        <div class="space-y-6">
            <section class="crm-card" aria-labelledby="lead-detail-contact">
                <h2 id="lead-detail-contact" class="text-lg font-semibold">Thông tin liên hệ</h2>
                <dl class="mt-5 grid gap-x-6 gap-y-5 sm:grid-cols-2">
                    <div><dt class="text-sm text-slate-500">Email</dt><dd class="mt-1 font-medium">{{ $lead->email ?: 'Chưa có' }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Điện thoại</dt><dd class="mt-1 font-medium">{{ $lead->phone ?: 'Chưa có' }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Điện thoại phụ</dt><dd class="mt-1 font-medium">{{ $lead->secondary_phone ?: 'Chưa có' }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Công ty</dt><dd class="mt-1 font-medium">{{ $lead->company_name ?: 'Chưa có' }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Chức danh</dt><dd class="mt-1 font-medium">{{ $lead->job_title ?: 'Chưa có' }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Website</dt><dd class="mt-1 font-medium break-all">{{ $lead->website ?: 'Chưa có' }}</dd></div>
                </dl>
            </section>

            <section class="crm-card" aria-labelledby="lead-detail-address">
                <h2 id="lead-detail-address" class="text-lg font-semibold">Địa chỉ và nhu cầu</h2>
                <dl class="mt-5 grid gap-x-6 gap-y-5 sm:grid-cols-2">
                    <div class="sm:col-span-2"><dt class="text-sm text-slate-500">Địa chỉ</dt><dd class="mt-1 font-medium">{{ $lead->address ?: 'Chưa có' }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Tỉnh/Thành phố</dt><dd class="mt-1 font-medium">{{ $lead->provinceUnit?->full_name ?? $lead->province ?? 'Chưa có' }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Phường/Xã</dt><dd class="mt-1 font-medium">{{ $lead->ward?->full_name ?? $lead->city ?? 'Chưa có' }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Quốc gia</dt><dd class="mt-1 font-medium">{{ $lead->country ?: 'Chưa có' }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Giá trị dự kiến</dt><dd class="mt-1 font-medium">{{ $lead->estimated_value !== null ? number_format((float) $lead->estimated_value, 0, ',', '.').' ₫' : 'Chưa xác định' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-sm text-slate-500">Ghi chú</dt><dd class="mt-1 whitespace-pre-line text-sm leading-6">{{ $lead->notes ?: 'Chưa có ghi chú' }}</dd></div>
                </dl>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="crm-card" aria-labelledby="lead-detail-owner">
                <h2 id="lead-detail-owner" class="font-semibold">Phân công</h2>
                <dl class="mt-4 space-y-4">
                    <div><dt class="text-sm text-slate-500">Người phụ trách</dt><dd class="mt-1 font-medium">{{ $lead->owner?->name ?? 'Chưa phân công' }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Phòng ban</dt><dd class="mt-1 font-medium">{{ $lead->department ? $lead->department->name.' ('.$lead->department->code.')' : 'Chưa gán' }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Nguồn</dt><dd class="mt-1 font-medium">{{ $lead->source?->name ?? 'Chưa xác định' }}</dd></div>
                </dl>
                <div class="mt-5 flex flex-wrap gap-2">
                    @forelse ($lead->tags as $tag)
                        <flux:badge color="zinc">{{ $tag->name }}</flux:badge>
                    @empty
                        <span class="text-sm text-slate-500">Chưa có tag</span>
                    @endforelse
                </div>
            </section>

            <section class="crm-card" aria-labelledby="lead-detail-audit">
                <h2 id="lead-detail-audit" class="font-semibold">Thông tin hệ thống</h2>
                <dl class="mt-4 space-y-4 text-sm">
                    <div><dt class="text-slate-500">Người tạo</dt><dd class="mt-1 font-medium">{{ $lead->createdBy?->name ?? 'Hệ thống' }}</dd></div>
                    <div><dt class="text-slate-500">Thời gian tạo</dt><dd class="mt-1 font-medium">{{ $lead->created_at?->timezone(config('crm.display_timezone'))->format('d/m/Y H:i:s') }}</dd></div>
                    <div><dt class="text-slate-500">Cập nhật bởi</dt><dd class="mt-1 font-medium">{{ $lead->updatedBy?->name ?? 'Hệ thống' }}</dd></div>
                    <div><dt class="text-slate-500">Cập nhật lúc</dt><dd class="mt-1 font-medium">{{ $lead->updated_at?->timezone(config('crm.display_timezone'))->format('d/m/Y H:i:s') }}</dd></div>
                </dl>
            </section>
        </aside>
    </div>

    <livewire:leads.lead-workflow :lead-id="$lead->id" />
@endsection
