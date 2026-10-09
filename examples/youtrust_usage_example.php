<?php

/**
 * Example of how to use the YouTrustService in a Symfony application.
 * 
 * This file demonstrates the complete workflow for creating a signature request
 * with a document and 3 signers, then activating it.
 * 
 * Note: This is a standalone example for reference. In a real Symfony application,
 * you would use dependency injection to get the YouTrustService instance.
 */

// In a Symfony controller, you would do:

use App\Service\YouTrustService;
use App\Dto\YouTrust\SignatureRequestConfig;
use App\Dto\YouTrust\ReminderSettings;
use App\Dto\YouTrust\EmailNotification;
use App\Dto\YouTrust\SenderConfig;
use App\Dto\YouTrust\CustomTextConfig;
use App\Dto\YouTrust\DocumentConfig;
use App\Dto\YouTrust\SignerConfig;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Example 1: Using the complete workflow method
 */
class SignatureController extends AbstractController
{
    public function createStageConvention(
        string $studentName,
        string $companyName,
        string $documentPath,
        array $signersData,
        YouTrustService $youTrustService
    ): Response {
        // 1. Create signature request configuration
        $reminderSettings = new ReminderSettings(
            intervalInDays: 2,  // Send reminder every 2 days
            maxOccurrences: 3   // Maximum 3 reminders
        );

        $customText = new CustomTextConfig(
            requestSubject: "Convention de stage de {$studentName} à signer",
            requestBody: "Merci d'accueillir {$studentName} pour son stage au sein de {$companyName}. " .
                         "Veuillez signer cette convention de stage.",
            reminderSubject: "[Rappel] Convention de stage de {$studentName} à signer",
            reminderBody: "Vous n'avez pas encore signé la convention de stage de {$studentName}. " .
                          "Merci de le faire dès que possible."
        );

        $sender = new SenderConfig(
            type: 'custom',
            customName: 'Lycée Gabriel Fauré'
        );

        $emailNotification = new EmailNotification(
            sender: $sender,
            customText: $customText
        );

        $signatureRequestConfig = new SignatureRequestConfig(
            name: "Convention de stage - {$studentName}",
            deliveryMode: 'email',
            orderedSigners: true,  // Signers must sign in order
            smartAnchorsResolution: 'on_activation',
            timezone: 'Europe/Paris',
            externalId: 'CONV-' . date('YmdHis'),  // Unique external ID
            auditTrailLocale: 'fr',
            signersAllowedToDecline: false,
            reminderSettings: $reminderSettings,
            emailNotification: $emailNotification
        );

        // 2. Create document configuration
        $documentConfig = new DocumentConfig(
            filePath: $documentPath,  // e.g., '/var/www/uploads/convention.pdf'
            nature: 'signable_document',
            parseAnchors: true
        );

        // 3. Create signer configurations (must be exactly 3)
        $signerConfigs = [];
        
        foreach ($signersData as $signerInfo) {
            $signerConfig = new SignerConfig(
                firstName: $signerInfo['first_name'],
                lastName: $signerInfo['last_name'],
                email: $signerInfo['email'],
                phoneNumber: $signerInfo['phone'] ?? null,
                locale: 'fr',
                signatureLevel: 'electronic_signature',
                deliveryMode: 'email',
                signatureAuthenticationMode: 'otp_sms'
            );
            $signerConfigs[] = $signerConfig;
        }

        // 4. Execute the complete workflow
        try {
            $result = $youTrustService->createCompleteSignatureRequest(
                $signatureRequestConfig,
                $documentConfig,
                $signerConfigs
            );

            $signatureRequestId = $result['signature_request_id'];
            
            // Save to database, log, etc.
            $this->addFlash('success', 'Convention de stage créée avec succès !');
            
            return $this->redirectToRoute('convention_show', [
                'id' => $signatureRequestId
            ]);

        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', 'Erreur de validation : ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la création : ' . $e->getMessage());
        }

        return $this->redirectToRoute('convention_new');
    }
}

/**
 * Example 2: Manual workflow step by step
 */
class ManualSignatureController extends AbstractController
{
    public function createManualSignature(
        YouTrustService $youTrustService
    ): Response {
        // Step 1: Create signature request
        $signatureRequestConfig = new SignatureRequestConfig(
            name: 'Manual Test',
            externalId: 'MANUAL-' . uniqid()
        );

        $signatureRequest = $youTrustService->initiateSignatureRequest($signatureRequestConfig);
        $signatureRequestId = $signatureRequest['id'];

        // Step 2: Upload document
        $documentConfig = new DocumentConfig(
            filePath: '/path/to/document.pdf'
        );

        $document = $youTrustService->uploadDocument($signatureRequestId, $documentConfig);

        // Step 3: Add signers
        $signer1 = new SignerConfig(
            firstName: 'John',
            lastName: 'Doe',
            email: 'john@example.com',
            phoneNumber: '+33612345678'
        );

        $signer2 = new SignerConfig(
            firstName: 'Jane',
            lastName: 'Smith',
            email: 'jane@example.com',
            phoneNumber: '+33687654321'
        );

        $signer3 = new SignerConfig(
            firstName: 'Bob',
            lastName: 'Wilson',
            email: 'bob@example.com',
            phoneNumber: '+33611223344'
        );

        $youTrustService->createSigner($signatureRequestId, $signer1);
        $youTrustService->createSigner($signatureRequestId, $signer2);
        $youTrustService->createSigner($signatureRequestId, $signer3);

        // Step 4: Activate
        $activated = $youTrustService->activateSignatureRequest($signatureRequestId);

        return $this->json([
            'success' => true,
            'signature_request_id' => $signatureRequestId,
            'document' => $document,
            'activated' => $activated
        ]);
    }
}

/**
 * Example 3: Using the API endpoints from the controller
 */
class ApiConsumerExample
{
    public function exampleUsage(YouTrustService $youTrustService)
    {
        // List all signature requests
        $allRequests = $youTrustService->listSignatureRequests();
        
        // Get a specific request
        $request = $youTrustService->getSignatureRequest('request_id_here');
        
        // List documents for a request
        $documents = $youTrustService->listDocuments('request_id_here');
        
        // List signers for a request
        $signers = $youTrustService->listSigners('request_id_here');
        
        // Delete a document
        $youTrustService->deleteDocument('request_id_here', 'document_id_here');
        
        // Delete a signer
        $youTrustService->deleteSigner('request_id_here', 'signer_id_here');
        
        // Cancel a signature request
        $youTrustService->cancelSignatureRequest('request_id_here');
    }
}

/**
 * Example 4: Checking service configuration
 */
class HealthCheckController extends AbstractController
{
    public function checkYouTrustConfig(YouTrustService $youTrustService): Response
    {
        $isConfigured = $youTrustService->isConfigured();
        $apiBaseUrl = $youTrustService->getApiBaseUrl();
        $apiKeyMasked = $youTrustService->getApiKeyMasked();

        return $this->json([
            'configured' => $isConfigured,
            'base_url' => $apiBaseUrl,
            'api_key' => $apiKeyMasked
        ]);
    }
}
