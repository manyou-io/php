<?php

declare(strict_types=1);

namespace Manyou\X509ChainVerifier;

use InvalidArgumentException;
use OpenSSLCertificate;
use RuntimeException;

use function array_flip;
use function array_map;
use function array_shift;
use function openssl_x509_verify;
use function Safe\openssl_x509_export;
use function Safe\openssl_x509_fingerprint;
use function Safe\openssl_x509_read;

class X509ChainVerifier
{
    // Apple Intermediate Certificates - Worldwide Developer Relations - G6-G1
    // https://www.apple.com/certificateauthority/
    public const APPLE_WDR = [
        'bdd4ed6e74691f0c2bfd01be0296197af1379e0418e2d300efa9c3bef642ca30',
        '53fd008278e5a595fe1e908ae9c5e5675f26243264a5a6438c023e3ce2870760',
        'ea4757885538dd8cb59ff4556f676087d83c85e70902c122e42c0808b5bce14c',
        'dcf21878c77f4198e4b4614f03d696d89c66c66008d4244e1b99161aac91601f',
        '9ed4b3b88c6a339cf1387895bda9ca6ea31a6b5ce9edf7511845923b0c8ac94c',
        'ce057691d730f89ca25e916f7335f4c8a15713dcd273a658c024023f8eb809c2',
    ];

    private array $trustedFingerprints;

    private array $cached = [];

    public function __construct(array $trustedFingerprints)
    {
        if ($trustedFingerprints === []) {
            throw new InvalidArgumentException('No trusted fingerprints.');
        }

        $this->trustedFingerprints = array_flip($trustedFingerprints);
    }

    private function verifyOne(OpenSSLCertificate $cert): string
    {
        if (! $this->isTrusted($cert)) {
            throw new RuntimeException('Unknown certificate.');
        }

        openssl_x509_export($cert, $exported);

        return $exported;
    }

    private function isTrusted(OpenSSLCertificate $cert): bool
    {
        $fingerprint = openssl_x509_fingerprint($cert, 'sha256');

        return isset($this->trustedFingerprints[$fingerprint]);
    }

    private function cache(string $base64Der, string $exported): string
    {
        $cached[$base64Der] = $exported;

        return $exported;
    }

    public function verify(array $chain): string
    {
        if (! isset($chain[0])) {
            throw new RuntimeException('No certificate chain available.');
        }

        $base64Der = $chain[0];

        if (isset($this->cached[$base64Der])) {
            return $this->cached[$base64Der];
        }

        $leaf = self::readCertificate($base64Der);

        if (! isset($chain[1])) {
            // There is only one certificate in the chain
            return $this->cache($base64Der, $this->verifyOne($leaf));
        }

        array_shift($chain);
        $issuers = array_map(self::readCertificate(...), $chain);

        $current = $leaf;
        foreach ($issuers as $issuer) {
            if (openssl_x509_verify($current, $issuer) !== 1) {
                throw new RuntimeException('Broken certificate chain.');
            }

            if ($this->isTrusted($issuer)) {
                openssl_x509_export($leaf, $exported);

                return $this->cache($base64Der, $exported);
            }

            $current = $issuer;
        }

        throw new RuntimeException('Unknown certificate chain.');
    }

    private static function readCertificate(string $cert): OpenSSLCertificate
    {
        return openssl_x509_read("-----BEGIN CERTIFICATE-----\n{$cert}\n-----END CERTIFICATE-----\n");
    }
}
