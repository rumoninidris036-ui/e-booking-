<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\Notifications\WhatsAppNotificationGateway;
use App\Services\Notifications\FlowKirimWhatsAppService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FlowKirimWhatsAppServiceTest extends TestCase
{
    public function test_it_sends_text_message_to_flowkirim(): void
    {
        config([
            'services.flowkirim.base_url' => 'https://api.flowkirim.test',
            'services.flowkirim.token' => 'test-token',
            'services.flowkirim.session_id' => 'session-123',
        ]);

        Http::fake([
            'api.flowkirim.test/api/whatsapp/messages/text' => Http::response([
                'success' => true,
                'messageId' => 'msg-123',
            ]),
        ]);

        $response = app(FlowKirimWhatsAppService::class)
            ->sendTextMessage('0812-3456-7890', 'Halo dari test');

        $this->assertSame(['success' => true, 'messageId' => 'msg-123'], $response);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.flowkirim.test/api/whatsapp/messages/text'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $request['sessionId'] === 'session-123'
                && $request['to'] === '6281234567890@s.whatsapp.net'
                && $request['message'] === 'Halo dari test';
        });
    }

    public function test_it_sends_document_message_to_flowkirim(): void
    {
        config([
            'services.flowkirim.base_url' => 'https://api.flowkirim.test',
            'services.flowkirim.token' => 'test-token',
            'services.flowkirim.session_id' => 'session-123',
        ]);

        Http::fake([
            'api.flowkirim.test/api/whatsapp/messages/media' => Http::response([
                'success' => true,
                'messageId' => 'doc-123',
            ]),
        ]);

        $response = app(FlowKirimWhatsAppService::class)
            ->sendDocumentMessage(
                to: '0812-3456-7890',
                documentUrl: 'https://example.test/invoices/INV-2026-00001.pdf',
                caption: 'Invoice booking',
                filename: 'INV-2026-00001.pdf',
            );

        $this->assertSame(['success' => true, 'messageId' => 'doc-123'], $response);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.flowkirim.test/api/whatsapp/messages/media'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $request['session_id'] === 'session-123'
                && $request['to'] === '6281234567890'
                && $request['media_url'] === 'https://example.test/invoices/INV-2026-00001.pdf'
                && $request['type'] === 'document'
                && $request['caption'] === 'Invoice booking'
                && $request['filename'] === 'INV-2026-00001.pdf';
        });
    }

    public function test_it_binds_contract_to_flowkirim_service(): void
    {
        $this->assertInstanceOf(
            FlowKirimWhatsAppService::class,
            app(WhatsAppNotificationGateway::class),
        );
    }
}
