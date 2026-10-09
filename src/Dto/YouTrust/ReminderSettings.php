<?php

namespace App\Dto\YouTrust;

/**
 * Configuration for reminder settings
 */
class ReminderSettings
{
    public function __construct(
        public ?int $intervalInDays = 1,
        public ?int $maxOccurrences = 5,
    ) {}

    public function toArray(): array
    {
        $data = [];
        
        if ($this->intervalInDays !== null) {
            $data['interval_in_days'] = $this->intervalInDays;
        }
        
        if ($this->maxOccurrences !== null) {
            $data['max_occurrences'] = $this->maxOccurrences;
        }

        return $data;
    }
}
