<?php

namespace Baril\Orderly\Tests;

use Baril\Orderly\OrderableCollection;
use Baril\Orderly\Tests\Models\Status;

class CollectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Status::factory()->count(5)->create();
    }

    public function test_collection()
    {
        $this->assertInstanceOf(OrderableCollection::class, Status::all());
    }

    public function test_save_order()
    {
        $statuses = Status::whereIn('position', [2, 4])->get()->sortDesc()->saveOrder();
        $this->assertEquals(
            [2, 4],
            $statuses->pluck('position')->all()
        );
        $this->assertEquals(
            [1, 4, 3, 2, 5],
            Status::orderBy('id')->get()->pluck('position')->all()
        );
    }

    public function test_set_order()
    {
        $ids = Status::orderBy('id')->pluck('id');
        $movedToTop = [$ids[4], $ids[0], $ids[2]];
        $statuses = Status::all()->setOrder($movedToTop);
        $this->assertEquals(
            $movedToTop,
            $statuses->take(3)->modelKeys(),
        );
        $this->assertEquals(
            [1, 2, 3, 4, 5],
            $statuses->pluck('position')->all()
        );
        $this->assertEquals(
            [2, 4, 3, 5, 1],
            Status::orderBy('id')->get()->pluck('position')->all()
        );
    }
}
