<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

call_user_func(
    function ($extKey) {

        ExtensionManagementUtility::addStaticFile($extKey, 'Configuration/TypoScript',
            'Saml Auth');

    },
    'samlauth'
);
