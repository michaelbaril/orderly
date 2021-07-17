<?php

use Faker\Generator as Faker;

$factory->define(Baril\Orderly\Tests\Models\Article::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence(3),
        'body' => $faker->text(50),
    ];
});

$factory->define(Baril\Orderly\Tests\Models\Paragraph::class, function (Faker $faker) {
    return [
        'content' => $faker->text(20),
    ];
});

$factory->define(Baril\Orderly\Tests\Models\Tag::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->word,
    ];
});

$factory->define(Baril\Orderly\Tests\Models\Status::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->word,
    ];
});

$factory->define(Baril\Orderly\Tests\Models\Post::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence(3),
        'body' => $faker->text(50),
    ];
});

$factory->define(Baril\Orderly\Tests\Models\Video::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence(3),
        'url' => $faker->url,
    ];
});
