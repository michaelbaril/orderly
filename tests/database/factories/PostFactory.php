<?php

namespace Baril\Orderly\Tests\Factories;

use Baril\Orderly\Tests\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence(3),
            'body' => $this->faker->text(50),
        ];
    }
}
