<?php

namespace Baril\Orderly\Tests;

use Baril\Orderly\PositionException;
use Baril\Orderly\Tests\Models\Status;

class OrderableTest extends TestCase
{
    protected $items;

    protected function setUp(): void
    {
        parent::setUp();
        $this->items = Status::factory()->count(5)->create();
    }

    protected function assertPositions($expected)
    {
        $actual = Status::orderBy('id')->pluck('position')->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function test_position_on_create()
    {
        $this->assertEquals(5, $this->items[4]->position);
    }

    public function test_positions_on_delete()
    {
        $this->items[2]->delete();
        $this->assertPositions([1, 2, 3, 4]);
    }

    public function test_move()
    {
        $this->assertPositions([1, 2, 3, 4, 5]);
        $this->items[1]->fresh()->moveToOffset(-2);
        $this->assertPositions([1, 4, 2, 3, 5]);
        $this->items[2]->fresh()->moveToStart();
        $this->assertPositions([2, 4, 1, 3, 5]);
        $this->items[3]->fresh()->moveToEnd();
        $this->assertPositions([2, 3, 1, 5, 4]);
        $this->items[4]->fresh()->moveToPosition(3);
        $this->assertPositions([2, 4, 1, 5, 3]);
        $this->items[0]->fresh()->moveToPosition(4);
        $this->assertPositions([4, 3, 1, 5, 2]);
        $this->items[1]->fresh()->swapWith($this->items[3]->fresh());
        $this->assertPositions([4, 5, 1, 3, 2]);
        $this->items[2]->fresh()->moveBefore($this->items[0]->fresh());
        $this->assertPositions([4, 5, 3, 2, 1]);
        $this->items[3]->fresh()->moveAfter($this->items[1]->fresh());
        $this->assertPositions([3, 4, 2, 5, 1]);
        $this->items[3]->fresh()->moveBefore($this->items[1]->fresh());
        $this->assertPositions([3, 5, 2, 4, 1]);
        $this->items[3]->fresh()->moveAfter($this->items[4]->fresh());
        $this->assertPositions([4, 5, 3, 2, 1]);
        $this->items[0]->fresh()->moveUp(2);
        $this->assertPositions([2, 5, 4, 3, 1]);
        $this->items[3]->fresh()->moveDown(12345, false);
        $this->assertPositions([2, 4, 3, 5, 1]);
    }

    public function test_move_to_invalid_position()
    {
        $this->expectException(PositionException::class);
        $this->items[0]->moveToPosition(12);
    }

    public function test_ordered_scope()
    {
        $this->items[2]->moveToPosition(5);
        $expected = [
            $this->items[2]->id,
            $this->items[4]->id,
            $this->items[3]->id,
            $this->items[1]->id,
            $this->items[0]->id,
        ];
        $actual = Status::ordered('desc')->pluck('id')->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function test_unordered_scope()
    {
        $this->items[2]->moveToPosition(5);
        $expected = $this->items->pluck('id')->toArray();

        $actual = Status::ordered()->orderBy('id')->pluck('id')->toArray();
        $this->assertNotEquals($expected, $actual);

        $actual = Status::ordered()->unordered()->orderBy('id')->pluck('id')->toArray();
        $this->assertEquals($expected, $actual);

        $actual = Status::ordered()->forceOrderBy('id')->pluck('id')->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function test_previous_and_next()
    {
        $this->assertEquals(2, $this->items[2]->previous()->count());
        $this->assertEquals(3, $this->items[1]->next()->count());
    }

    /**
     * @dataProvider reorderProvider
     */
    public function test_reorder($pick, $expectedNewOrder, $expectedAffected)
    {
        $ids = [];
        foreach ($pick as $pos) {
            $ids[] = $this->items[$pos - 1]->id;
        }

        $affected = Status::setOrder($ids);
        $this->assertEquals($expectedAffected, $affected);

        $ids = Status::ordered()->pluck('id')->all();
        $items = $this->items->pluck('id')->flip();
        $newOrder = [];
        foreach ($ids as $id) {
            $newOrder[] = $items[$id] + 1;
        }

        $this->assertEquals($expectedNewOrder, $newOrder);
    }

    public static function reorderProvider()
    {
        return [
            'all' => [
                [5, 2, 1, 3, 4],
                [5, 2, 1, 3, 4],
                4,
            ],
            'subset1' => [
                [2, 5, 3],
                [2, 5, 3, 1, 4],
                4,
            ],
            'subset2' => [
                [5, 4],
                [5, 4, 1, 2, 3],
                5,
            ],
        ];
    }
}
