<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KbArticleVersion extends Model
{
    use HasFactory;

    protected $fillable = ['kb_article_id', 'version', 'title', 'body', 'edited_by'];
}
