<?php

namespace Tests\Feature;

use Illuminate\Foundation\Application;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    public function test_application_bootstraps(): void
    {
        $this->assertInstanceOf(Application::class, $this->app);
        $this->assertTrue($this->app->isBooted());
    }

    public function test_home_page_is_available(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertViewIs('welcome')
            ->assertSee('Brasileirão Analytics')
            ->assertSee('2027')
            ->assertSee('Em desenvolvimento');
    }
}
