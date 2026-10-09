<?php

namespace App\Dto\YouTrust;

/**
 * Configuration DTO for creating a YouTrust Signature Request.
 * All fields are optional and will use sensible defaults if not provided.
 */
class SignatureRequestConfig
{
    /**
     * @param string|null $name The name of the signature request
     * @param string|null $deliveryMode Delivery mode (default: 'email')
     * @param bool|null $orderedSigners Whether signers must sign in order (default: true)
     * @param string|null $smartAnchorsResolution When to resolve smart anchors (default: 'on_activation')
     * @param string|null $timezone Timezone for the request (default: 'Europe/Paris')
     * @param string|null $externalId External identifier for tracking
     * @param string|null $auditTrailLocale Locale for audit trail (default: 'fr')
     * @param bool|null $signersAllowedToDecline Whether signers can decline (default: false)
     * @param ReminderSettings|null $reminderSettings Configuration for reminder emails
     * @param EmailNotification|null $emailNotification Configuration for email notifications
     */
    public function __construct(
        public ?string $name = null,
        public ?string $deliveryMode = 'email',
        public ?bool $orderedSigners = true,
        public ?string $smartAnchorsResolution = 'on_activation',
        public ?string $timezone = 'Europe/Paris',
        public ?string $externalId = null,
        public ?string $auditTrailLocale = 'fr',
        public ?bool $signersAllowedToDecline = false,
        public ?ReminderSettings $reminderSettings = null,
        public ?EmailNotification $emailNotification = null,
    ) {}

    /**
     * Convert to array for API request
     */
    public function toArray(): array
    {
        $data = [
            'delivery_mode' => $this->deliveryMode,
            'ordered_signers' => $this->orderedSigners,
            'smart_anchors_resolution' => $this->smartAnchorsResolution,
            'timezone' => $this->timezone,
            'audit_trail_locale' => $this->auditTrailLocale,
            'signers_allowed_to_decline' => $this->signersAllowedToDecline,
        ];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        if ($this->externalId !== null) {
            $data['external_id'] = $this->externalId;
        }

        if ($this->reminderSettings !== null) {
            $data['reminder_settings'] = $this->reminderSettings->toArray();
        }

        if ($this->emailNotification !== null) {
            $data['email_notification'] = $this->emailNotification->toArray();
        }

        // Filter out null values
        return array_filter($data, fn ($value) => $value !== null);
    }
}
