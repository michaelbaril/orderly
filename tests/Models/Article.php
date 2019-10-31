<?php

namespace Baril\Orderable\Tests\Models;

use Baril\Orderable\Concerns\HasOrderedRelationships;
use Baril\Orderable\Tests\Models\Status;
use Baril\Orderable\Tests\Models\Tag;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasOrderedRelationships;

    protected $fillable = ['title', 'body', 'status_id', 'publication_date'];

    public function tags()
    {
        return $this->belongsToManyOrdered(Tag::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
