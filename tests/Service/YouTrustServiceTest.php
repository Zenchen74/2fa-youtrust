<?php

namespace App\Tests\Service;

use App\Dto\YouTrust\DocumentConfig;
use App\Dto\YouTrust\CustomTextConfig;
use App\Dto\YouTrust\EmailNotification;
use App\Dto\YouTrust\ReminderSettings;
use App\Dto\YouTrust\SenderConfig;
use App\Dto\YouTrust\SignatureRequestConfig;
use App\Dto\YouTrust\SignerConfig;
use App\Service\YouTrustService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Test cases for YouTrustService
 */
class YouTrustServiceTest extends TestCase
{
    private string $testApiKey = 'test_api_key_12345';
    private string $testBaseUrl = 'https://api-sandbox.yousign.app/v3';

    /**
     * Test that service is properly configured
     */
    public function testServiceConfiguration(): void
    {
        $httpClient = $this->createMockMockHttpClient();
        
        $service = new YouTrustService(
            $httpClient,
            $this->testApiKey,
            $this->testBaseUrl
        );

        $this->assertTrue($service->isConfigured());
        $this->assertEquals($this->testBaseUrl, $service->getApiBaseUrl());
        // test_api_key_12345 = 18 chars, so: test + (18-8=10) * + 2345 = test**********2345
        $this->assertEquals('test**********2345', $service->getApiKeyMasked());
    }

    /**
     * Test DTO toArray conversion
     */
    public function testSignatureRequestConfigToArray(): void
    {
        $reminderSettings = new ReminderSettings(
            intervalInDays: 2,
            maxOccurrences: 3
        );

        $customText = new CustomTextConfig(
            requestSubject: 'Test Subject',
            requestBody: 'Test Body',
            reminderSubject: 'Reminder Subject',
            reminderBody: 'Reminder Body'
        );

        $sender = new SenderConfig(
            type: 'custom',
            customName: 'Test Company'
        );

        $emailNotification = new EmailNotification($sender, $customText);

        $config = new SignatureRequestConfig(
            name: 'Test Request',
            deliveryMode: 'email',
            orderedSigners: true,
            smartAnchorsResolution: 'on_activation',
            timezone: 'Europe/Paris',
            externalId: 'EXT-123',
            auditTrailLocale: 'fr',
            signersAllowedToDecline: false,
            reminderSettings: $reminderSettings,
            emailNotification: $emailNotification
        );

        $array = $config->toArray();

        $this->assertEquals('Test Request', $array['name']);
        $this->assertEquals('email', $array['delivery_mode']);
        $this->assertTrue($array['ordered_signers']);
        $this->assertEquals('on_activation', $array['smart_anchors_resolution']);
        $this->assertEquals('Europe/Paris', $array['timezone']);
        $this->assertEquals('EXT-123', $array['external_id']);
        $this->assertEquals('fr', $array['audit_trail_locale']);
        $this->assertFalse($array['signers_allowed_to_decline']);
        $this->assertEquals(['interval_in_days' => 2, 'max_occurrences' => 3], $array['reminder_settings']);
        $this->assertArrayHasKey('email_notification', $array);
    }

    /**
     * Test SignerConfig toArray conversion
     */
    public function testSignerConfigToArray(): void
    {
        $signer = new SignerConfig(
            firstName: 'John',
            lastName: 'Doe',
            email: 'john@example.com',
            phoneNumber: '+33612345678',
            locale: 'fr',
            signatureLevel: 'electronic_signature',
            deliveryMode: 'email',
            signatureAuthenticationMode: 'otp_sms'
        );

        $array = $signer->toArray();

        $this->assertEquals('John', $array['info']['first_name']);
        $this->assertEquals('Doe', $array['info']['last_name']);
        $this->assertEquals('john@example.com', $array['info']['email']);
        $this->assertEquals('+33612345678', $array['info']['phone_number']);
        $this->assertEquals('fr', $array['info']['locale']);
        $this->assertEquals('electronic_signature', $array['signature_level']);
        $this->assertEquals('email', $array['delivery_mode']);
        $this->assertEquals('otp_sms', $array['signature_authentication_mode']);
    }

    /**
     * Test DocumentConfig getters
     */
    public function testDocumentConfigGetters(): void
    {
        $document = new DocumentConfig(
            filePath: '/path/to/document.pdf',
            nature: 'signable_document',
            parseAnchors: true
        );

        $this->assertEquals('/path/to/document.pdf', $document->getFilePath());
        $this->assertEquals('signable_document', $document->getNature());
        $this->assertEquals('true', $document->getParseAnchors());
    }

    /**
     * Test that createCompleteSignatureRequest requires exactly 3 signers
     */
    public function testCompleteWorkflowRequiresThreeSigners(): void
    {
        $httpClient = $this->createMockMockHttpClient();
        
        $service = new YouTrustService(
            $httpClient,
            $this->testApiKey,
            $this->testBaseUrl
        );

        $signatureRequestConfig = new SignatureRequestConfig();
        $documentConfig = new DocumentConfig('/path/to/file.pdf');
        
        // Test with less than 3 signers
        $fewerSigners = [
            new SignerConfig('John', 'Doe', 'john@example.com'),
            new SignerConfig('Jane', 'Smith', 'jane@example.com'),
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Exactly 3 signers must be provided for this workflow.');
        
        $service->createCompleteSignatureRequest(
            $signatureRequestConfig,
            $documentConfig,
            $fewerSigners
        );
    }

    /**
     * Test with exactly 3 signers (should not throw exception)
     */
    public function testCompleteWorkflowWithThreeSigners(): void
    {
        // This test would need a mock that can return proper responses
        // For now, we'll just verify the method exists and accepts the right parameters
        $httpClient = $this->createMockMockHttpClient();
        
        $service = new YouTrustService(
            $httpClient,
            $this->testApiKey,
            $this->testBaseUrl
        );

        $signatureRequestConfig = new SignatureRequestConfig();
        $documentConfig = new DocumentConfig('/path/to/file.pdf');
        
        $threeSigners = [
            new SignerConfig('John', 'Doe', 'john@example.com'),
            new SignerConfig('Jane', 'Smith', 'jane@example.com'),
            new SignerConfig('Bob', 'Wilson', 'bob@example.com'),
        ];

        // This will fail because we don't have proper mocks, but it tests the parameter validation
        try {
            $service->createCompleteSignatureRequest(
                $signatureRequestConfig,
                $documentConfig,
                $threeSigners
            );
        } catch (\Exception $e) {
            // Expected to fail due to mock responses, but not due to parameter validation
            $this->assertStringNotContainsString('Exactly 3 signers must be provided', $e->getMessage());
        }
    }

    /**
     * Create a mock HTTP client that returns mock responses
     */
    private function createMockMockHttpClient()
    {
        // This is a simplified mock - in a real test, you'd set up proper response expectations
        return new MockHttpClient(function ($method, $url, $options) {
            return new MockResponse(json_encode(['id' => 'test_id']), ['http_code' => 200]);
        });
    }
}
