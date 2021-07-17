<?php

namespace Baril\Orderly\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use \Baril\Orderly\Concerns\Orderable;

    protected $fillable = ['name'];
}
