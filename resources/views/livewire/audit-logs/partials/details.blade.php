@php
    $oldValues = $activity->properties->get('old', []);
    $newValues = $activity->properties->get('new', []);
    $metadata = $activity->properties->except(['old', 'new'])->all();
    $console = $console ?? false;
@endphp

@if (! $console)
    <details class="mt-2 text-sm">
        <summary class="cursor-pointer font-medium text-emerald-700 hover:underline dark:text-emerald-400">Xem dữ liệu trước–sau</summary>
@endif
        <div @class(['mt-3 grid gap-3 xl:grid-cols-2' => ! $console, 'grid gap-3 xl:grid-cols-2' => $console])>
            <div>
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Dữ liệu trước</p>
                <pre class="max-h-72 overflow-auto rounded-lg bg-slate-950 p-3 text-xs text-slate-100">{{ json_encode($oldValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}' }}</pre>
            </div>
            <div>
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Dữ liệu sau</p>
                <pre class="max-h-72 overflow-auto rounded-lg bg-slate-950 p-3 text-xs text-slate-100">{{ json_encode($newValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}' }}</pre>
            </div>
        </div>
        @if ($metadata !== [])
            <div class="mt-3">
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Thông tin bổ sung</p>
                <pre class="max-h-52 overflow-auto rounded-lg bg-slate-950 p-3 text-xs text-slate-100">{{ json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        @endif
@if (! $console)
    </details>
@endif
