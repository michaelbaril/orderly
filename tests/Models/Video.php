<?php

namespace Baril\Orderable\Tests\Models;

use Baril\Orderable\Concerns\HasOrderableRelationships;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasOrderableRelationships;

    public function tags()
    {
        return $this->morphToManyOrderable(Tag::class, 'taggable', 'order');
    }
}
