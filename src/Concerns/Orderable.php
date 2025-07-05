<?php

namespace Baril\Orderly\Concerns;

use Baril\Orderly\GroupException;
use Baril\Orderly\OrderableCollection;
use Baril\Orderly\PositionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property string $orderColumn
 */
trait Orderable
{
    use Groupable;

    /**
     * Adds position to model on creating event.
     */
    public static function bootOrderable()
    {
        static::creating(function ($model) {
            /** @var Model $model */
            $orderColumn = $model->getOrderColumn();

            // only automatically calculate next position with max+1 when a position has not been set already
            if ($model->$orderColumn === null) {
                $model->setAttribute($orderColumn, $model->getNextPosition());
            }
        });

        static::updating(function ($model) {
            /** @var Model $model */
            $groupColumn = $model->getGroupColumn();
            $orderColumn = $model->getOrderColumn();
            $query = $model->newQueryInSameGroup();

            // only automatically calculate next position with max+1 when a position has not been set already,
            // or if the group is changing
            if ($model->getPosition() === null || ($groupColumn && $model->isDirty($groupColumn))) {
                $model->setAttribute($orderColumn, $query->max($orderColumn) + 1);
            }
        });

        static::updated(function ($model) {
            $groupColumn = $model->getGroupColumn();
            // If the group was changed, we need to refresh the position for the
            // former group:
            if ($groupColumn && $model->isDirty($groupColumn)) {
                $group = $model->getGroup(true);
                static::whereGroup($group)->ordered()->updateColumnWithRowNumber($model->getOrderColumn());
            }
        });

        static::deleting(function ($model) {
            $model->getConnection()->beginTransaction();
        });

        static::deleted(function ($model) {
            /** @var Model $model */
            $model->next()->decrement($model->getOrderColumn());
            $model->getConnection()->commit();
        });
    }

    public function newCollection(array $models = [])
    {
        return new OrderableCollection($models);
    }

    /**
     * @return string
     */
    public function getOrderColumn()
    {
        return property_exists($this, 'orderColumn') ? $this->orderColumn : 'position';
    }

    public function getPosition()
    {
        return $this->{$this->getOrderColumn()};
    }

    /**
     * Returns the maximum possible position for the current model.
     *
     * @return int
     */
    public function getMaxPosition()
    {
        return $this->newQueryInSameGroup()->max($this->getOrderColumn());
    }

    /**
     * Returns the position for a newly inserted model.
     *
     * @return int
     */
    public function getNextPosition()
    {
        return $this->getMaxPosition() + 1;
    }

    /**
     * @param Builder $query
     * @param string $direction
     *
     * @return void
     */
    public function scopeOrdered($query, $direction = 'asc')
    {
        $this->scopeUnordered($query);
        $query->orderBy($this->getOrderColumn(), $direction);
    }

    /**
     * @param Builder $query
     *
     * @return void
     */
    public function scopeUnordered($query)
    {
        $query->getQuery()->orders = collect($query->getQuery()->orders)
                ->reject(function ($order) {
                    return isset($order['column'])
                           ? $order['column'] === $this->getOrderColumn() : false;
                })->values()->all();
    }

    /**
     * @param Builder $query
     * @param  mixed  $column
     * @param  string  $direction
     *
     * @return void
     */
    public function scopeForceOrderBy($query, $column, $direction = 'asc')
    {
        $this->scopeUnordered($query);
        $query->orderBy($column, $direction);
    }

    /**
     *
     * @param int $newOffset
     *
     * @throws PositionException
     */
    public function moveToOffset($newOffset)
    {
        $query = $this->newQueryInSameGroup();
        $count = $query->count();

        if ($newOffset < 0) {
            $newOffset = $count + $newOffset;
        }
        if ($newOffset < 0 || $newOffset >= $count) {
            throw new PositionException("Invalid offset $newOffset!");
        }

        $oldOffset = $this->previous()->count();

        if ($oldOffset === $newOffset) {
            return $this;
        }

        $entity = $query->ordered()->offset($newOffset)->first();
        if ($oldOffset < $newOffset) {
            return $this->moveAfter($entity);
        } else {
            return $this->moveBefore($entity);
        }
    }

    /**
     * Moves the current model to the first position.
     *
     * @return $this
     */
    public function moveToStart()
    {
        return $this->moveToOffset(0);
    }

    /**
     * moves $this model to the last position.
     *
     */
    public function moveToEnd()
    {
        return $this->moveToOffset(-1);
    }

    /**
     *
     * @param int $newPosition
     */
    public function moveToPosition($newPosition)
    {
        return $this->moveToOffset($newPosition - 1);
    }

    /**
     *
     * @param int $positions
     * @param boolean $strict
     */
    public function moveUp($positions = 1, $strict = true)
    {
        $currentPosition = $this->getPosition();
        $newPosition = $currentPosition - $positions;
        if (!$strict) {
            $newPosition = max(1, $newPosition);
            $newPosition = min($this->getMaxPosition(), $newPosition);
        }
        return $this->moveToPosition($newPosition);
    }

    /**
     *
     * @param int $positions
     * @param boolean $strict
     */
    public function moveDown($positions = 1, $strict = true)
    {
        return $this->moveUp(-$positions, $strict);
    }

    /**
     *
     * @param static $entity
     *
     * @throws GroupException
     */
    public function swapWith($entity)
    {
        if (!$this->isInSameGroupAs($entity)) {
            throw new GroupException('Both models must be in the same group!');
        }

        $this->getConnection()->transaction(function () use ($entity) {
            $orderColumn = $this->getOrderColumn();

            $oldPosition = $this->getPosition();
            $newPosition = $entity->getPosition();

            if ($oldPosition === $newPosition) {
                return;
            }

            $this->setAttribute($orderColumn, $newPosition);
            $entity->setAttribute($orderColumn, $oldPosition);

            $this->save();
            $entity->save();
        });
        return $this;
    }

    /**
     * moves $this model after $entity model (and rearrange all entities).
     *
     * @param static $entity
     *
     * @throws GroupException
     */
    public function moveAfter($entity)
    {
        return $this->move('moveAfter', $entity);
    }

    /**
     * moves $this model before $entity model (and rearrange all entities).
     *
     * @param static $entity
     *
     * @throws GroupException
     */
    public function moveBefore($entity)
    {
        return $this->move('moveBefore', $entity);
    }

    /**
     * @param string $action moveAfter/moveBefore
     * @param static $entity
     *
     * @throws GroupException
     */
    protected function move($action, $entity)
    {
        if (!$this->isInSameGroupAs($entity)) {
            throw new GroupException('Both models must be in same group!');
        }

        $this->getConnection()->transaction(function () use ($entity, $action) {
            $orderColumn = $this->getOrderColumn();

            $oldPosition = $this->getPosition();
            $newPosition = $entity->getPosition();

            if ($oldPosition === $newPosition) {
                return;
            }

            $isMoveBefore = $action === 'moveBefore'; // otherwise moveAfter
            $isMoveForward = $oldPosition < $newPosition;

            if ($isMoveForward) {
                $this->newQueryBetween($oldPosition, $newPosition)->decrement($orderColumn);
            } else {
                $this->newQueryBetween($newPosition, $oldPosition)->increment($orderColumn);
            }

            $this->setAttribute($orderColumn, $this->getNewPosition($isMoveBefore, $isMoveForward, $newPosition));
            $entity->setAttribute($orderColumn, $this->getNewPosition(!$isMoveBefore, $isMoveForward, $newPosition));

            $this->save();
            $entity->save();
        });
        return $this;
    }

    /**
     * @param bool $isMoveBefore
     * @param bool $isMoveForward
     * @param      $position
     *
     * @return mixed
     */
    protected function getNewPosition($isMoveBefore, $isMoveForward, $position)
    {
        if (!$isMoveBefore) {
            ++$position;
        }

        if ($isMoveForward) {
            --$position;
        }

        return $position;
    }

    /**
     * @param $leftPosition
     * @param $rightPosition
     *
     * @return Builder
     */
    protected function newQueryBetween($leftPosition, $rightPosition)
    {
        $orderColumn = $this->getOrderColumn();
        $query = $this->newQueryInSameGroup();

        if (!is_null($leftPosition)) {
            $query->where($orderColumn, '>', $leftPosition);
        }
        if (!is_null($rightPosition)) {
            $query->where($orderColumn, '<', $rightPosition);
        }
        return $query;
    }

    /**
     * @param int $limit
     *
     * @return Builder
     */
    public function previous($limit = null)
    {
        $query = $this->newQueryBetween(null, $this->getPosition())->ordered('desc');
        if ($limit) {
            $query->limit($limit);
        }
        return $query;
    }

    /**
     * @param int $limit
     *
     * @return Builder
     */
    public function next($limit = null)
    {
        $query = $this->newQueryBetween($this->getPosition(), null)->ordered();
        if ($limit) {
            $query->limit($limit);
        }
        return $query;
    }

    /**
     * Reorders the elements based on their ids.
     *
     * @param Builder $query
     * @param array $ids
     * @return int
     */
    public function scopeSetOrder($query, $ids)
    {
        $query = clone $query;
        $query->setQuery($query->getQuery()->cloneWithout(['orders'])->cloneWithoutBindings(['order']));

        $instance = $query->getModel();
        $pdo = $instance->getConnection()->getPdo();

        // We're selecting only the necessary columns:
        $orderColumn = $instance->getOrderColumn();
        $groupColumn = $instance->getGroupColumn();
        $columns = [
            $instance->getKeyName(),
            $orderColumn,
        ];
        if ($groupColumn) {
            $columns = array_merge($columns, (array) $groupColumn);
        }

        $collection = $query->orderByValues($instance->getKeyName(), $ids)
                ->ordered()
                ->select($columns)
                ->get();

        $oldPositions = $collection->pluck($orderColumn);
        $collection->saveOrder();
        $newPositions = $collection->pluck($orderColumn);

        return $oldPositions->combine($newPositions)->map(function ($new, $old) {
            return (int) ($old != $new);
        })->sum();
    }
}
