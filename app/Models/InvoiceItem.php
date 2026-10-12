<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id', 'ticket_id', 'time_entry_id', 'description',
        'quantity', 'unit_price', 'line_total',
    ];

    protected static function booted(): void
    {
        static::saving(function (InvoiceItem $item) {
            $item->line_total = round((float) $item->quantity * (float) $item->unit_price, 2);
        });
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
