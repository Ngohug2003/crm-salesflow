<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\QuoteWorkflowException;
use App\Models\Quote;
use App\Models\QuoteApprovalRule;
use App\Models\QuoteBrandingSetting;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

final class QuoteSettingsService
{
    /**
     * @param  array<string, string|null>  $branding
     * @param  list<array{id: int, minimum_discount_percent: string, maximum_discount_percent: string|null}>  $rules
     */
    public function update(User $actor, array $branding, array $rules, ?UploadedFile $logo = null): void
    {
        Gate::forUser($actor)->authorize('manageSettings', Quote::class);
        $this->validateRuleContinuity($rules);

        $current = QuoteBrandingSetting::query()->firstOrNew();
        $oldLogoPath = $current->logo_path;
        if ($logo !== null) {
            $path = $logo->store('quotes/branding', ['disk' => 'local']);
            if (! is_string($path)) {
                throw new QuoteWorkflowException('Không thể lưu logo báo giá.');
            }
            $branding['logo_path'] = $path;
        }

        DB::transaction(function () use ($actor, $branding, $rules, $current): void {
            QuoteBrandingSetting::query()->updateOrCreate(
                ['id' => $current->exists ? $current->getKey() : 1],
                [...$branding, 'updated_by' => $actor->id],
            );

            foreach ($rules as $rule) {
                QuoteApprovalRule::query()->whereKey($rule['id'])->update([
                    'minimum_discount_percent' => $rule['minimum_discount_percent'],
                    'maximum_discount_percent' => $rule['maximum_discount_percent'],
                ]);
            }
        });

        if ($logo !== null && $oldLogoPath !== null && $oldLogoPath !== $branding['logo_path']) {
            Storage::disk('local')->delete($oldLogoPath);
        }
    }

    /** @param list<array{id: int, minimum_discount_percent: string, maximum_discount_percent: string|null}> $rules */
    private function validateRuleContinuity(array $rules): void
    {
        $ordered = collect($rules)->sortBy(
            static fn (array $rule): string => str_pad($rule['minimum_discount_percent'], 8, '0', STR_PAD_LEFT)
        )->values();

        foreach ($ordered as $index => $rule) {
            $minimum = BigDecimal::of($rule['minimum_discount_percent']);
            $maximum = $rule['maximum_discount_percent'] !== null && $rule['maximum_discount_percent'] !== ''
                ? BigDecimal::of($rule['maximum_discount_percent'])
                : null;

            if ($maximum !== null && $maximum->isLessThan($minimum)) {
                throw new QuoteWorkflowException('Ngưỡng tối đa phải lớn hơn hoặc bằng ngưỡng tối thiểu.');
            }

            if ($index > 0) {
                $previous = $ordered[$index - 1]['maximum_discount_percent'];
                if ($previous !== null && $previous !== ''
                    && $minimum->isLessThanOrEqualTo(BigDecimal::of($previous))) {
                    throw new QuoteWorkflowException('Các khoảng chiết khấu không được giao nhau.');
                }
            }
        }
    }
}
