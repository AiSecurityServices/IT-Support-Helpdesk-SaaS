<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'invoice_id', 'amount', 'currency', 'method',
        'reference', 'provider_payment_id', 'paid_on', 'recorded_by', 'notes',
    ];

    protected $casts = ['paid_on' => 'date'];
}
