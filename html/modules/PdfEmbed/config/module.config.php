<?php
return [
    'file_renderers' => [
        'invokables' => [
            'pdf_embed' => 'PdfEmbed\PdfRenderer',
        ],
        'aliases' => [
            'application/pdf' => 'pdf_embed',
            'pdf' => 'pdf_embed',
        ],
    ],
];
