<?php

namespace Baril\Orderly\Tests\Models;

use Baril\Orderly\Concerns\HasOrderableRelationships;
use Baril\Orderly\Tests\Models\Status;
use Baril\Orderly\Tests\Models\Tag;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasOrderableRelationships;

    protected $fillable = ['title', 'body', 'status_id', 'publication_date'];

    public function tags()
    {
        return $this->belongsToManyOrderable(Tag::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
