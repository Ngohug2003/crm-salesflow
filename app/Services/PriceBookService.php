<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PriceBook;
use App\Models\PriceBookEntry;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class PriceBookService
{
    public function __construct(private SystemAuditService $audit) {}

    /** @param array<string, mixed> $data */
    public function save(User $actor, ?PriceBook $priceBook, array $data): PriceBook
    {
        Gate::forUser($actor)->authorize($priceBook === null ? 'create' : 'update', $priceBook ?? PriceBook::class);

        return DB::transaction(function () use ($actor, $priceBook, $data): PriceBook {
            $before = $priceBook?->only(['name', 'customer_segment', 'region', 'effective_from', 'effective_until', 'is_active']) ?? [];
            $priceBook ??= new PriceBook(['owner_id' => $actor->id, 'department_id' => $actor->department_id, 'created_by' => $actor->id]);
            $priceBook->fill([...$data, 'updated_by' => $actor->id])->save();
            $this->audit->record($actor, $priceBook, $before === [] ? 'created' : 'updated', "Cập nhật bảng giá {$priceBook->name}", $before, $priceBook->only(['name', 'customer_segment', 'region', 'effective_from', 'effective_until', 'is_active']), ['module' => 'price-books']);

            return $priceBook;
        });
    }

    /** @param array<string, mixed> $data */
    public function saveEntry(User $actor, PriceBook $priceBook, ?PriceBookEntry $entry, array $data): PriceBookEntry
    {
        Gate::forUser($actor)->authorize('update', $priceBook);

        $product = Product::query()->findOrFail((int) $data['product_id']);
        Gate::forUser($actor)->authorize('view', $product);

        if ($entry !== null && $entry->price_book_id !== $priceBook->id) {
            abort(404);
        }

        return DB::transaction(function () use ($actor, $priceBook, $entry, $data): PriceBookEntry {
            $before = $entry?->only(['product_id', 'unit_price', 'vat_percent', 'min_quantity', 'is_active']) ?? [];
            $entry ??= new PriceBookEntry(['price_book_id' => $priceBook->id]);
            $entry->fill($data)->save();
            $this->audit->record($actor, $priceBook, 'price_entry_updated', "Cập nhật giá trong {$priceBook->name}", $before, ['entry_id' => $entry->id, 'product_id' => $entry->product_id, 'unit_price' => $entry->unit_price, 'vat_percent' => $entry->vat_percent, 'min_quantity' => $entry->min_quantity], ['module' => 'price-books']);

            return $entry;
        });
    }
}
