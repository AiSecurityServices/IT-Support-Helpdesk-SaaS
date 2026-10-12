<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'number', 'customer_id', 'contact_id', 'status', 'currency',
        'discount_percent', 'tax_percent', 'subtotal', 'discount_total',
        'tax_total', 'total', 'amount_paid', 'issue_date', 'due_date',
        'payment_terms_days', 'notes', 'provider', 'provider_payment_id',
        'provider_checkout_url',
    ];

    protected $casts = ['issue_date' => 'date', 'due_date' => 'date'];

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->number) && $invoice->tenant_id) {
                $prefix = tenant_setting('invoice.prefix', config('supportflow.invoice_prefix', 'INV-'));
                $year = now()->format('Y');
                $count = static::withoutTenantScope()->where('tenant_id', $invoice->tenant_id)->count() + 1;
                $invoice->number = $prefix.$year.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function balance(): float
    {
        return round((float) $this->total - (float) $this->amount_paid, 2);
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, ['issued', 'partially_paid'], true)
            && $this->due_date && $this->due_date->isPast();
    }

    /**
     * Recompute totals from items using decimal-safe arithmetic.
     * All money stored with 2 decimals; rounding happens per line then on totals.
     */
    public function recalculate(): void
    {
        $subtotal = '0';
        foreach ($this->items as $item) {
            $subtotal = bcadd($subtotal, (string) $item->line_total, 2);
        }
        $discount = bcmul($subtotal, bcdiv((string) $this->discount_percent, '100', 4), 2);
        $taxable = bcsub($subtotal, $discount, 2);
        $tax = bcmul($taxable, bcdiv((string) $this->tax_percent, '100', 4), 2);
        $total = bcadd($taxable, $tax, 2);

        $this->subtotal = $subtotal;
        $this->discount_total = $discount;
        $this->tax_total = $tax;
        $this->total = $total;
        $this->save();
        $this->refreshStatus();
    }

    public function refreshStatus(): void
    {
        if ($this->status === 'void' || $this->status === 'draft') {
            return;
        }
        $balance = $this->balance();
        if ($balance <= 0 && (float) $this->total > 0) {
            $this->status = 'paid';
        } elseif ((float) $this->amount_paid > 0) {
            $this->status = 'partially_paid';
        } elseif ($this->isOverdue()) {
            $this->status = 'overdue';
        } elseif ($this->status === 'overdue' && ! ($this->due_date && $this->due_date->isPast())) {
            $this->status = 'issued';
        }
        $this->save();
    }
}
