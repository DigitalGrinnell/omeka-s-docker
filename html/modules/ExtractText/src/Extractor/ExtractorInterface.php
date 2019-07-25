<?php
namespace ExtractText\Extractor;

/**
 * Interface for text extractors.
 */
interface ExtractorInterface
{
    /**
     * Is this extractor available?
     *
     * @return bool
     */
    public function isAvailable();

    /**
     * Extract text from a file.
     *
     * Returns the extracted text of the file or false if the extractor could
     * not extract text.
     *
     * @param string $filePath The path to a file
     * @param array $options
     * @return string|false
     */
    public function extract($filePath, array $options = []);
}
