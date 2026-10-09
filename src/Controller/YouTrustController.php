<?php

namespace App\Controller;

use App\Dto\YouTrust\CustomTextConfig;
use App\Dto\YouTrust\DocumentConfig;
use App\Dto\YouTrust\EmailNotification;
use App\Dto\YouTrust\ReminderSettings;
use App\Dto\YouTrust\SenderConfig;
use App\Dto\YouTrust\SignatureRequestConfig;
use App\Dto\YouTrust\SignerConfig;
use App\Service\YouTrustService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Controller for YouTrust API integration with web MVC interface.
 *
 * This controller provides web-based forms for testing the YouTrustService.
 */
class YouTrustController extends AbstractController
{
    public function __construct(
        private YouTrustService $youTrustService,
    ) {}

    // ========================================================================
    // WEB INTERFACE ROUTES
    // ========================================================================

    /**
     * YouTrust Dashboard - Main page with service status and quick actions
     */
    #[Route('/youtrust', name: 'youtrust_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $isConfigured = $this->youTrustService->isConfigured();

        return $this->render('youtrust/dashboard.html.twig', [
            'is_configured' => $isConfigured,
            'api_base_url' => $this->youTrustService->getApiBaseUrl(),
            'api_key_masked' => $this->youTrustService->getApiKeyMasked(),
        ]);
    }

    /**
     * Health check page
     */
    #[Route('/youtrust/health', name: 'youtrust_health', methods: ['GET'])]
    public function healthCheckPage(): Response
    {
        $isConfigured = $this->youTrustService->isConfigured();

        return $this->render('youtrust/health.html.twig', [
            'is_configured' => $isConfigured,
            'api_base_url' => $this->youTrustService->getApiBaseUrl(),
            'api_key_masked' => $this->youTrustService->getApiKeyMasked(),
        ]);
    }

    /**
     * List all signature requests
     */
    #[Route('/youtrust/signature-requests', name: 'youtrust_list_requests_page', methods: ['GET'])]
    public function listSignatureRequestsPage(Request $request): Response
    {
        try {
            $signatureRequests = $this->youTrustService->listSignatureRequests();

            return $this->render('youtrust/list_requests.html.twig', [
                'signature_requests' => $signatureRequests,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la récupération des demandes de signature: ' . $e->getMessage());
            return $this->redirectToRoute('youtrust_dashboard');
        }
    }

    /**
     * View details of a specific signature request
     */
    #[Route('/youtrust/signature-requests/{id}', name: 'youtrust_view_request', methods: ['GET'])]
    public function viewSignatureRequest(string $id): Response
    {
        try {
            $signatureRequest = $this->youTrustService->getSignatureRequest($id);
            $documents = $this->youTrustService->listDocuments($id);
            $signers = $this->youTrustService->listSigners($id);

            return $this->render('youtrust/view_request.html.twig', [
                'signature_request' => $signatureRequest,
                'documents' => $documents,
                'signers' => $signers,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la récupération de la demande de signature: ' . $e->getMessage());
            return $this->redirectToRoute('youtrust_list_requests_page');
        }
    }


    /**
     * Form for creating a complete signature request (1 document, 3 signers)
     */
    #[Route('/youtrust/signature-requests/complete/new', name: 'youtrust_complete_request_new', methods: ['GET'])]
    public function newCompleteSignatureRequest(): Response
    {
        // Check if test document exists
        $testDocumentPath = $this->getParameter('kernel.project_dir') . '/public/uploads/test-document.pdf';
        $testDocumentExists = file_exists($testDocumentPath);

        return $this->render('youtrust/form_complete.html.twig', [
            'test_document_exists' => $testDocumentExists,
            'test_document_url' => $testDocumentExists ? '/uploads/test-document.pdf' : null,
        ]);
    }

    /**
     * Process form submission for complete signature request
     */
    #[Route('/youtrust/signature-requests/complete', name: 'youtrust_complete_request_create', methods: ['POST'])]
    public function createCompleteSignatureRequest(Request $request): Response
    {
        try {
            // Get form data
            $filePath = $request->request->get('file_path');
            $useTestDocument = $request->request->getBoolean('use_test_document');

            // Handle file upload
            $uploadedFile = $request->files->get('document');

            if ($uploadedFile instanceof UploadedFile) {
                // Save uploaded file to uploads directory
                $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/';

                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }

                $newFilename = 'uploaded-' . uniqid() . '.' . $uploadedFile->guessExtension();
                $uploadedFile->move($uploadsDir, $newFilename);
                $filePath = $uploadsDir . $newFilename;
            } elseif ($useTestDocument) {
                $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/test-document.pdf';
            }

            // Validate file exists
            if (!file_exists($filePath)) {
                $this->addFlash('error', 'Le fichier document n\'existe pas');
                return $this->redirectToRoute('youtrust_complete_request_new');
            }

            // Create configuration objects
            $reminderSettings = new ReminderSettings(
                (int)$request->request->get('reminder_interval_days', 1),
                (int)$request->request->get('reminder_max_occurrences', 5)
            );

            $customText = new CustomTextConfig(
                $request->request->get('request_subject'),
                $request->request->get('request_body'),
                $request->request->get('reminder_subject'),
                $request->request->get('reminder_body')
            );

            $sender = new SenderConfig(
                $request->request->get('sender_type', 'custom'),
                $request->request->get('sender_custom_name')
            );

            $emailNotification = new EmailNotification($sender, $customText);

            $signatureRequestConfig = new SignatureRequestConfig(
                $request->request->get('name', 'Convention de stage'),
                $request->request->get('delivery_mode', 'email'),
                $request->request->getBoolean('ordered_signers', true),
                $request->request->get('smart_anchors_resolution', 'on_activation'),
                $request->request->get('timezone', 'Europe/Paris'),
                null,
                $request->request->get('audit_trail_locale', 'fr'),
                $request->request->getBoolean('signers_allowed_to_decline', false),
                $reminderSettings,
                $emailNotification
            );

            $documentConfig = new DocumentConfig(
                $filePath,
                $request->request->get('document_nature', 'signable_document'),
                $request->request->getBoolean('parse_anchors', true)
            );

            // Create 3 signer configurations from form data
            $signerConfigs = [];
            for ($i = 1; $i <= 3; $i++) {
                $firstName = $request->request->get('signer_' . $i . '_first_name');
                $lastName = $request->request->get('signer_' . $i . '_last_name');
                $email = $request->request->get('signer_' . $i . '_email');
                $phoneNumber = $request->request->get('signer_' . $i . '_phone_number');

                if (empty($firstName) || empty($lastName) || empty($email)) {
                    $this->addFlash('error', 'Tous les champs des signataires sont obligatoires (prénom, nom, email)');
                    return $this->redirectToRoute('youtrust_complete_request_new');
                }

                $signerConfig = new SignerConfig(
                    $firstName,
                    $lastName,
                    $email,
                    $phoneNumber ?: null,
                    $request->request->get('signer_' . $i . '_locale', 'fr'),
                    $request->request->get('signer_' . $i . '_signature_level', 'electronic_signature'),
                    $request->request->get('signer_' . $i . '_delivery_mode', 'email'),
                    $request->request->get('signer_' . $i . '_authentication_mode', 'otp_sms')
                );
                $signerConfigs[] = $signerConfig;
            }

            // Execute the complete workflow
            $result = $this->youTrustService->createCompleteSignatureRequest(
                $signatureRequestConfig,
                $documentConfig,
                $signerConfigs
            );

            $this->addFlash('success', 'Demande de signature complète créée avec succès! ID: ' . $result['signature_request_id']);

            return $this->redirectToRoute('youtrust_view_request', [
                'id' => $result['signature_request_id']
            ]);

        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', 'Erreur de validation: ' . $e->getMessage());
            return $this->redirectToRoute('youtrust_complete_request_new');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la création de la demande: ' . $e->getMessage());
            return $this->redirectToRoute('youtrust_complete_request_new');
        }
    }

    /**
     * Cancel a signature request
     */
    #[Route('/youtrust/signature-requests/{id}/cancel', name: 'youtrust_cancel_request', methods: ['POST'])]
    public function cancelSignatureRequestPage(string $id, Request $request): Response
    {
        try {
            // CSRF protection
            if (!$this->isCsrfTokenValid('cancel-' . $id, $request->request->get('_token'))) {
                $this->addFlash('error', 'Token CSRF invalide');
                return $this->redirectToRoute('youtrust_view_request', ['id' => $id]);
            }

            $result = $this->youTrustService->cancelSignatureRequest($id);
            $this->addFlash('success', 'Demande de signature annulée avec succès');

            return $this->redirectToRoute('youtrust_view_request', ['id' => $id]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'annulation: ' . $e->getMessage());
            return $this->redirectToRoute('youtrust_view_request', ['id' => $id]);
        }
    }

}
