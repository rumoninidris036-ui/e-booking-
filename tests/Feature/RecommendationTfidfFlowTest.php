<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BadmintonField;
use App\Models\Booking;
use App\Models\Facility;
use App\Models\Rating;
use App\Models\User;
use App\Services\Recommendations\CosineSimilarityService;
use App\Services\Recommendations\DocumentBuilderService;
use App\Services\Recommendations\FieldRecommendationService;
use App\Services\Recommendations\RecommendationService;
use App\Services\Recommendations\TFIDFService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationTfidfFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_existing_user_gets_tfidf_recommendations_with_manual_vector_steps(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-07 10:00:00'));

        [$user, $fields] = $this->seedThreeFieldDataset();
        $alpha = $fields['alpha'];
        $beta = $fields['beta'];

        $this->actingAs($user)
            ->getJson(route('public.fields.recommendations', ['limit' => 3]))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.field.slug', $alpha->slug);

        $documents = app(DocumentBuilderService::class)->buildFieldDocuments(
            BadmintonField::query()
                ->with('facilities')
                ->withAvg('ratings', 'score')
                ->withCount([
                    'bookings as recent_bookings_count' => fn ($query) => $query
                        ->where('booking_date', '>=', now()->subDays(30)->toDateString())
                        ->whereIn('status', [Booking::STATUS_PAID, Booking::STATUS_FINISHED]),
                ])
                ->whereIn('id', [$alpha->id, $beta->id, $fields['gamma']->id])
                ->orderBy('id')
                ->get(),
        );

        $tfidf = app(TFIDFService::class);
        $idf = $tfidf->inverseDocumentFrequency($documents->all());
        $profile = app(\App\Services\Recommendations\UserProfileService::class)->buildProfile($user, $documents, $idf);

        $alphaDocument = $documents->firstWhere('field.id', $alpha->id);
        $betaDocument = $documents->firstWhere('field.id', $beta->id);

        $alphaTf = $tfidf->termFrequency($alphaDocument['tokens']);
        $betaTf = $tfidf->termFrequency($betaDocument['tokens']);
        $alphaVector = $tfidf->tfIdf($alphaDocument['tokens'], $idf);
        $betaVector = $tfidf->tfIdf($betaDocument['tokens'], $idf);

        $this->assertArrayHasKey('wifi', $alphaTf);
        $this->assertEqualsWithDelta(
            ($alphaDocument['term_counts']['wifi'] ?? 0) / count($alphaDocument['tokens']),
            $alphaTf['wifi'],
            0.000001,
        );
        $this->assertEqualsWithDelta(log((3 + 1) / (1 + 1)) + 1, $idf['wifi'], 0.000001);
        $this->assertGreaterThan($betaVector['wifi'] ?? 0.0, $alphaVector['wifi'] ?? 0.0);

        $cosine = app(CosineSimilarityService::class);
        $userVector = $profile['vector'];
        $alphaSimilarity = $cosine->similarity($userVector, $alphaVector);
        $betaSimilarity = $cosine->similarity($userVector, $betaVector);

        $this->assertGreaterThan($betaSimilarity, $alphaSimilarity);
        $this->assertGreaterThan(0.0, $alphaSimilarity);
    }

    public function test_new_user_still_gets_recommendations_from_global_fallback(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-07 10:00:00'));

        $this->seedThreeFieldDataset();

        $this->getJson(route('public.fields.recommendations', ['limit' => 3]))
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_empty_dataset_returns_empty_recommendations(): void
    {
        $this->getJson(route('public.fields.recommendations', ['limit' => 3]))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_single_court_returns_recommendation_without_error(): void
    {
        $field = BadmintonField::query()->create([
            'name' => 'Single Court',
            'slug' => 'single-court',
            'address' => 'Jl. Tunggal 1',
            'price_per_hour' => 100000,
            'is_active' => true,
        ]);

        $this->getJson(route('public.fields.recommendations', ['limit' => 3]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.field.slug', $field->slug);
    }

    public function test_multiple_courts_rank_by_similarity(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-07 10:00:00'));

        [$user, $fields] = $this->seedThreeFieldDataset();

        $this->actingAs($user)
            ->getJson(route('public.fields.recommendations', ['limit' => 3]))
            ->assertOk()
            ->assertJsonPath('data.0.field.slug', $fields['alpha']->slug)
            ->assertJsonCount(3, 'data');
    }

    public function test_empty_facility_does_not_break_recommendations(): void
    {
        $field = BadmintonField::query()->create([
            'name' => 'Facility Free Court',
            'slug' => 'facility-free-court',
            'description' => 'Indoor court sederhana',
            'address' => 'Jl. Tanpa Fasilitas 1',
            'price_per_hour' => 90000,
            'is_active' => true,
        ]);

        $this->getJson(route('public.fields.recommendations', ['limit' => 3]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.field.slug', $field->slug);
    }

    public function test_empty_description_does_not_break_recommendations(): void
    {
        $field = BadmintonField::query()->create([
            'name' => 'No Description Court',
            'slug' => 'no-description-court',
            'description' => null,
            'address' => 'Jl. Deskripsi Kosong 1',
            'price_per_hour' => 95000,
            'is_active' => true,
        ]);

        $this->getJson(route('public.fields.recommendations', ['limit' => 3]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.field.slug', $field->slug);
    }

    public function test_empty_rating_does_not_break_recommendations(): void
    {
        $field = BadmintonField::query()->create([
            'name' => 'Unrated Court',
            'slug' => 'unrated-court',
            'description' => 'lapangan nyaman dan luas',
            'address' => 'Jl. Rating Kosong 1',
            'price_per_hour' => 105000,
            'is_active' => true,
        ]);

        $this->getJson(route('public.fields.recommendations', ['limit' => 3]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.field.slug', $field->slug);
    }

    /**
     * @return array{0: User, 1: array{alpha: BadmintonField, beta: BadmintonField, gamma: BadmintonField}}
     */
    private function seedThreeFieldDataset(): array
    {
        $wifi = Facility::query()->create([
            'name' => 'WiFi',
            'slug' => 'wifi',
            'description' => 'Free internet',
        ]);

        $shower = Facility::query()->create([
            'name' => 'Shower',
            'slug' => 'shower',
            'description' => 'Ruang bilas bersih',
        ]);

        $lighting = Facility::query()->create([
            'name' => 'Lighting',
            'slug' => 'lighting',
            'description' => 'Lampu terang',
        ]);

        $user = User::factory()->create();

        $alpha = BadmintonField::query()->create([
            'name' => 'Alpha Court',
            'slug' => 'alpha-court',
            'description' => 'wifi shower indoor nyaman',
            'address' => 'Jl. Merdeka 1, Ambon',
            'price_per_hour' => 100000,
            'open_time' => '08:00',
            'close_time' => '22:00',
            'slot_duration_minutes' => 60,
            'is_active' => true,
        ]);
        $alpha->facilities()->attach([$wifi->id, $shower->id]);

        $beta = BadmintonField::query()->create([
            'name' => 'Beta Court',
            'slug' => 'beta-court',
            'description' => 'outdoor budget court',
            'address' => 'Jl. Utama 2, Jayapura',
            'price_per_hour' => 70000,
            'open_time' => '08:00',
            'close_time' => '22:00',
            'slot_duration_minutes' => 60,
            'is_active' => true,
        ]);
        $beta->facilities()->attach([$lighting->id]);

        $gamma = BadmintonField::query()->create([
            'name' => 'Gamma Arena',
            'slug' => 'gamma-arena',
            'description' => 'premium lighting indoor',
            'address' => 'Jl. Pantai 3, Jayapura',
            'price_per_hour' => 150000,
            'open_time' => '08:00',
            'close_time' => '22:00',
            'slot_duration_minutes' => 60,
            'is_active' => true,
        ]);

        Booking::query()->create([
            'booking_code' => 'BK-ALPHA-001',
            'badminton_field_id' => $alpha->id,
            'user_id' => $user->id,
            'booking_date' => now()->subDays(4)->format('Y-m-d'),
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => Booking::STATUS_PAID,
            'price_per_hour' => 100000,
        ]);

        Booking::query()->create([
            'booking_code' => 'BK-ALPHA-002',
            'badminton_field_id' => $alpha->id,
            'user_id' => $user->id,
            'booking_date' => now()->subDays(2)->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => Booking::STATUS_FINISHED,
            'price_per_hour' => 100000,
        ]);

        Rating::query()->create([
            'booking_id' => Booking::query()->where('booking_code', 'BK-ALPHA-002')->firstOrFail()->id,
            'badminton_field_id' => $alpha->id,
            'score' => 5,
            'comment' => 'wifi shower nyaman',
        ]);

        Booking::query()->create([
            'booking_code' => 'BK-BETA-001',
            'badminton_field_id' => $beta->id,
            'user_id' => null,
            'booking_date' => now()->subDays(3)->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => Booking::STATUS_PAID,
            'price_per_hour' => 70000,
        ]);

        Booking::query()->create([
            'booking_code' => 'BK-GAMMA-001',
            'badminton_field_id' => $gamma->id,
            'user_id' => null,
            'booking_date' => now()->subDays(1)->format('Y-m-d'),
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'status' => Booking::STATUS_PAID,
            'price_per_hour' => 150000,
        ]);

        return [
            $user,
            [
                'alpha' => $alpha,
                'beta' => $beta,
                'gamma' => $gamma,
            ],
        ];
    }
}
