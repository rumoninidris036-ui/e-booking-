<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\Payments\MidtransGateway;
use App\Models\BadmintonField;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeMidtransGateway;
use Tests\TestCase;

class MidtransPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_generate_snap_payment_for_pending_booking(): void
    {
        $gateway = new FakeMidtransGateway;
        $this->app->instance(MidtransGateway::class, $gateway);

        $user = User::factory()->create();
        $field = BadmintonField::query()->create([
            'name' => 'Arena Bayar',
            'slug' => 'arena-bayar',
            'price_per_hour' => 80000,
            'is_active' => true,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-2026-0001',
            'badminton_field_id' => $field->id,
            'user_id' => $user->id,
            'booking_date' => '2026-05-21',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => Booking::STATUS_PENDING,
            'price_per_hour' => 80000,
        ]);

        $response = $this->actingAs($user)->postJson(route('payments.store', $booking));

        $response->assertCreated()
            ->assertJsonPath('data.order_id', 'BK-2026-0001')
            ->assertJsonPath('data.snap_token', 'snap-token-test')
            ->assertJsonPath('data.status', Payment::STATUS_PENDING)
            ->assertJsonPath('meta.midtrans.client_key', 'Mid-client-eTIvWK6JoSR9nFFU')
            ->assertJsonMissingPath('data.snap_response')
            ->assertJsonMissingPath('data.notification_payload');
    }

    public function test_web_booking_flow_redirects_to_internal_payment_page(): void
    {
        $gateway = new FakeMidtransGateway;
        $gateway->statusResponse = [
            'order_id' => 'BK-2026-0001',
            'status_code' => '201',
            'gross_amount' => '90000.00',
            'transaction_status' => 'pending',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'trx-pending-page',
            'fraud_status' => 'accept',
        ];
        $this->app->instance(MidtransGateway::class, $gateway);

        $user = User::factory()->create();
        $field = BadmintonField::query()->create([
            'name' => 'Arena Redirect',
            'slug' => 'arena-redirect',
            'price_per_hour' => 90000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('public.fields.bookings.store', [
            'slug' => $field->slug,
        ]), [
            'booking_date' => '2026-05-21',
            'start_time' => '08:00',
        ]);

        $payment = Payment::query()->firstOrFail();

        $response->assertRedirect(route('payments.show', $payment));

        $this->actingAs($user)
            ->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee('Continue To Pay')
            ->assertSee('BK-2026-0001');
    }

    public function test_payment_page_syncs_success_status_from_midtrans(): void
    {
        $gateway = new FakeMidtransGateway;
        $gateway->statusResponse = [
            'order_id' => 'BK-2026-0001',
            'status_code' => '200',
            'gross_amount' => '80000.00',
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'trx-sync-success',
            'fraud_status' => 'accept',
        ];
        $this->app->instance(MidtransGateway::class, $gateway);

        $user = User::factory()->create();
        $field = BadmintonField::query()->create([
            'name' => 'Arena Sync Success',
            'slug' => 'arena-sync-success',
            'price_per_hour' => 80000,
            'is_active' => true,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-2026-0001',
            'badminton_field_id' => $field->id,
            'user_id' => $user->id,
            'booking_date' => '2026-05-21',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => Booking::STATUS_PENDING,
            'price_per_hour' => 80000,
        ]);

        $payment = Payment::query()->create([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'order_id' => 'BK-2026-0001',
            'amount' => 80000,
            'currency' => 'IDR',
            'status' => Payment::STATUS_PENDING,
            'snap_redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/test-token',
        ]);

        $this->actingAs($user)
            ->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee('Payment Confirmed')
            ->assertSee('Payment sudah sukses')
            ->assertSee('status halaman ini sudah sinkron');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => Payment::STATUS_SUCCESS,
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => Booking::STATUS_PAID,
        ]);
    }

    public function test_failed_payment_page_still_offers_continue_to_pay(): void
    {
        $gateway = new FakeMidtransGateway;
        $gateway->statusResponse = [
            'order_id' => 'BK-2026-0001',
            'status_code' => '407',
            'gross_amount' => '80000.00',
            'transaction_status' => 'expire',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'trx-failed',
            'fraud_status' => 'accept',
        ];
        $this->app->instance(MidtransGateway::class, $gateway);

        $user = User::factory()->create();
        $field = BadmintonField::query()->create([
            'name' => 'Arena Failed Pay',
            'slug' => 'arena-failed-pay',
            'price_per_hour' => 80000,
            'is_active' => true,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-2026-0001',
            'badminton_field_id' => $field->id,
            'user_id' => $user->id,
            'booking_date' => '2026-05-21',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => Booking::STATUS_PENDING,
            'price_per_hour' => 80000,
        ]);

        $payment = Payment::query()->create([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'order_id' => 'BK-2026-0001',
            'amount' => 80000,
            'currency' => 'IDR',
            'status' => Payment::STATUS_PENDING,
            'snap_redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/test-token',
        ]);

        $this->actingAs($user)
            ->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee('Payment Needs Retry')
            ->assertSee('Continue To Pay');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => Payment::STATUS_FAILED,
        ]);
    }

    public function test_midtrans_return_route_can_apply_local_success_fallback(): void
    {
        $gateway = new FakeMidtransGateway;
        $this->app->instance(MidtransGateway::class, $gateway);

        $user = User::factory()->create();
        $field = BadmintonField::query()->create([
            'name' => 'Arena Return',
            'slug' => 'arena-return',
            'price_per_hour' => 80000,
            'is_active' => true,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-2026-0001',
            'badminton_field_id' => $field->id,
            'user_id' => $user->id,
            'booking_date' => '2026-05-21',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => Booking::STATUS_PENDING,
            'price_per_hour' => 80000,
        ]);

        $payment = Payment::query()->create([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'order_id' => 'BK-2026-0001',
            'amount' => 80000,
            'currency' => 'IDR',
            'status' => Payment::STATUS_PENDING,
            'snap_redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/test-token',
        ]);

        $this->actingAs($user)
            ->get(route('payments.return', $payment, [
                'order_id' => 'BK-2026-0001',
                'callback_state' => 'finish',
            ]))
            ->assertOk()
            ->assertSee('Status Tersinkron')
            ->assertSee('success');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => Payment::STATUS_SUCCESS,
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => Booking::STATUS_PAID,
        ]);
    }

    public function test_retry_payment_after_failed_status_creates_new_order_id(): void
    {
        $gateway = new FakeMidtransGateway;
        $this->app->instance(MidtransGateway::class, $gateway);

        $user = User::factory()->create();
        $field = BadmintonField::query()->create([
            'name' => 'Arena Retry',
            'slug' => 'arena-retry',
            'price_per_hour' => 80000,
            'is_active' => true,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-2026-0001',
            'badminton_field_id' => $field->id,
            'user_id' => $user->id,
            'booking_date' => '2026-05-21',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => Booking::STATUS_PENDING,
            'price_per_hour' => 80000,
        ]);

        Payment::query()->create([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'order_id' => 'BK-2026-0001',
            'amount' => 80000,
            'currency' => 'IDR',
            'status' => Payment::STATUS_FAILED,
        ]);

        $response = $this->actingAs($user)->postJson(route('payments.store', $booking));

        $response->assertCreated()
            ->assertJsonPath('data.order_id', 'BK-2026-0001-PAY-02')
            ->assertJsonPath('data.status', Payment::STATUS_PENDING);

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'order_id' => 'BK-2026-0001-PAY-02',
            'status' => Payment::STATUS_PENDING,
        ]);
    }

    public function test_midtrans_webhook_marks_payment_success_and_booking_paid(): void
    {
        $gateway = new FakeMidtransGateway;
        $gateway->statusResponse = [
            'order_id' => 'BK-2026-0001',
            'status_code' => '200',
            'gross_amount' => '80000.00',
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'trx-456',
            'fraud_status' => 'accept',
        ];
        $this->app->instance(MidtransGateway::class, $gateway);

        $user = User::factory()->create();
        $field = BadmintonField::query()->create([
            'name' => 'Arena Webhook',
            'slug' => 'arena-webhook',
            'price_per_hour' => 80000,
            'is_active' => true,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-2026-0001',
            'badminton_field_id' => $field->id,
            'user_id' => $user->id,
            'booking_date' => '2026-05-21',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => Booking::STATUS_PENDING,
            'price_per_hour' => 80000,
        ]);

        $payment = Payment::query()->create([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'order_id' => 'BK-2026-0001',
            'amount' => 80000,
            'currency' => 'IDR',
            'status' => Payment::STATUS_PENDING,
            'snap_token' => 'snap-token-test',
            'snap_redirect_url' => 'https://app.sandbox.midtrans.com/test',
        ]);

        $payload = [
            'order_id' => 'BK-2026-0001',
            'status_code' => '200',
            'gross_amount' => '80000.00',
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'trx-456',
            'fraud_status' => 'accept',
            'signature_key' => hash('sha512', 'BK-2026-0001'.'200'.'80000.00'.config('services.midtrans.server_key')),
        ];

        $response = $this->postJson(route('webhooks.midtrans.handle'), $payload);

        $response->assertOk()
            ->assertJsonPath('data.payment_id', $payment->id)
            ->assertJsonPath('data.payment_status', Payment::STATUS_SUCCESS)
            ->assertJsonPath('data.booking_status', Booking::STATUS_PAID);
    }

    public function test_midtrans_webhook_is_idempotent_and_ignores_downgrade_status(): void
    {
        $gateway = new FakeMidtransGateway;
        $this->app->instance(MidtransGateway::class, $gateway);

        $user = User::factory()->create();
        $field = BadmintonField::query()->create([
            'name' => 'Arena Idempotent',
            'slug' => 'arena-idempotent',
            'price_per_hour' => 80000,
            'is_active' => true,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-2026-0001',
            'badminton_field_id' => $field->id,
            'user_id' => $user->id,
            'booking_date' => '2026-05-21',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => Booking::STATUS_PENDING,
            'price_per_hour' => 80000,
        ]);

        $payment = Payment::query()->create([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'order_id' => 'BK-2026-0001',
            'amount' => 80000,
            'currency' => 'IDR',
            'status' => Payment::STATUS_PENDING,
        ]);

        $successPayload = [
            'order_id' => 'BK-2026-0001',
            'status_code' => '200',
            'gross_amount' => '80000.00',
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'trx-success',
            'fraud_status' => 'accept',
            'signature_key' => hash('sha512', 'BK-2026-0001'.'200'.'80000.00'.config('services.midtrans.server_key')),
        ];

        $this->postJson(route('webhooks.midtrans.handle'), $successPayload)
            ->assertOk()
            ->assertJsonPath('data.payment_status', Payment::STATUS_SUCCESS);

        $gateway->statusResponse = [
            'order_id' => 'BK-2026-0001',
            'status_code' => '201',
            'gross_amount' => '80000.00',
            'transaction_status' => 'pending',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'trx-pending',
            'fraud_status' => 'accept',
        ];

        $downgradePayload = [
            'order_id' => 'BK-2026-0001',
            'status_code' => '201',
            'gross_amount' => '80000.00',
            'transaction_status' => 'pending',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'trx-pending',
            'fraud_status' => 'accept',
            'signature_key' => hash('sha512', 'BK-2026-0001'.'201'.'80000.00'.config('services.midtrans.server_key')),
        ];

        $this->postJson(route('webhooks.midtrans.handle'), $downgradePayload)
            ->assertOk()
            ->assertJsonPath('data.payment_status', Payment::STATUS_SUCCESS)
            ->assertJsonPath('data.booking_status', Booking::STATUS_PAID);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => Payment::STATUS_SUCCESS,
        ]);
    }

    public function test_midtrans_webhook_rejects_invalid_amount(): void
    {
        $gateway = new FakeMidtransGateway;
        $gateway->statusResponse = [
            'order_id' => 'BK-2026-0001',
            'status_code' => '200',
            'gross_amount' => '75000.00',
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'trx-789',
            'fraud_status' => 'accept',
        ];
        $this->app->instance(MidtransGateway::class, $gateway);

        $user = User::factory()->create();
        $field = BadmintonField::query()->create([
            'name' => 'Arena Invalid',
            'slug' => 'arena-invalid',
            'price_per_hour' => 80000,
            'is_active' => true,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-2026-0001',
            'badminton_field_id' => $field->id,
            'user_id' => $user->id,
            'booking_date' => '2026-05-21',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => Booking::STATUS_PENDING,
            'price_per_hour' => 80000,
        ]);

        Payment::query()->create([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'order_id' => 'BK-2026-0001',
            'amount' => 80000,
            'currency' => 'IDR',
            'status' => Payment::STATUS_PENDING,
        ]);

        $payload = [
            'order_id' => 'BK-2026-0001',
            'status_code' => '200',
            'gross_amount' => '80000.00',
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'trx-789',
            'fraud_status' => 'accept',
            'signature_key' => hash('sha512', 'BK-2026-0001'.'200'.'80000.00'.config('services.midtrans.server_key')),
        ];

        $response = $this->postJson(route('webhooks.midtrans.handle'), $payload);
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['gross_amount']);
    }

    public function test_payment_creation_endpoint_is_rate_limited(): void
    {
        $gateway = new FakeMidtransGateway;
        $this->app->instance(MidtransGateway::class, $gateway);

        $user = User::factory()->create();
        $field = BadmintonField::query()->create([
            'name' => 'Arena Limit',
            'slug' => 'arena-limit',
            'price_per_hour' => 80000,
            'is_active' => true,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-2026-0001',
            'badminton_field_id' => $field->id,
            'user_id' => $user->id,
            'booking_date' => '2026-05-21',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => Booking::STATUS_PENDING,
            'price_per_hour' => 80000,
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->actingAs($user)
                ->postJson(route('payments.store', $booking))
                ->assertCreated();
        }

        $this->actingAs($user)
            ->postJson(route('payments.store', $booking))
            ->assertTooManyRequests();
    }
}
