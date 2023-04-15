<?php

namespace Modules\Car\Tests;

use Tests\TestCase;

class CarFeatureTest extends TestCase
{
    /** @test */
    public function get_cars_should_return_success(): void
    {
        $response = $this->get('/cars');

        $response->assertStatus(200);
    }
}
