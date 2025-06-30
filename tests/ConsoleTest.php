<?php

namespace Baril\Orderly\Tests;

use Baril\Orderly\Tests\Models\Article;
use Baril\Orderly\Tests\Models\Paragraph;
use Baril\Orderly\Tests\Models\Tag;

class ConsoleTest extends TestCase
{
    public function test_fix_positions()
    {
        $tags = Tag::factory()->count(5)->create();

        $tags[0]->newModelQuery()
            ->toBase()
            ->limit(1)
            ->update(['position' => 10]);

        $this->assertNotEquals([1, 2, 3, 4, 5], Tag::ordered()->get()->pluck('position')->all());
        $this->artisan('orderly:fix-positions', ['model' => Tag::class]);
        $this->assertEquals([1, 2, 3, 4, 5], Tag::ordered()->get()->pluck('position')->all());
    }

    public function test_fix_positions_within_groups()
    {
        $article = Article::factory()->create();
        $paragraphs = Paragraph::factory()->count(10)->create([
            'article_id' => $article->id,
            'section' => 1,
        ]);
        $paragraphs->skip(5)->each(function ($paragraph) {
            $paragraph->section = 2;
            $paragraph->save();
        });

        $this->assertEquals([1, 2, 3, 4, 5, 1, 2, 3, 4, 5], Paragraph::orderBy('id')->pluck('position')->all());

        $paragraphs[0]->position = 0;
        $paragraphs[0]->save();

        $paragraphs[7]->position = 10;
        $paragraphs[7]->save();

        $this->artisan('orderly:fix-positions', ['model' => Paragraph::class]);

        $this->assertEquals([1, 2, 3, 4, 5, 1, 2, 5, 3, 4], Paragraph::orderBy('id')->pluck('position')->all());
    }

    public function test_fix_relation_positions()
    {
        $article = Article::factory()->create();
        $article->tags()->attach(Tag::factory()->count(5)->create());

        $article->tags()
            ->newPivotStatement()
            ->limit(1)
            ->update(['position' => 10]);

        $this->assertNotEquals([1, 2, 3, 4, 5], $article->tags()->ordered()->get()->pluck('pivot.position')->all());
        $this->artisan('orderly:fix-positions', [
            'model' => Article::class,
            'relationName' => 'tags',
        ]);
        $this->assertEquals([1, 2, 3, 4, 5], $article->tags()->ordered()->get()->pluck('pivot.position')->all());
    }
}
