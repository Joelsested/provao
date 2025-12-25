<?php

require_once __DIR__ . '/../config/env.php';

/**
 * Environment
 */
$sandbox = filter_var(env('EFI_SANDBOX', 'false'), FILTER_VALIDATE_BOOLEAN); // false = Production | true = Homologation

/**
 * Credentials of Production
 */
$clientIdProd = env('EFI_CLIENT_ID_PROD', '');
$clientSecretProd = env('EFI_CLIENT_SECRET_PROD', '');
$pathCertificateProd = env('EFI_CERT_PATH_PROD', __DIR__ . '/producao-517293-SESTED-EJA_cert.pem'); // Absolute path to the certificate in .pem or .p12 format

/**
 * Credentials of Homologation
 */
$clientIdHomolog = env('EFI_CLIENT_ID_HOMOLOG', '');
$clientSecretHomolog = env('EFI_CLIENT_SECRET_HOMOLOG', '');
$pathCertificateHomolog = env('EFI_CERT_PATH_HOMOLOG', __DIR__ . '/homologacao-517293-SESTED-EJA-HOMO_cert.pem'); // Absolute path to the certificate in .pem or .p12 format

$pixKey = env('EFI_PIX_KEY', '');

/**
 * Array with credentials and other settings
 */
return [
	"clientId" => ($sandbox) ? $clientIdHomolog : $clientIdProd,
	"clientSecret" => ($sandbox) ? $clientSecretHomolog : $clientSecretProd,
	"certificate" => ($sandbox) ? $pathCertificateHomolog : $pathCertificateProd,
	"pwdCertificate" => ($sandbox) ? $pathCertificateHomolog : $pathCertificateProd, // Optional | Default = ""
	"sandbox" => $sandbox, // Optional | Default = false
	"pixKey" => $pixKey,
	"debug" => false, // Optional | Default = false
	"timeout" => 30, // Optional | Default = 30
	"responseHeaders" => true, //  Optional | Default = false
];
