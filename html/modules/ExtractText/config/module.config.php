<?php
return [
    'service_manager' => [
        'factories' => [
            'ExtractText\ExtractorManager' => ExtractText\Service\Extractor\ManagerFactory::class,
        ],
    ],
    'extract_text_extractors' => [
        'factories' => [
            'catdoc' => ExtractText\Service\Extractor\CatdocFactory::class,
            'docx2txt' => ExtractText\Service\Extractor\Docx2txtFactory::class,
            'lynx' => ExtractText\Service\Extractor\LynxFactory::class,
            'odt2txt' => ExtractText\Service\Extractor\Odt2txtFactory::class,
            'pdftotext' => ExtractText\Service\Extractor\PdftotextFactory::class,
        ],
        'invokables' => [
            'filegetcontents' => ExtractText\Extractor\Filegetcontents::class,
        ],
        'aliases' => [
            'application/msword' => 'catdoc',
            'application/pdf' => 'pdftotext',
            'application/rtf' => 'catdoc',
            'application/vnd.oasis.opendocument.text' => 'odt2txt',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx2txt',
            'text/html' => 'lynx',
            'text/plain' => 'filegetcontents',
        ],
    ],
];
