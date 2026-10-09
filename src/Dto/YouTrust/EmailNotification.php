<?php

namespace App\Dto\YouTrust;

/**
 * Configuration for email notifications
 */
class EmailNotification
{
    public function __construct(
        public ?SenderConfig $sender = null,
        public ?CustomTextConfig $customText = null,
    ) {}

    public function toArray(): array
    {
        $data = [];
        
        if ($this->sender !== null) {
            $data['sender'] = $this->sender->toArray();
        }
        
        if ($this->customText !== null) {
            $data['custom_text'] = $this->customText->toArray();
        }

        return $data;
    }
}
