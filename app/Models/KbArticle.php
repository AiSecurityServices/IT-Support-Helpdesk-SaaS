<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class KbArticle extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    public const STATUSES = ['draft', 'review', 'published', 'archived'];

    protected $fillable = [
        'tenant_id', 'kb_category_id', 'title', 'slug', 'body', 'visibility',
        'status', 'author_id', 'version', 'last_reviewed_at', 'tags',
    ];

    protected $casts = ['last_reviewed_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (KbArticle $a) {
            $a->slug = $a->slug ?: Str::slug($a->title);
            $a->version = $a->version ?? 1;
            $a->status = $a->status ?? 'draft';
            $a->visibility = $a->visibility ?? 'internal';
        });
    }

    public function category()
    {
        return $this->belongsTo(KbCategory::class, 'kb_category_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function versions()
    {
        return $this->hasMany(KbArticleVersion::class)->orderByDesc('version');
    }

    public function feedback()
    {
        return $this->hasMany(KbFeedback::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeVisibleTo($query, ?User $user)
    {
        // Internal docs require staff; public articles are visible to customers.
        if ($user && $user->isStaff() && (int) $user->tenant_id === (int) $this->tenant_id) {
            return $query;
        }

        return $query->where('visibility', 'public');
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === 'published' && $this->visibility === 'public';
    }
}
