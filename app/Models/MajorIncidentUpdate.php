<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MajorIncidentUpdate extends Model
{
    use HasFactory;

    protected $fillable = ['major_incident_id', 'author_id', 'body', 'notify_audience'];

    protected $casts = ['notify_audience' => 'boolean'];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
