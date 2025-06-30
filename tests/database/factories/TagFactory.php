<?php

namespace Baril\Orderly\Tests\Factories;

use Baril\Orderly\Tests\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->word,
        ];
    }
}
