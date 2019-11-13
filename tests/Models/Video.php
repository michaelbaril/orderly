<?php

namespace Baril\Orderable\Tests\Models;

use Baril\Orderable\Concerns\HasOrderedRelationships;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasOrderedRelationships;

    public function tags()
    {
        return $this->morphToManyOrdered(Tag::class, 'taggable', 'order');
    }
}