<?php

namespace App\Service;

use App\Dto\YouTrust\DocumentConfig;
use App\Dto\YouTrust\SignatureRequestConfig;
use App\Dto\YouTrust\SignerConfig;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service for interacting with the YouTrust (ex-YouSign) API.
 *
 * This service provides methods to:
 * - Create and manage Signature Requests
 * - Upload and manage Documents
 * - Add and manage Signers
 * - Activate signature workflows
 */
class YouTrustService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $youTrustApiKey,
        private string $youTrustBaseUrl = 'https://api-sandbox.yousign.app/v3',
    ) {}

    // ========================================================================
    // SIGNATURE REQUESTS
    // ========================================================================

    /**
     * 1. Initiate a new Signature Request
     *
     * POST: https://api-sandbox.yousign.app/v3/signature_requests
     *
     * @param SignatureRequestConfig $config Configuration for the signature request
     * @return array The created signature request data
     * @throws TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface
     */
    public function initiateSignatureRequest(SignatureRequestConfig $config): array
    {
        $response = $this->request('POST', '/signature_requests', [
            'json' => $config->toArray(),
        ]);

        return $response->toArray();
    }

    /**
     * 2. List all Signature Requests
     *
     * GET: https://api-sandbox.yousign.app/v3/signature_requests
     *
     * @param array $query Query parameters for filtering/pagination
     * @return array List of signature requests
     * @throws TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface
     */
    public function listSignatureRequests(array $query = []): array
    {
        $options = [];
        if (!empty($query)) {
            $options['query'] = $query;
        }

        $response = $this->request('GET', '/signature_requests', $options);
        return $response->toArray();
    }

    /**
     * 3. Cancel a Signature Request
     *
     * POST: https://api-sandbox.yousign.app/v3/signature_requests/{signatureRequestId}/cancel
     *
     * @param string $signatureRequestId ID of the signature request to cancel
     * @return array The cancelled signature request data
     * @throws TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface
     */
    public function cancelSignatureRequest(string $signatureRequestId): array
    {
        $response = $this->request('POST', "/signature_requests/{$signatureRequestId}/cancel");
        return $response->toArray();
    }

    /**
     * Get a specific Signature Request
     *
     * GET: https://api-sandbox.yousign.app/v3/signature_requests/{signatureRequestId}
     *
     * @param string $signatureRequestId ID of the signature request
     * @return array The signature request data
     * @throws TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface
     */
    public function getSignatureRequest(string $signatureRequestId): array
    {
        $response = $this->request('GET', "/signature_requests/{$signatureRequestId}");
        return $response->toArray();
    }

    // ========================================================================
    // DOCUMENTS
    // ========================================================================

    /**
     * 4. Upload Document to a Signature Request
     *
     * POST: https://api-sandbox.yousign.app/v3/signature_requests/{signatureRequestId}/documents
     * multipart/form-data
     *
     * @param string $signatureRequestId ID of the signature request
     * @param DocumentConfig $documentConfig Configuration for the document
     * @return array The uploaded document data
     * @throws TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface
     */
    public function uploadDocument(string $signatureRequestId, DocumentConfig $documentConfig): array
    {
        $form = new FormDataPart([
            'file' => DataPart::fromPath($documentConfig->getFilePath()),
            'nature' => $documentConfig->getNature(),
            'parse_anchors' => $documentConfig->getParseAnchors(),
        ]);

        $response = $this->request('POST', "/signature_requests/{$signatureRequestId}/documents", [
            'headers' => $form->getPreparedHeaders()->toArray(),
            'body' => $form->bodyToIterable(),
        ]);

        return $response->toArray();
    }

    /**
     * 5. List Signature Request's Documents
     *
     * GET: https://api-sandbox.yousign.app/v3/signature_requests/{signatureRequestId}/documents
     *
     * @param string $signatureRequestId ID of the signature request
     * @return array List of documents
     * @throws TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface
     */
    public function listDocuments(string $signatureRequestId): array
    {
        $response = $this->request('GET', "/signature_requests/{$signatureRequestId}/documents");
        return $response->toArray();
    }

    /**
     * 6. Delete a Document
     *
     * DELETE: https://api-sandbox.yousign.app/v3/signature_requests/{signatureRequestId}/documents/{documentId}
     *
     * @param string $signatureRequestId ID of the signature request
     * @param string $documentId ID of the document to delete
     * @return array Empty response on success
     * @throws TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface
     */
    public function deleteDocument(string $signatureRequestId, string $documentId): array
    {
        $response = $this->request('DELETE', "/signature_requests/{$signatureRequestId}/documents/{$documentId}");
        return $response->toArray();
    }

    // ========================================================================
    // SIGNERS
    // ========================================================================

    /**
     * 7. Create a new Signer
     *
     * POST: https://api-sandbox.yousign.app/v3/signature_requests/{signatureRequestId}/signers
     *
     * @param string $signatureRequestId ID of the signature request
     * @param SignerConfig $signerConfig Configuration for the signer
     * @return array The created signer data
     * @throws TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface
     */
    public function createSigner(string $signatureRequestId, SignerConfig $signerConfig): array
    {
        $response = $this->request('POST', "/signature_requests/{$signatureRequestId}/signers", [
            'json' => $signerConfig->toArray(),
        ]);

        return $response->toArray();
    }

    /**
     * 8. List Signature Request's Signers
     *
     * GET: https://api-sandbox.yousign.app/v3/signature_requests/{signatureRequestId}/signers
     *
     * @param string $signatureRequestId ID of the signature request
     * @return array List of signers
     * @throws TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface
     */
    public function listSigners(string $signatureRequestId): array
    {
        $response = $this->request('GET', "/signature_requests/{$signatureRequestId}/signers");
        return $response->toArray();
    }

    /**
     * 9. Delete a signer from a request
     *
     * DELETE: https://api-sandbox.yousign.app/v3/signature_requests/{signatureRequestId}/signers/{signerId}
     *
     * @param string $signatureRequestId ID of the signature request
     * @param string $signerId ID of the signer to delete
     * @return array Empty response on success
     * @throws TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface
     */
    public function deleteSigner(string $signatureRequestId, string $signerId): array
    {
        $response = $this->request('DELETE', "/signature_requests/{$signatureRequestId}/signers/{$signerId}");
        return $response->toArray();
    }

    // ========================================================================
    // ACTIVATION
    // ========================================================================

    /**
     * 10. Activate a Signature Request
     *
     * POST: https://api-sandbox.yousign.app/v3/signature_requests/{signatureRequestId}/activate
     *
     * @param string $signatureRequestId ID of the signature request to activate
     * @return array The activated signature request data
     * @throws TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface
     */
    public function activateSignatureRequest(string $signatureRequestId): array
    {
        $response = $this->request('POST', "/signature_requests/{$signatureRequestId}/activate");
        return $response->toArray();
    }

    // ========================================================================
    // MAIN WORKFLOW METHOD
    // ========================================================================

    /**
     * Create and activate a complete signature request with document and multiple signers.
     *
     * This method performs the following steps:
     * 1. Initiate a new Signature Request
     * 2. Upload Document to the Signature Request
     * 3. Create 3 Signers (one after another)
     * 4. Activate the Signature Request
     *
     * @param SignatureRequestConfig $signatureRequestConfig Configuration for the signature request
     * @param DocumentConfig $documentConfig Configuration for the document to upload
     * @param SignerConfig[] $signerConfigs Array of exactly 3 SignerConfig objects
     * @return array Result containing signature request ID and all created resources
     * @throws \InvalidArgumentException If not exactly 3 signers are provided
     * @throws TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface
     */
    public function createCompleteSignatureRequest(
        SignatureRequestConfig $signatureRequestConfig,
        DocumentConfig $documentConfig,
        array $signerConfigs
    ): array {
        // Validate that we have exactly 3 signers
        if (count($signerConfigs) !== 3) {
            throw new \InvalidArgumentException('Exactly 3 signers must be provided for this workflow.');
        }

        $result = [];

        // Step 1: Initiate Signature Request
        $signatureRequest = $this->initiateSignatureRequest($signatureRequestConfig);
        $signatureRequestId = $signatureRequest['id'];
        $result['signature_request'] = $signatureRequest;

        // Step 2: Upload Document
        $document = $this->uploadDocument($signatureRequestId, $documentConfig);
        $result['document'] = $document;

        // Step 3: Create 3 Signers
        $result['signers'] = [];
        foreach ($signerConfigs as $signerConfig) {
            $signer = $this->createSigner($signatureRequestId, $signerConfig);
            $result['signers'][] = $signer;
        }

        // Step 4: Activate Signature Request
        $activatedRequest = $this->activateSignatureRequest($signatureRequestId);
        $result['activated_request'] = $activatedRequest;

        $result['signature_request_id'] = $signatureRequestId;

        return $result;
    }

    /**
     * Create and activate a simple signature request with one document and one signer.
     *
     * This is a simplified version for testing purposes that doesn't require exactly 3 signers.
     *
     * @param SignatureRequestConfig $signatureRequestConfig Configuration for the signature request
     * @param DocumentConfig $documentConfig Configuration for the document to upload
     * @param SignerConfig $signerConfig Signer configuration
     * @return array Result containing signature request ID and all created resources
     * @throws TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface
     */
    public function createSimpleSignatureRequest(
        SignatureRequestConfig $signatureRequestConfig,
        DocumentConfig $documentConfig,
        SignerConfig $signerConfig
    ): array {
        $result = [];

        // Step 1: Initiate Signature Request
        $signatureRequest = $this->initiateSignatureRequest($signatureRequestConfig);
        $signatureRequestId = $signatureRequest['id'];
        $result['signature_request'] = $signatureRequest;

        // Step 2: Upload Document
        $document = $this->uploadDocument($signatureRequestId, $documentConfig);
        $result['document'] = $document;

        // Step 3: Create Signer
        $signer = $this->createSigner($signatureRequestId, $signerConfig);
        $result['signer'] = $signer;

        // Step 4: Activate Signature Request
        $activatedRequest = $this->activateSignatureRequest($signatureRequestId);
        $result['activated_request'] = $activatedRequest;

        $result['signature_request_id'] = $signatureRequestId;

        return $result;
    }

    // ========================================================================
    // UTILITY METHODS
    // ========================================================================

    /**
     * Make a request to the YouTrust API
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
     * @param string $path API endpoint path (without base URL)
     * @param array $options Additional options for the request
     * @return \Symfony\Contracts\HttpClient\ResponseInterface
     * @throws TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface
     */
    private function request(string $method, string $path, array $options = []): \Symfony\Contracts\HttpClient\ResponseInterface
    {
        $url = $this->youTrustBaseUrl . $path;

        $defaultOptions = [
            'auth_bearer' => $this->youTrustApiKey,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ];

        $mergedOptions = array_merge_recursive($defaultOptions, $options);

        // Remove Content-Type header for multipart requests
        if (isset($mergedOptions['body']) && !isset($mergedOptions['json'])) {
            unset($mergedOptions['headers']['Content-Type']);
        }

        $response = $this->httpClient->request($method, $url, $mergedOptions);

        // Throw exception for non-2xx responses
        if ($response->getStatusCode() >= 400) {
            $content = $response->getContent(false);
            $errorData = json_decode($content, true) ?: ['error' => $content];

            throw new \RuntimeException(
                sprintf(
                    "YouTrust API error: %d - %s. Response: %s",
                    $response->getStatusCode(),
                    json_encode($errorData)
                )
            );
        }

        return $response;
    }

    // ========================================================================
    // GETTERS FOR CONFIGURATION
    // ========================================================================

    /**
     * Get the configured API base URL
     */
    public function getApiBaseUrl(): string
    {
        return $this->youTrustBaseUrl;
    }

    /**
     * Get the configured API key (masked)
     */
    public function getApiKeyMasked(): string
    {
        $key = $this->youTrustApiKey;
        $length = strlen($key);

        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($key, 0, 4) . str_repeat('*', $length - 8) . substr($key, -4);
    }

    /**
     * Check if the service is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->youTrustApiKey) && !empty($this->youTrustBaseUrl);
    }
}
