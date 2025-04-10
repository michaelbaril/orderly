<?php

namespace Baril\Orderly\Tests;

use Baril\Orderly\Tests\Models\Article;
use Baril\Orderly\Tests\Models\Tag;

class ConsoleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->article = factory(Article::class)->create();
        $this->tags = factory(Tag::class, 5)->create();
        $this->article->tags()->attach(Tag::all());
    }

    public function test_fix_positions()
    {
        $this->tags[0]->newModelQuery()
            ->toBase()
            ->limit(1)
            ->update(['position' => 10]);

        $this->assertNotEquals([1, 2, 3, 4, 5], Tag::ordered()->get()->pluck('position')->all());
        $this->artisan('orderly:fix-positions ' . addslashes(Tag::class));
        $this->assertEquals([1, 2, 3, 4, 5], Tag::ordered()->get()->pluck('position')->all());
    }

    public function test_fix_relation_positions()
    {
        $this->article->tags()
            ->newPivotStatement()
            ->limit(1)
            ->update(['position' => 10]);

        $this->assertNotEquals([1, 2, 3, 4, 5], $this->article->tags()->ordered()->get()->pluck('pivot.position')->all());
        $this->artisan('orderly:fix-positions ' . addslashes(Article::class) . ' tags');
        $this->assertEquals([1, 2, 3, 4, 5], $this->article->tags()->ordered()->get()->pluck('pivot.position')->all());
    }
}
