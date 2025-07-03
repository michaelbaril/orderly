<?php

namespace Baril\Orderly\Tests;

use Baril\Orderly\Tests\Models\Tag;
use Illuminate\Support\Facades\DB;

class MixinsTest extends TestCase
{
    public function test_order_by_values()
    {
        $ids = Tag::factory()->count(5)->create()->pluck('id')->all();
        $shuffled = [];
        foreach ([1, 4, 0, 3, 2] as $i) {
            $shuffled[] = $ids[$i];
        }
        $this->assertEquals($shuffled, Tag::orderByValues('id', $shuffled)->pluck('id')->all());
    }

    public function test_update_with_row_number()
    {
        Tag::factory()->count(5)->create()->pluck('id')->all();
        Tag::orderBy('id', 'desc')->updateColumnWithRowNumber('position');
        $this->assertEquals(
            [5, 4, 3, 2, 1],
            Tag::orderBy('id')->get()->map->getPosition()->all()
        );
    }
}
