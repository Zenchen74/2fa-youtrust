<?php

namespace App\Dto\YouTrust;

/**
 * Configuration for the sender of notifications
 */
class SenderConfig
{
    public function __construct(
        public ?string $type = 'custom',
        public ?string $customName = null,
    ) {}

    public function toArray(): array
    {
        $data = [];
        
        if ($this->type !== null) {
            $data['type'] = $this->type;
        }
        
        if ($this->customName !== null) {
            $data['custom_name'] = $this->customName;
        }

        return $data;
    }
}
