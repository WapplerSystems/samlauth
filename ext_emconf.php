<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'SAML Authentication',
    'description' => 'Authentication for SAML IDP',
    'version' => '13.0.0',
    'state' => 'stable',
    'category' => 'misc',
    'author' => 'Sven Wappler',
    'author_email' => 'info@wappler.systems',
    'author_company' => 'WapplerSystems',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];

