# Orderable behavior

Adds orderable behavior to Eloquent models (forked from
<https://github.com/boxfrommars/rutorika-sortable>).

## Setup

First, add a `position` field to your model (see below how to change this name):

```php
public function up()
{
    Schema::create('articles', function (Blueprint $table) {
        // ... other fields ...
        $table->unsignedInteger('position');
    });
}
```

Then, use the `\Baril\Orderable\Concerns\Orderable` trait in your model. The
`position` field should be guarded as it won't be filled manually.

```php
class Article extends Model
{
    use \Baril\Orderable\Concerns\Orderable;

    protected $guarded = ['position'];
}
```

You need to set the `$orderColumn` property if you want another name than
`position`:

```php
class Article extends Model
{
    use \Baril\Orderable\Concerns\Orderable;

    protected $orderColumn = 'order';
    protected $guarded = ['order'];
}
```

## Basic usage

You can use the following method to change the model's position (no need to
save it afterwards, the method does it already):

* `moveToOffset($offset)` (`$offset` starts at 0 and can be negative, ie.
`$offset = -1` is the last position),
* `moveToStart()`,
* `moveToEnd()`,
* `moveToPosition($position)` (`$position` starts at 1 and must be a valid
position),
* `moveUp($positions = 1, $strict = true)`: moves the model up by `$positions`
positions (the `$strict` parameter controls what happens if you try to move the
model "out of bounds": if set to `false`, the model will simply be moved to the
first or last position, else it will throw a `PositionException`),
* `moveDown($positions = 1, $strict = true)`,
* `swapWith($anotherModel)`,
* `moveBefore($anotherModel)`,
* `moveAfter($anotherModel)`.

```php
$model = Article::find(1);
$anotherModel = Article::find(10)
$model->moveAfter($anotherModel);
// $model is now positioned after $anotherModel, and both have been saved
```

Also, this trait:
* automatically defines the model position on the `create` event, so you don't
need to set `position` manually,
* automatically decreases the position of subsequent models on the `delete`
event so that there's no "gap".

```php
$article = new Article();
$article->title = $request->input('title');
$article->body = $request->input('body');
$article->save();
```

This model will be positioned at `MAX(position) + 1`.

To get ordered models, use the `ordered` scope:

```php
$articles = Article::ordered()->get();
$articles = Article::ordered('desc')->get();
```

(You can cancel the effect of this scope by calling the `unordered` scope.)

Previous and next models can be queried using the `previous` and `next`
methods:

```php
$entity = Article::find(10);
$entity->next(10); // returns a QueryBuilder on the next 10 entities, ordered
$entity->previous(5)->get(); // returns a collection with the previous 5 entities, in reverse order
$entity->next()->first(); // returns the next entity
```

## Mass reordering

The `move*` methods described above are not appropriate for mass reordering
because:
* they would perform many unneeded queries,
* changing a model's position affects other model's positions as well, and
can cause side effects if you're not careful.

Example:

```php
$models = Article::orderBy('publication_date', 'desc')->get();
$models->map(function($model, $key) {
    return $model->moveToOffset($key);
});
```

The sample code above will corrupt the data because you need each model to be
"fresh" before you change its position. The following code, on the other hand,
 will work properly:

```php
$collection = Article::orderBy('publication_date', 'desc')->get();
$collection->map(function($model, $key) {
    return $model->fresh()->moveToOffset($key);
});
```

It's still not a good way to do it though, because it performs many unneeded
queries. A better way to handle mass reordering is to use the `saveOrder`
method on a collection:

```php
$collection = Article::orderBy('publication_date', 'desc')->get();
// $collection is not a regular Eloquent collection object, it's a custom class
// with the following additional method:
$collection->saveOrder();
```

That's it! Now the items' order in the collection has been applied to the
`position` column of the database.
```

You can also order a collection explicitely with the `setOrder` method.
It takes an array of ids as a parameter:

```php
$ordered = $collection->setOrder([4, 5, 2]);
```

The returned collection is ordered so that the items with ids 4, 5 and 2
are at the beginning of the collection. Also, the new order is saved to the
database automatically (you don't need to call `saveOrder`).

> Note: Only the models within the collection are reordered / swapped between
> one another. The other rows in the table remain untouched.

You can also use the `setOrder` method, either statically on the model, or on
a query builder.

```php
// This will reorder all statuses (assuming there are 5 statuses in the table):
Status::setOrder([2, 1, 5, 3, 4]);

// This will put the status with id 4 at the beginning, and move the other
// statuses' positions accordingly:
Status::setOrder([4]);

// This will only swap the statuses 3, 4 and 5, and won't change the position
// of the other statuses:
Status::whereKey([3, 4, 5])->setOrder([4, 5, 3]);
```

When used like this, the `setOrder` method returns the number of affected rows.

## Orderable groups / one-to-many relationships

Sometimes, the table's data is "grouped" by some column, and you need to order
each group individually instead of having a global order. To achieve this, you
just need to set the `$groupColumn` property:

```php
class Article extends Model
{
    use \Baril\Orderable\Concerns\Orderable;

    protected $guarded = ['position'];
    protected $groupColumn = 'section_id';
}
```

If the group is defined by multiple columns, you can use an array:

```php
protected $groupColumn = ['field_name1', 'field_name2'];
```

Orderable groups can be used to handle ordered one-to-many relationships:

```php
class Section extends Model
{
    public function articles()
    {
        return $this->hasMany(Article::class)->ordered();
    }
}
```

## Ordered many-to-many relationships

If you need to order a many-to-many relationship, you will need a `position`
column (or some other name) in the pivot table.

Have your model use the `\Baril\Orderable\Concerns\HasOrderedRelationships` trait
(or extend the `Baril\Orderable\Model` class):

```php
class Post extends Model
{
    use \Baril\Orderable\Concerns\HasOrderedRelationships;

    public function tags()
    {
        return $this->belongsToManyOrdered(Tag::class);
    }
}
```

The prototype of the `belongsToManyOrdered` method is similar as `belongsToMany`
with an added 2nd parameter `$orderColumn`:

```php
public function belongsToManyOrdered(
        $related,
        $orderColumn = 'position',
        $table = null,
        $foreignPivotKey = null,
        $relatedPivotKey = null,
        $parentKey = null,
        $relatedKey = null,
        $relation = null)
```

Now all the usual methods from the `BelongsToMany` class will set the proper
position to attached models:

```php
$post->tags()->attach($tag->id); // will attach $tag and give it the last position
$post->tags()->sync([$tag1->id, $tag2->id, $tag3->id]) // will keep the provided order
$post->tags()->detach($tag->id); // will decrement the position of subsequent $tags
```

When queried, the relation is sorted by default. If you want to order the
related models by some other field, you will need to use the `unordered` scope
first:

```php
$post->tags; // ordered by position
$post->tags()->ordered('desc')->get(); // reverse order
$post->tags()->unordered()->get(); // unordered

// Note that orderBy has no effect here since the tags are already ordered by position:
$post->tags()->orderBy('id')->get();

// This is the proper way to do it:
$post->tags()->unordered()->orderBy('id')->get();
```

Of course, you can also define the relation like this if you don't want it
ordered by default:

```php
class Post extends Model
{
    use \Baril\Orderable\Concerns\HasOrderedRelationships;

    public function tags()
    {
        return $this->belongsToManyOrdered(Tag::class)->unordered();
    }
}

$article->tags; // unordered
$article->tags()->ordered()->get(); // ordered
```

The `BelongsToManyOrdered` class has all the same methods as the `Orderable`
trait, except that you will need to pass them a related $model to work with:

* `moveToOffset($model, $offset)`,
* `moveToStart($model)`,
* `moveToEnd($model)`,
* `moveToPosition($model, $position)`,
* `moveUp($model, $positions = 1, $strict = true)`,
* `moveDown($model, $positions = 1, $strict = true)`,
* `swap($model, $anotherModel)`,
* `moveBefore($model, $anotherModel)` (`$model` will be moved before
`$anotherModel`),
* `moveAfter($model, $anotherModel)` (`$model` will be moved after
`$anotherModel`),
* `before($model)` (similar as the `previous` method from the `Orderable` trait),
* `after($model)` (similar as `next`).

```php
$tag1 = $article->tags()->first();
$tag2 = $article->tags()->last();
$article->tags()->moveBefore($tag1, $tag2);
// now $tag1 is at the second to last position
```

Note that if `$model` doesn't belong to the relationship, any of these methods
will throw a `Baril\Orderable\GroupException`.

There's also a method for mass reordering:

```php
$article->tags()->setOrder([$id1, $id2, $id3]);
```

In the example above, tags with ids `$id1`, `$id2`, `$id3` will now be at the
beginning of the article's `tags` collection. Any other tags attached to the
article will come after, in the same order as before calling `setOrder`.

## Ordered morph-to-many relationships

Similarly, the package defines a `MorphToManyOrdered` type of relationship.
The 3rd parameter of the `morphToManyOrdered` method is the name of the order
column (defaults to `position`):

```php
class Post extends Model
{
    use \Baril\Orderable\Concerns\HasOrderedRelationships;

    public function tags()
    {
        return $this->morphToManyOrdered('App\Tag', 'taggable', 'tag_order');
    }
}
```

Same thing with the `morphedByManyOrdered` method:

```php
class Tag extends Model
{
    use \Baril\Orderable\Concerns\HasOrderedRelationships;

    public function posts()
    {
        return $this->morphedByManyOrdered('App\Post', 'taggable', 'order');
    }

    public function videos()
    {
        return $this->morphedByManyOrdered('App\Video', 'taggable', 'order');
    }
}
```
