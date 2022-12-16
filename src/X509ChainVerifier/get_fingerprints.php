<?php

declare(strict_types=1);

$links = $argv;
array_shift($links);

$fingerprints = [];

foreach ($links as $link) {
    $cert = file_get_contents($link);
    $cert = base64_encode($cert);
    $cert = "-----BEGIN CERTIFICATE-----\n{$cert}\n-----END CERTIFICATE-----\n";

    $fingerprints[] = openssl_x509_fingerprint(openssl_x509_read($cert), 'sha256');
}

echo json_encode($fingerprints, JSON_PRETTY_PRINT) . "\n";
