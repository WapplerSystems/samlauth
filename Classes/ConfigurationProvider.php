<?php

namespace WapplerSystems\Samlauth;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use WapplerSystems\Samlauth\Exception\MissingConfigurationException;
use WapplerSystems\Samlauth\Repository\ConfigurationRepository;

class ConfigurationProvider implements SingletonInterface
{


    /**
     * @param null $host
     * @return array|null
     * @throws MissingConfigurationException
     */
    public function getConfiguration($host = null)
    {
        if ($host === null) $host = GeneralUtility::getIndpEnv('HTTP_HOST');

        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var ConfigurationRepository $repository */
        $repository = $objectManager->get(ConfigurationRepository::class);
        $record = $repository->findByHostname($host);

        if (!is_array($record)) {
            throw new MissingConfigurationException(sprintf("no saml configuration found for domain %s", $host));
        }

        return $record;
    }


    /**
     * Get Settings
     *
     * @return array
     * @throws MissingConfigurationException
     */
    public function getSAMLSettings()
    {

        $configuration = $this->getConfiguration();


        $spSlsUrl = $configuration['sp_sls_page'] . '&type=701002';
        $spAcsUrl = $configuration['sp_acs_page'];


        if ($GLOBALS['TSFE']->tmpl !== null) {
            // only if TyposcriptFrontendController->tmpl is initialized for UriBuilder

            $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $spSlsUrl = $cObj->typolink_URL([
                'parameter' => $configuration['sp_sls_page'],
                'additionalParams' => 'type=701002',
                'forceAbsoluteUrl' => 1
            ]);

            $spAcsUrl = $cObj->typolink_URL([
                'parameter' => $configuration['sp_acs_page'],
                'forceAbsoluteUrl' => 1
            ]);
        }

        $samlStrict = true;
        $samlDebug = true;

        $idpEntityid = $configuration['idp_entity_id'];
        $idpSSO = $configuration['url'];
        $idpSSOBinding = null;
        $idpSLO = $configuration['url'];

        $spEntityid = $configuration['name'];
        $spNameIDFormat = 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress';

        $nameIdEncrypted = false;
        $authnReqsSigned = true;
        $logoutReqSigned = true;
        $logoutResSigned = true;
        $wMesSigned = false;
        $wAssertSigned = false;
        $wAssertEncrypted = false;

        $signatureAlgorithm = 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256';

        $digestAlgorithm = 'http://www.w3.org/2001/04/xmlenc#sha256';

        $lowerCaseUrlEncoding = false;

        $reqAuthnContext = '';

        if (isset($reqAuthnContext)) {
            if (!is_array($reqAuthnContext)) {
                $reqAuthnContext = explode(',', $reqAuthnContext);
            }
        } else {
            $reqAuthnContext = false;
        }

        $signMetadata = false;

        $defaultSSOBinding = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect';
        $defaultSig = 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256';
        $defaultDigestAlg = 'http://www.w3.org/2001/04/xmlenc#sha256';
        $defaultNameID = 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress';

        $settings = [
            'strict' => true,
            'debug' => ($configuration['debug'] === 1),

            'sp' => [
                'entityId' => $spEntityid,
                'assertionConsumerService' => [
                    'url' => $spAcsUrl,
                ],
                'singleLogoutService' => [
                    'url' => $spSlsUrl,
                ],
                'NameIDFormat' => $spNameIDFormat ? $spNameIDFormat : $defaultNameID,
            ],
            'idp' => [
                'entityId' => $idpEntityid,
                'singleSignOnService' => [
                    'url' => $idpSSO,
                    'binding' => isset($idpSSOBinding) ? $idpSSOBinding : $defaultSSOBinding,
                ],
            ],
            'security' => [
                'signMetadata' => isset($signMetadata) ? (bool)$signMetadata : false,
                'nameIdEncrypted' => isset($nameIdEncrypted) ? $nameIdEncrypted : false,
                'authnRequestsSigned' => isset($authnReqsSigned) ? $authnReqsSigned : false,
                'logoutRequestSigned' => isset($logoutReqSigned) ? $logoutReqSigned : false,
                'logoutResponseSigned' => isset($logoutResSigned) ? $logoutResSigned : false,
                'wantMessagesSigned' => isset($wMesSigned) ? $wMesSigned : false,
                'wantAssertionsSigned' => isset($wAssertSigned) ? $wAssertSigned : false,
                'wantAssertionsEncrypted' => isset($wAssertEncrypted) ? $wAssertEncrypted : false,
                'signatureAlgorithm' => isset($signatureAlgorithm) ? $signatureAlgorithm : $defaultSig,
                'digestAlgorithm' => isset($digestAlgorithm) ? $digestAlgorithm : $defaultDigestAlg,
                'lowercaseUrlencoding' => isset($lowerCaseUrlEncoding) ? $lowerCaseUrlEncoding : false,
                'requestedAuthnContext' => !empty($reqAuthnContext) ? $reqAuthnContext : false,
                'relaxDestinationValidation' => true,
                'allowRepeatAttributeName' => true,
                'wantNameId' => false
            ]
        ];

        $spCert = $configuration['certificate'];
        $spPrivatekey = $configuration['cert_key'];

        $idpX509cert = $configuration['idp_certificate'];
        $idpX509cert2 = '';
        $idpX509cert3 = '';

        $settings['idp']['x509certMulti'] = [
            'signing' => [
                0 => $idpX509cert,
            ],
            'encryption' => [
                0 => $idpX509cert,
            ]
        ];
        if (!empty($idpX509cert2)) {
            $settings['idp']['x509certMulti']['signing'][] = $idpX509cert2;
        }
        if (!empty($idpX509cert3)) {
            $settings['idp']['x509certMulti']['signing'][] = $idpX509cert3;
        }

        if (!empty($spCert)) {
            $settings['sp']['x509cert'] = $spCert;
        }

        if (!empty($spPrivatekey)) {
            $settings['sp']['privateKey'] = $spPrivatekey;
        }

        if (!empty($idpSLO)) {
            $settings['idp']['singleLogoutService']['url'] = $idpSLO;
        }

        return $settings;
    }


}
