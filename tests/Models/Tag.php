<?php

namespace Baril\Orderly\Tests\Models;

class Tag extends Model
{
    use \Baril\Orderly\Concerns\Orderable;

    protected $fillable = ['name'];

    public function articles()
    {
        return $this->belongsToMany(Article::class);
    }

    public function posts()
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }

    public function videos()
    {
        return $this->morphedByMany(Video::class, 'taggable');
    }
}
