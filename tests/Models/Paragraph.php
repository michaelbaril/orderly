<?php

namespace Baril\Orderable\Tests\Models;

use Baril\Orderable\Tests\Models\Article;
use Illuminate\Database\Eloquent\Model;

class Paragraph extends Model
{
    use \Baril\Orderable\Concerns\Orderable;

    protected $groupColumn = ['article_id', 'section'];

    protected $fillable = ['article_id', 'section', 'content'];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
