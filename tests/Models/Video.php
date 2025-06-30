<?php

namespace Baril\Orderly\Tests\Models;

use Baril\Orderly\Concerns\HasOrderableRelationships;

class Video extends Model
{
    use HasOrderableRelationships;

    public function tags()
    {
        return $this->morphToManyOrderable(Tag::class, 'taggable', 'order');
    }
}
