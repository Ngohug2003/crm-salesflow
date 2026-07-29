<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class QuoteVersion extends Model
{
    /** @var list<string> */
    protected $fillable = ['quote_id', 'version', 'snapshot', 'created_by'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['version' => 'integer', 'snapshot' => 'array'];
    }

    /** @return BelongsTo<Quote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /** @return HasOne<QuoteDocument, $this> */
    public function document(): HasOne
    {
        return $this->hasOne(QuoteDocument::class);
    }
}
