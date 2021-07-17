<?php

namespace Baril\Orderly\Concerns;

use Baril\Orderly\GroupException;

/**
 * @property string|array $groupColumn Name of the "group" column
 */
trait Groupable
{
    /**
     * Return the name of the "group" field.
     *
     * @return string|null
     */
    public function getGroupColumn()
    {
        return property_exists($this, 'groupColumn') ? $this->groupColumn : null;
    }

    /**
     * Return the group for $this.
     *
     * @param bool $original If set to true, the method will return the "original" value.
     * @return mixed
     */
    public function getGroup($original = false)
    {
        $groupColumn = $this->getGroupColumn();
        if (is_null($groupColumn)) {
            return null;
        }
        if (!is_array($groupColumn)) {
            return $original ? $this->getOriginal($groupColumn) : $this->{$groupColumn};
        }

        $group = [];
        foreach ($groupColumn as $column) {
            $group[] = $original ? $this->getOriginal($column) : $this->$column;
        }
        return $group;
    }

    /**
     * Restrict the query to the provided group.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $group
     */
    public function scopeWhereGroup($query, $group)
    {
        $groupColumn = (array) $this->getGroupColumn();
        $group = is_null($group) ? [ null ] : array_values((array) $group);
        foreach ($group as $i => $value) {
            $query->where($groupColumn[$i], $value);
        }
    }

    /**
     * Restrict the query to the provided groups.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $groups
     */
    public function scopeWhereGroupIn($query, $groups)
    {
        $query->where(function ($query) use ($groups) {
            foreach ($groups as $group) {
                $query->orWhere(function ($query) use ($group) {
                    $this->scopeWhereGroup($query, $group);
                });
            }
        });
    }

    /**
     * Get a new query builder for the model's group.
     *
     * @param boolean $excludeThis
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQueryInSameGroup($excludeThis = false)
    {
        $query = $this->newQuery();
        $groupColumn = (array) $this->getGroupColumn();
        if ($groupColumn) {
            $group = [];
            foreach ($groupColumn as $column) {
                $group[] = $this->$column;
            }
            $query->whereGroup($group);
        }
        if ($excludeThis) {
            $query->whereKeyNot($this->getKey());
        }
        return $query;
    }

    /**
     * Check if $this belongs to the same group as the provided $model.
     *
     * @param static $model
     * @return bool
     *
     * @throws GroupException
     */
    public function isInSameGroupAs($model)
    {
        if (!$model instanceof static) {
            throw new GroupException('Both models must belong to the same class!');
        }
        $groupColumn = (array) $this->getGroupColumn();
        foreach ($groupColumn as $column) {
            if ($model->$column != $this->$column) {
                return false;
            }
        }
        return true;
    }
}
