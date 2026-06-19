<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_post_can_be_created_and_retrieved(): void
    {
        Post::create(['title' => 'My first post']);

        $this->assertDatabaseHas('posts', [
            'title' => 'My first post',
        ]);
    }
}
