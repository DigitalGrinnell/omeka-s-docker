<?php

namespace PdfEmbed;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Media\FileRenderer\RendererInterface;
use Zend\View\Renderer\PhpRenderer;

class PdfRenderer implements RendererInterface
{
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = [])
    {
        return sprintf(
            '<iframe src="%s" style="width: 100%%; height: 600px;" allowfullscreen></iframe>',
            $view->escapeHtml($media->originalUrl())
        );
    }
}
