<?php

namespace App\Dto\YouTrust;

/**
 * Configuration DTO for creating a YouTrust Signer.
 */
class SignerConfig
{
    /**
     * @param string $firstName First name of the signer
     * @param string $lastName Last name of the signer
     * @param string $email Email address of the signer
     * @param string|null $phoneNumber Phone number for OTP authentication
     * @param string|null $locale Locale for the signer (default: 'fr')
     * @param string|null $signatureLevel Type of signature (default: 'electronic_signature')
     * @param string|null $deliveryMode How to deliver the signature request (default: 'email')
     * @param string|null $signatureAuthenticationMode Authentication mode (default: 'otp_sms')
     */
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public ?string $phoneNumber = null,
        public ?string $locale = 'fr',
        public ?string $signatureLevel = 'electronic_signature',
        public ?string $deliveryMode = 'email',
        public ?string $signatureAuthenticationMode = 'otp_sms',
    ) {}

    /**
     * Convert to array for API request
     */
    public function toArray(): array
    {
        $data = [
            'info' => array_filter([
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'email' => $this->email,
                'phone_number' => $this->phoneNumber,
                'locale' => $this->locale,
            ], fn ($value) => $value !== null),
            'signature_level' => $this->signatureLevel,
            'delivery_mode' => $this->deliveryMode,
            'signature_authentication_mode' => $this->signatureAuthenticationMode,
        ];

        return array_filter($data, fn ($value) => $value !== null);
    }
}
