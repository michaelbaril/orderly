<?php

namespace Baril\Smoothie\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = ['code', 'name', 'continent'];
    protected $cache = 'array';
}
