<?php

namespace Baril\Orderly\Tests;

use Baril\Orderly\Tests\Models\Post;
use Baril\Orderly\Tests\Models\Tag;
use Baril\Orderly\Tests\Models\Video;

class MorphToManyOrderedTest extends BelongsToManyOrderedTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->articles = Post::factory()->count(2)->create();
        $this->items = Tag::factory()->count(8)->create();
        $video = Video::factory()->create();
        $video->tags()->sync($this->items);
    }
}
