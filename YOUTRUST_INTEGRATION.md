# YouTrust (ex-YouSign) API Integration

This documentation explains how to use the YouTrustService to integrate with the YouTrust API for electronic signatures.

## Configuration

### Environment Variables

Add your YouTrust API credentials to your `.env.local` file:

```bash
###> YouTrust API Configuration ###
YOU_TRUST_API_KEY="your_api_key_here"
YOU_TRUST_BASE_URL="https://api-sandbox.yousign.app/v3"
###< YouTrust API Configuration ###
```

For production, use:
```bash
YOU_TRUST_BASE_URL="https://api.yousign.app/v3"
```

### Service Configuration

The service is already configured in `config/services.yaml`:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        bind:
            string $youTrustApiKey: '%env(YOU_TRUST_API_KEY)%'
            string $youTrustBaseUrl: '%env(YOU_TRUST_BASE_URL)%'
```

## Usage

### 1. Basic Service Usage

```php
use App\Service\YouTrustService;

class YourController extends AbstractController
{
    public function __construct(
        private YouTrustService $youTrustService,
    ) {}

    public function someAction()
    {
        // Check if service is configured
        if (!$this->youTrustService->isConfigured()) {
            throw new \RuntimeException('YouTrust service not configured');
        }

        // Use the service methods...
    }
}
```

### 2. Creating a Signature Request

```php
use App\Dto\YouTrust\SignatureRequestConfig;
use App\Dto\YouTrust\ReminderSettings;
use App\Dto\YouTrust\EmailNotification;
use App\Dto\YouTrust\SenderConfig;
use App\Dto\YouTrust\CustomTextConfig;

// Create configuration objects
$reminderSettings = new ReminderSettings(
    intervalInDays: 1,
    maxOccurrences: 5
);

$customText = new CustomTextConfig(
    requestSubject: 'Convention de stage de {student} à signer',
    requestBody: 'Merci d\'accueillir {student} et de signer sa convention de stage',
    reminderSubject: '[Rappel]: Convention de stage de {student} à signer',
    reminderBody: 'Vous n\'avez pas eu le temps de signer?'
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
    name: 'Convention de stage - Étudiant X',
    deliveryMode: 'email',
    orderedSigners: true,
    smartAnchorsResolution: 'on_activation',
    timezone: 'Europe/Paris',
    externalId: '2422',
    auditTrailLocale: 'fr',
    signersAllowedToDecline: false,
    reminderSettings: $reminderSettings,
    emailNotification: $emailNotification
);

// Create the signature request
$signatureRequest = $this->youTrustService->initiateSignatureRequest($signatureRequestConfig);
$signatureRequestId = $signatureRequest['id'];
```

### 3. Uploading a Document

```php
use App\Dto\YouTrust\DocumentConfig;

$documentConfig = new DocumentConfig(
    filePath: '/path/to/your/Conventions de stage-template-fr.pdf',
    nature: 'signable_document',
    parseAnchors: true
);

$document = $this->youTrustService->uploadDocument($signatureRequestId, $documentConfig);
```

### 4. Adding Signers

```php
use App\Dto\YouTrust\SignerConfig;

$signer1 = new SignerConfig(
    firstName: 'Etienne',
    lastName: 'Buffet',
    email: 'etienne.buffet@example.com',
    phoneNumber: '+33667039604',
    locale: 'fr',
    signatureLevel: 'electronic_signature',
    deliveryMode: 'email',
    signatureAuthenticationMode: 'otp_sms'
);

$signer = $this->youTrustService->createSigner($signatureRequestId, $signer1);
```

### 5. Activating the Signature Request

```php
$activatedRequest = $this->youTrustService->activateSignatureRequest($signatureRequestId);
```

### 6. Complete Workflow (Recommended)

```php
use App\Dto\YouTrust\SignatureRequestConfig;
use App\Dto\YouTrust\DocumentConfig;
use App\Dto\YouTrust\SignerConfig;

// Create signature request configuration
$signatureRequestConfig = new SignatureRequestConfig(
    name: 'Convention de stage - Étudiant X',
    // ... other config options
);

// Create document configuration
$documentConfig = new DocumentConfig(
    filePath: '/path/to/document.pdf',
    // ... other config options
);

// Create 3 signer configurations
$signerConfigs = [
    new SignerConfig(
        firstName: 'Etienne',
        lastName: 'Buffet',
        email: 'etienne@example.com',
        phoneNumber: '+33667039604'
    ),
    new SignerConfig(
        firstName: 'Jean',
        lastName: 'Dupont',
        email: 'jean@example.com',
        phoneNumber: '+33612345678'
    ),
    new SignerConfig(
        firstName: 'Marie',
        lastName: 'Martin',
        email: 'marie@example.com',
        phoneNumber: '+33687654321'
    ),
];

// Execute complete workflow
$result = $this->youTrustService->createCompleteSignatureRequest(
    $signatureRequestConfig,
    $documentConfig,
    $signerConfigs
);

// $result contains:
// - signature_request: The created signature request data
// - document: The uploaded document data
// - signers: Array of created signers
// - activated_request: The activated signature request data
// - signature_request_id: The ID of the created signature request
```

## Available Service Methods

### Signature Requests
- `initiateSignatureRequest(SignatureRequestConfig $config): array`
- `listSignatureRequests(array $query = []): array`
- `cancelSignatureRequest(string $signatureRequestId): array`
- `getSignatureRequest(string $signatureRequestId): array`

### Documents
- `uploadDocument(string $signatureRequestId, DocumentConfig $documentConfig): array`
- `listDocuments(string $signatureRequestId): array`
- `deleteDocument(string $signatureRequestId, string $documentId): array`

### Signers
- `createSigner(string $signatureRequestId, SignerConfig $signerConfig): array`
- `listSigners(string $signatureRequestId): array`
- `deleteSigner(string $signatureRequestId, string $signerId): array`

### Activation
- `activateSignatureRequest(string $signatureRequestId): array`

### Complete Workflow
- `createCompleteSignatureRequest(SignatureRequestConfig $signatureRequestConfig, DocumentConfig $documentConfig, array $signerConfigs): array`

## Example Controller

See `src/Controller/YouTrustController.php` for a complete example with all API endpoints exposed as REST routes.

## API Routes

The following routes are available in the example controller (using Symfony attributes):

- `GET /api/youtrust/signature-requests` - List all signature requests
- `GET /api/youtrust/signature-requests/{id}` - Get a specific signature request
- `POST /api/youtrust/signature-requests/{id}/cancel` - Cancel a signature request
- `GET /api/youtrust/signature-requests/{id}/documents` - List documents for a request
- `DELETE /api/youtrust/signature-requests/{id}/documents/{docId}` - Delete a document
- `GET /api/youtrust/signature-requests/{id}/signers` - List signers for a request
- `DELETE /api/youtrust/signature-requests/{id}/signers/{signerId}` - Delete a signer
- `POST /api/youtrust/signature-requests/complete` - Create and activate complete signature request
- `GET /api/youtrust/health` - Check service configuration

**Note:** This project uses Symfony 6+ attributes for routing instead of annotations.

## DTO Reference

### SignatureRequestConfig

All fields are optional and have sensible defaults:

- `name`: Name of the signature request
- `deliveryMode`: Delivery mode (default: 'email')
- `orderedSigners`: Whether signers must sign in order (default: true)
- `smartAnchorsResolution`: When to resolve smart anchors (default: 'on_activation')
- `timezone`: Timezone for the request (default: 'Europe/Paris')
- `externalId`: External identifier for tracking
- `auditTrailLocale`: Locale for audit trail (default: 'fr')
- `signersAllowedToDecline`: Whether signers can decline (default: false)
- `reminderSettings`: ReminderSettings object
- `emailNotification`: EmailNotification object

### ReminderSettings

- `intervalInDays`: Days between reminders (default: 1)
- `maxOccurrences`: Maximum number of reminders (default: 5)

### EmailNotification

- `sender`: SenderConfig object
- `customText`: CustomTextConfig object

### SenderConfig

- `type`: Sender type (default: 'custom')
- `customName`: Custom sender name

### CustomTextConfig

- `requestSubject`: Subject for signature request emails
- `requestBody`: Body for signature request emails
- `reminderSubject`: Subject for reminder emails
- `reminderBody`: Body for reminder emails

### SignerConfig

Required fields:
- `firstName`: Signer's first name
- `lastName`: Signer's last name
- `email`: Signer's email address

Optional fields:
- `phoneNumber`: Phone number for OTP authentication
- `locale`: Locale for the signer (default: 'fr')
- `signatureLevel`: Type of signature (default: 'electronic_signature')
- `deliveryMode`: How to deliver the signature request (default: 'email')
- `signatureAuthenticationMode`: Authentication mode (default: 'otp_sms')

### DocumentConfig

- `filePath`: Path to the file to upload (required)
- `nature`: Nature of the document (default: 'signable_document')
- `parseAnchors`: Whether to parse anchors (default: true)

## Error Handling

All methods can throw exceptions:
- `TransportExceptionInterface`: Network/transport errors
- `ClientExceptionInterface`: Client errors (4xx)
- `RedirectionExceptionInterface`: Redirect errors (3xx)
- `ServerExceptionInterface`: Server errors (5xx)
- `\InvalidArgumentException`: Invalid arguments (e.g., not exactly 3 signers)
- `\RuntimeException`: General runtime errors

## Testing

Make sure to:
1. Set up your environment variables
2. Test with the sandbox API first: `https://api-sandbox.yousign.app/v3`
3. Use valid PDF files for document uploads
4. Use real email addresses and phone numbers for testing

## Migration from Previous Version

The new service is fully backward compatible but offers:
- Complete implementation of all 10 API methods
- Full parameterization via DTOs
- Complete workflow method
- Better error handling
- Example controller with REST endpoints

## Files Created/Modified

- `src/Service/YouTrustService.php` - Main service implementation
- `src/Dto/YouTrust/SignatureRequestConfig.php` - Signature request configuration DTO
- `src/Dto/YouTrust/SignerConfig.php` - Signer configuration DTO
- `src/Dto/YouTrust/DocumentConfig.php` - Document configuration DTO
- `src/Controller/YouTrustController.php` - Example REST controller
- `config/services.yaml` - Service configuration
- `.env` - Environment variables template
- `.env.local` - Local environment variables
