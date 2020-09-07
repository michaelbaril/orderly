<?php

namespace Baril\Orderable\Concerns;

use Baril\Orderable\Relations\BelongsToManyOrderable;
use Baril\Orderable\Relations\MorphToManyOrderable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait HasOrderableRelationships
{
    public static function bootHasOrderableRelationships()
    {
        static::$manyMethods = array_merge(static::$manyMethods, [
            'belongsToManyOrderable',
            'morphToManyOrderable',
            'morphedByManyOrderable',
            'belongsToManyOrdered',
            'morphToManyOrdered',
            'morphedByManyOrdered',
        ]);
    }

    /**
     * Define a many-to-many orderable relationship.
     * The prototype is similar as the belongsToMany method, with the
     * $orderColumn added as the 2nd parameter.
     *
     * @param string $related
     * @param string $orderColumn
     * @param string $table
     * @param string $foreignPivotKey
     * @param string $relatedPivotKey
     * @param string $parentKey
     * @param string $relatedKey
     * @param string $relation
     *
     * @return BelongsToSortedMany
     */
    public function belongsToManyOrderable(
        $related,
        $orderColumn = 'position',
        $table = null,
        $foreignPivotKey = null,
        $relatedPivotKey = null,
        $parentKey = null,
        $relatedKey = null,
        $relation = null
    ) {
    
        // If no relationship name was passed, we will pull backtraces to get the
        // name of the calling function. We will use that function name as the
        // title of this relation since that is a great convention to apply.
        if (is_null($relation)) {
            $relation = $this->guessBelongsToManyRelation();
        }

        // First, we'll need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we'll make the query
        // instances as well as the relationship instances we need for this.
        $instance = $this->newRelatedInstance($related);

        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();

        $relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey();

        // If no table name was provided, we can guess it by concatenating the two
        // models using underscores in alphabetical order. The two model names
        // are transformed to snake case from their default CamelCase also.
        if (is_null($table)) {
            $table = $this->joiningTable($related);
        }

        return new BelongsToManyOrderable(
            $instance->newQuery(),
            $this,
            $orderColumn,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(),
            $relation
        );
    }

    public function belongsToManyOrdered(
        $related,
        $orderColumn = 'position',
        $table = null,
        $foreignPivotKey = null,
        $relatedPivotKey = null,
        $parentKey = null,
        $relatedKey = null,
        $relation = null
    ) {
    
        return $this->belongsToManyOrderable(
            $related,
            $orderColumn,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey,
            $relatedKey,
            $relation
        )->ordered();
    }

    /**
     * Define a polymorphic orderable many-to-many relationship.
     *
     * @param  string  $related
     * @param  string  $name
     * @param  string  $table
     * @param  string  $foreignPivotKey
     * @param  string  $relatedPivotKey
     * @param  string  $parentKey
     * @param  string  $relatedKey
     * @param  bool  $inverse
     * @return \Baril\Orderable\Relations\MorphToManyOrderable
     */
    public function morphToManyOrderable(
        $related,
        $name,
        $orderColumn = 'position',
        $table = null,
        $foreignPivotKey = null,
        $relatedPivotKey = null,
        $parentKey = null,
        $relatedKey = null,
        $inverse = false
    ) {
    
        $caller = $this->guessBelongsToManyRelation();

        // First, we will need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we will make the query
        // instances, as well as the relationship instances we need for these.
        $instance = $this->newRelatedInstance($related);

        $foreignPivotKey = $foreignPivotKey ?: $name . '_id';

        $relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey();

        // Now we're ready to create a new query builder for this related model and
        // the relationship instances for this relation. This relations will set
        // appropriate query constraints then entirely manages the hydrations.
        $table = $table ?: Str::plural($name);

        return new MorphToManyOrderable(
            $instance->newQuery(),
            $this,
            $name,
            $orderColumn,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(),
            $caller,
            $inverse
        );
    }

    public function morphToManyOrdered(
        $related,
        $name,
        $orderColumn = 'position',
        $table = null,
        $foreignPivotKey = null,
        $relatedPivotKey = null,
        $parentKey = null,
        $relatedKey = null,
        $inverse = false
    ) {
    
        return $this->morphToManyOrderable(
            $related,
            $name,
            $orderColumn,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey,
            $relatedKey,
            $inverse
        )->ordered();
    }

    /**
     * Define a polymorphic, inverse, orderable many-to-many relationship.
     * The prototype is similar as the morphedByMany method, with the
     * $orderColumn added as the 3rd parameter.
     *
     * @param string $related
     * @param string $name
     * @param string $orderColumn
     * @param string $table
     * @param string $foreignPivotKey
     * @param string $relatedPivotKey
     * @param string $parentKey
     * @param string $relatedKey
     * @return \Baril\Orderable\Relations\MorphToManyOrderable
     */
    public function morphedByManyOrderable(
        $related,
        $name,
        $orderColumn = 'position',
        $table = null,
        $foreignPivotKey = null,
        $relatedPivotKey = null,
        $parentKey = null,
        $relatedKey = null
    ) {
    
        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();

        // For the inverse of the polymorphic many-to-many relations, we will change
        // the way we determine the foreign and other keys, as it is the opposite
        // of the morph-to-many method since we're figuring out these inverses.
        $relatedPivotKey = $relatedPivotKey ?: $name . '_id';

        return $this->morphToManyOrderable(
            $related,
            $name,
            $orderColumn,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey,
            $relatedKey,
            true
        );
    }

    public function morphedByManyOrdered(
        $related,
        $name,
        $orderColumn = 'position',
        $table = null,
        $foreignPivotKey = null,
        $relatedPivotKey = null,
        $parentKey = null,
        $relatedKey = null
    ) {
    
        return $this->morphedByManyOrderable(
            $related,
            $name,
            $orderColumn,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey,
            $relatedKey
        )->ordered();
    }
}
