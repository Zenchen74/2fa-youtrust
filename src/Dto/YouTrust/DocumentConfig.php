<?php

namespace App\Dto\YouTrust;

/**
 * Configuration DTO for uploading a document to a YouTrust Signature Request.
 */
class DocumentConfig
{
    /**
     * @param string $filePath Path to the file to upload
     * @param string|null $nature Nature of the document (default: 'signable_document')
     * @param bool|null $parseAnchors Whether to parse anchors (default: true)
     */
    public function __construct(
        public string $filePath,
        public ?string $nature = 'signable_document',
        public ?bool $parseAnchors = true,
    ) {}

    /**
     * Get the file path
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * Get the nature
     */
    public function getNature(): string
    {
        return $this->nature ?? 'signable_document';
    }

    /**
     * Get parse anchors as string for form data
     */
    public function getParseAnchors(): string
    {
        return $this->parseAnchors === true ? 'true' : 'false';
    }
}
