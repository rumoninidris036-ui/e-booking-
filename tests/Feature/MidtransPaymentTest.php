<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\Payments\MidtransGateway;
use App\Models\BadmintonField;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
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
            ->assertJsonPath('data.snap_token', 'snap-token-test')
            ->assertJsonPath('data.status', Payment::STATUS_PENDING)
            ->assertJsonPath('meta.midtrans.client_key', 'Mid-client-eTIvWK6JoSR9nFFU')
            ->assertJsonMissingPath('data.snap_response')
            ->assertJsonMissingPath('data.notification_payload');

        $this->assertMatchesRegularExpression(
            '/^BK-2026-0001-PAY-01-[A-Z0-9]{6}$/',
            $response->json('data.order_id'),
        );
    }

    public function test_payment_creation_rejects_expired_pending_booking(): void
    {
        $gateway = new FakeMidtransGateway;
        $this->app->instance(MidtransGateway::class, $gateway);

        $user = User::factory()->create();
        $field = BadmintonField::query()->create([
            'name' => 'Arena Expired Payment',
            'slug' => 'arena-expired-payment',
            'price_per_hour' => 80000,
            'is_active' => true,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-2026-0001',
            'badminton_field_id' => $field->id,
            'user_id' => $user->id,
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => Booking::STATUS_PENDING,
            'expires_at' => now()->subMinute(),
            'price_per_hour' => 80000,
        ]);

        $response = $this->actingAs($user)->postJson(route('payments.store', $booking));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['booking']);

        $booking->refresh();

        $this->assertSame(Booking::STATUS_CANCELLED, $booking->status);
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
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '08:00',
        ]);

        $payment = Payment::query()->with('booking')->firstOrFail();

        $response->assertRedirect(route('payments.show', [
            'payment' => $payment,
            'access_token' => $payment->booking->guest_access_token,
        ]));

        $this->actingAs($user)
            ->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee('Continue To Pay')
            ->assertSee('BK-2026-0001');
    }

    public function test_guest_can_book_and_open_payment_page_with_access_token(): void
    {
        $gateway = new FakeMidtransGateway;
        $gateway->statusResponse = [
            'order_id' => 'BK-2026-0001',
            'status_code' => '201',
            'gross_amount' => '90000.00',
            'transaction_status' => 'pending',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'trx-guest-pending-page',
            'fraud_status' => 'accept',
        ];
        $this->app->instance(MidtransGateway::class, $gateway);

        $field = BadmintonField::query()->create([
            'name' => 'Arena Guest',
            'slug' => 'arena-guest',
            'price_per_hour' => 90000,
            'is_active' => true,
        ]);

        $response = $this->post(route('public.fields.bookings.store', [
            'slug' => $field->slug,
        ]), [
            'customer_name' => 'Guest Customer',
            'customer_contact' => '081234567890',
            'customer_email' => 'guest@example.test',
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '08:00',
        ]);

        $booking = Booking::query()->firstOrFail();
        $payment = Payment::query()->firstOrFail();

        $this->assertNull($booking->user_id);
        $this->assertSame('Guest Customer', $booking->customer_name);
        $this->assertSame('081234567890', $booking->customer_contact);
        $this->assertNotNull($booking->guest_access_token);

        $response->assertRedirect(route('payments.show', [
            'payment' => $payment,
            'access_token' => $booking->guest_access_token,
        ]));

        $this->get(route('payments.show', $payment))->assertForbidden();

        $this->get(route('payments.show', [
            'payment' => $payment,
            'access_token' => $booking->guest_access_token,
        ]))
            ->assertOk()
            ->assertSee('Guest Customer')
            ->assertSee('Continue To Pay');
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

        $payment->refresh();
        $this->assertNotNull($payment->invoice_number);
        $this->assertNotNull($payment->invoice_pdf_path);
        Storage::disk('local')->assertExists($payment->invoice_pdf_path);
    }

    public function test_successful_guest_payment_sends_booking_pdf_to_customer_whatsapp(): void
    {
        config([
            'services.flowkirim.base_url' => 'https://api.flowkirim.test',
            'services.flowkirim.token' => 'test-token',
            'services.flowkirim.session_id' => 'session-123',
        ]);

        Http::fake([
            'api.flowkirim.test/api/whatsapp/messages/media' => Http::response([
                'success' => true,
                'messageId' => 'wa-doc-123',
            ]),
        ]);

        $gateway = new FakeMidtransGateway;
        $gateway->statusResponse = [
            'order_id' => 'BK-2026-0001',
            'status_code' => '200',
            'gross_amount' => '90000.00',
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'trx-wa-success',
            'fraud_status' => 'accept',
        ];
        $this->app->instance(MidtransGateway::class, $gateway);

        $field = BadmintonField::query()->create([
            'name' => 'Arena WhatsApp',
            'slug' => 'arena-whatsapp',
            'price_per_hour' => 90000,
            'is_active' => true,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-2026-0001',
            'badminton_field_id' => $field->id,
            'customer_name' => 'Guest Customer',
            'customer_contact' => '0812-3456-7890',
            'customer_email' => 'guest@example.test',
            'guest_access_token' => 'guest-token-123',
            'booking_date' => '2026-05-21',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => Booking::STATUS_PENDING,
            'price_per_hour' => 90000,
        ]);

        $payment = Payment::query()->create([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'order_id' => 'BK-2026-0001',
            'amount' => 90000,
            'currency' => 'IDR',
            'status' => Payment::STATUS_PENDING,
            'snap_redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/test-token',
        ]);

        $this->get(route('payments.show', [
            'payment' => $payment,
            'access_token' => $booking->guest_access_token,
        ]))->assertOk();

        $payment->refresh();

        $this->assertNotNull($payment->whatsapp_notified_at);
        $this->assertSame(['success' => true, 'messageId' => 'wa-doc-123'], $payment->whatsapp_notification_response);

        Http::assertSent(function (Request $request) use ($payment): bool {
            return $request->url() === 'https://api.flowkirim.test/api/whatsapp/messages/media'
                && $request['session_id'] === 'session-123'
                && $request['to'] === '6281234567890'
                && $request['type'] === 'document'
                && str_contains((string) $request['media_url'], route('payments.invoice.download', [
                    'payment' => $payment,
                    'access_token' => 'guest-token-123',
                ]))
                && str_contains((string) $request['caption'], 'Kode booking: BK-2026-0001')
                && str_contains((string) $request['caption'], 'PDF booking/invoice terlampir')
                && str_contains((string) $request['caption'], URL::signedRoute('public.rating.create', [
                    'booking' => $payment->booking_id,
                ]));
        });
    }

    public function test_successful_payment_invoice_can_be_downloaded_as_pdf(): void
    {
        $gateway = new FakeMidtransGateway;
        $gateway->statusResponse = [
            'order_id' => 'BK-2026-0001',
            'status_code' => '200',
            'gross_amount' => '80000.00',
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'trx-invoice-success',
            'fraud_status' => 'accept',
        ];
        $this->app->instance(MidtransGateway::class, $gateway);

        $user = User::factory()->create();
        $field = BadmintonField::query()->create([
            'name' => 'Arena Invoice',
            'slug' => 'arena-invoice',
            'price_per_hour' => 80000,
            'is_active' => true,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-2026-0001',
            'badminton_field_id' => $field->id,
            'user_id' => $user->id,
            'guest_access_token' => 'guest-token-456',
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
            ->getJson(route('payments.show', $payment))
            ->assertOk()
            ->assertJsonPath('data.status', Payment::STATUS_SUCCESS)
            ->assertJsonPath('data.invoice_number', 'INV-2026-00001');

        $payment->refresh();

        $response = $this->get(route('payments.invoice.download', [
            'payment' => $payment,
            'access_token' => $booking->guest_access_token,
        ]));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertStringContainsString(
            'attachment',
            (string) $response->headers->get('content-disposition'),
        );
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

    public function test_midtrans_return_route_can_apply_signed_success_fallback_in_production(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->app->instance(MidtransGateway::class, new class implements MidtransGateway
        {
            public function createSnapTransaction(array $payload): array
            {
                return [];
            }

            public function getTransactionStatus(string $orderId): array
            {
                throw new \RuntimeException('Midtrans status API unavailable.');
            }
        });

        $user = User::factory()->create();
        $field = BadmintonField::query()->create([
            'name' => 'Arena Signed Return',
            'slug' => 'arena-signed-return',
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

        $payload = [
            'order_id' => 'BK-2026-0001',
            'status_code' => '200',
            'gross_amount' => '80000.00',
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
        ];
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].config('services.midtrans.server_key'));

        $this->actingAs($user)
            ->get(route('payments.return', ['payment' => $payment, ...$payload]))
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

    public function test_midtrans_return_route_uses_signed_payload_when_status_api_is_still_pending(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $gateway = new FakeMidtransGateway;
        $gateway->statusResponse = [
            'order_id' => 'BK-2026-0001',
            'status_code' => '201',
            'gross_amount' => '80000.00',
            'transaction_status' => 'pending',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'trx-still-pending',
            'fraud_status' => 'accept',
        ];
        $this->app->instance(MidtransGateway::class, $gateway);

        $user = User::factory()->create();
        $field = BadmintonField::query()->create([
            'name' => 'Arena Pending Api',
            'slug' => 'arena-pending-api',
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

        $payload = [
            'order_id' => 'BK-2026-0001',
            'status_code' => '200',
            'gross_amount' => '80000.00',
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
        ];
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].config('services.midtrans.server_key'));

        $this->actingAs($user)
            ->get(route('payments.return', ['payment' => $payment, ...$payload]))
            ->assertOk()
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

    public function test_midtrans_return_route_allows_unsigned_finish_fallback_in_sandbox(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
        config(['services.midtrans.is_production' => false]);

        $this->app->instance(MidtransGateway::class, new class implements MidtransGateway
        {
            public function createSnapTransaction(array $payload): array
            {
                return [];
            }

            public function getTransactionStatus(string $orderId): array
            {
                throw new \RuntimeException("Transaction doesn't exist.");
            }
        });

        $user = User::factory()->create();
        $field = BadmintonField::query()->create([
            'name' => 'Arena Sandbox Return',
            'slug' => 'arena-sandbox-return',
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
            ->get(route('payments.return', [
                'payment' => $payment,
                'order_id' => 'BK-2026-0001',
                'callback_state' => 'finish',
            ]))
            ->assertOk()
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
            ->assertJsonPath('data.status', Payment::STATUS_PENDING);

        $orderId = $response->json('data.order_id');

        $this->assertMatchesRegularExpression('/^BK-2026-0001-PAY-02-[A-Z0-9]{6}$/', $orderId);

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'order_id' => $orderId,
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
