<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KbFeedback extends Model
{
    use HasFactory;

    protected $table = 'kb_feedback';

    protected $fillable = ['kb_article_id', 'user_id', 'helpful', 'comment'];

    protected $casts = ['helpful' => 'boolean'];
}
