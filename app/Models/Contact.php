<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'customer_id', 'first_name', 'last_name', 'email',
        'phone', 'job_title', 'department', 'is_primary', 'portal_access',
    ];

    protected $casts = ['is_primary' => 'boolean', 'portal_access' => 'boolean'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.($this->last_name ?? ''));
    }
}
