<?php

namespace Baril\Orderly\Tests\Models;

use Baril\Orderly\Concerns\HasOrderableRelationships;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasOrderableRelationships;

    public function tags()
    {
        return $this->morphToManyOrderable(Tag::class, 'taggable', 'order');
    }
}
