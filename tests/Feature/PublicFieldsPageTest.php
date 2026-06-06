<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BadmintonField;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicFieldsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_fields_page_lists_active_courts(): void
    {
        BadmintonField::query()->create([
            'name' => 'Court Public Page',
            'slug' => 'court-public-page',
            'address' => 'Jl. Public Court',
            'latitude' => -2.5895123,
            'longitude' => 140.6687412,
            'price_per_hour' => 125000,
            'is_active' => true,
        ]);

        BadmintonField::query()->create([
            'name' => 'Court Draft Hidden',
            'slug' => 'court-draft-hidden',
            'price_per_hour' => 90000,
            'is_active' => false,
        ]);

        $this->get(route('public.fields.index'))
            ->assertOk()
            ->assertSee('Explore Courts')
            ->assertSee('Court Public Page')
            ->assertSee(route('public.fields.show', ['slug' => 'court-public-page']))
            ->assertSee(route('public.fields.booking', ['slug' => 'court-public-page']))
            ->assertDontSee('Court Draft Hidden');
    }
}
