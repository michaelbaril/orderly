<?php

namespace Baril\Orderly\Tests\Models;

use Baril\Orderly\Tests\Models\Article;

class Paragraph extends Model
{
    use \Baril\Orderly\Concerns\Orderable;

    protected $groupColumn = ['article_id', 'section'];

    protected $fillable = ['article_id', 'section', 'content'];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
