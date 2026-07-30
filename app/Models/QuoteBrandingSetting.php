<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class QuoteBrandingSetting extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'company_name', 'tax_code', 'address', 'hotline', 'email', 'logo_path',
        'payment_terms', 'bank_information', 'updated_by',
    ];
}
