<?php

namespace Baril\Orderable\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use \Baril\Orderable\Concerns\Orderable;

    protected $fillable = ['name'];
}
