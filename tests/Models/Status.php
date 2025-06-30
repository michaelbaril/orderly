<?php

namespace Baril\Orderly\Tests\Models;

class Status extends Model
{
    use \Baril\Orderly\Concerns\Orderable;

    protected $fillable = ['name'];
}
