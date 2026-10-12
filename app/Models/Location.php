<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['tenant_id', 'customer_id', 'name', 'address', 'city', 'postcode'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
