<?php

namespace App\Dto\YouTrust;

/**
 * Configuration for custom text in notifications
 */
class CustomTextConfig
{
    public function __construct(
        public ?string $requestSubject = null,
        public ?string $requestBody = null,
        public ?string $reminderSubject = null,
        public ?string $reminderBody = null,
    ) {}

    public function toArray(): array
    {
        $data = [];
        
        if ($this->requestSubject !== null) {
            $data['request_subject'] = $this->requestSubject;
        }
        
        if ($this->requestBody !== null) {
            $data['request_body'] = $this->requestBody;
        }
        
        if ($this->reminderSubject !== null) {
            $data['reminder_subject'] = $this->reminderSubject;
        }
        
        if ($this->reminderBody !== null) {
            $data['reminder_body'] = $this->reminderBody;
        }

        return $data;
    }
}
