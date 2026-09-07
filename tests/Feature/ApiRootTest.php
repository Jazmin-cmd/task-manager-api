<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiRootTest extends TestCase
{
    public function test_root_endpoint_describes_the_api(): void
    {
        $this->getJson('/')
            ->assertOk()
            ->assertJsonPath('name', 'task-manager-api');
    }
}
