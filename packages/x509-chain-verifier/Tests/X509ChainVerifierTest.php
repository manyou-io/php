<?php

declare(strict_types=1);

namespace Manyou\X509ChainVerifier\Tests;

use Manyou\X509ChainVerifier\X509ChainVerifier;
use PHPUnit\Framework\TestCase;

use function Safe\openssl_x509_fingerprint;
use function Safe\openssl_x509_read;

class X509ChainVerifierTest extends TestCase
{
    public const LEAF_PEM = 'MIIEMDCCA7agAwIBAgIQaPoPldvpSoEH0lBrjDPv9jAKBggqhkjOPQQDAzB1MUQwQgYDVQQDDDtBcHBsZSBXb3JsZHdpZGUgRGV2ZWxvcGVyIFJlbGF0aW9ucyBDZXJ0aWZpY2F0aW9uIEF1dGhvcml0eTELMAkGA1UECwwCRzYxEzARBgNVBAoMCkFwcGxlIEluYy4xCzAJBgNVBAYTAlVTMB4XDTIxMDgyNTAyNTAzNFoXDTIzMDkyNDAyNTAzM1owgZIxQDA+BgNVBAMMN1Byb2QgRUNDIE1hYyBBcHAgU3RvcmUgYW5kIGlUdW5lcyBTdG9yZSBSZWNlaXB0IFNpZ25pbmcxLDAqBgNVBAsMI0FwcGxlIFdvcmxkd2lkZSBEZXZlbG9wZXIgUmVsYXRpb25zMRMwEQYDVQQKDApBcHBsZSBJbmMuMQswCQYDVQQGEwJVUzBZMBMGByqGSM49AgEGCCqGSM49AwEHA0IABOoTcaPcpeipNL9eQ06tCu7pUcwdCXdN8vGqaUjd58Z8tLxiUC0dBeA+euMYggh1/5iAk+FMxUFmA2a1r4aCZ8SjggIIMIICBDAMBgNVHRMBAf8EAjAAMB8GA1UdIwQYMBaAFD8vlCNR01DJmig97bB85c+lkGKZMHAGCCsGAQUFBwEBBGQwYjAtBggrBgEFBQcwAoYhaHR0cDovL2NlcnRzLmFwcGxlLmNvbS93d2RyZzYuZGVyMDEGCCsGAQUFBzABhiVodHRwOi8vb2NzcC5hcHBsZS5jb20vb2NzcDAzLXd3ZHJnNjAyMIIBHgYDVR0gBIIBFTCCAREwggENBgoqhkiG92NkBQYBMIH+MIHDBggrBgEFBQcCAjCBtgyBs1JlbGlhbmNlIG9uIHRoaXMgY2VydGlmaWNhdGUgYnkgYW55IHBhcnR5IGFzc3VtZXMgYWNjZXB0YW5jZSBvZiB0aGUgdGhlbiBhcHBsaWNhYmxlIHN0YW5kYXJkIHRlcm1zIGFuZCBjb25kaXRpb25zIG9mIHVzZSwgY2VydGlmaWNhdGUgcG9saWN5IGFuZCBjZXJ0aWZpY2F0aW9uIHByYWN0aWNlIHN0YXRlbWVudHMuMDYGCCsGAQUFBwIBFipodHRwOi8vd3d3LmFwcGxlLmNvbS9jZXJ0aWZpY2F0ZWF1dGhvcml0eS8wHQYDVR0OBBYEFCOCmMBq//1L5imvVmqX1oCYeqrMMA4GA1UdDwEB/wQEAwIHgDAQBgoqhkiG92NkBgsBBAIFADAKBggqhkjOPQQDAwNoADBlAjEAl4JB9GJHixP2nuibyU1k3wri5psGIxPME05sFKq7hQuzvbeyBu82FozzxmbzpogoAjBLSFl0dZWIYl2ejPV+Di5fBnKPu8mymBQtoE/H2bES0qAs8bNueU3CBjjh1lwnDsI=';

    public const LEAF_SHA256 = '87da61ce27a91db54a38618657dffec761f095047c90f59c09d9fd40589b4890';

    public const INTERMEDIATE_PEM = 'MIIDFjCCApygAwIBAgIUIsGhRwp0c2nvU4YSycafPTjzbNcwCgYIKoZIzj0EAwMwZzEbMBkGA1UEAwwSQXBwbGUgUm9vdCBDQSAtIEczMSYwJAYDVQQLDB1BcHBsZSBDZXJ0aWZpY2F0aW9uIEF1dGhvcml0eTETMBEGA1UECgwKQXBwbGUgSW5jLjELMAkGA1UEBhMCVVMwHhcNMjEwMzE3MjAzNzEwWhcNMzYwMzE5MDAwMDAwWjB1MUQwQgYDVQQDDDtBcHBsZSBXb3JsZHdpZGUgRGV2ZWxvcGVyIFJlbGF0aW9ucyBDZXJ0aWZpY2F0aW9uIEF1dGhvcml0eTELMAkGA1UECwwCRzYxEzARBgNVBAoMCkFwcGxlIEluYy4xCzAJBgNVBAYTAlVTMHYwEAYHKoZIzj0CAQYFK4EEACIDYgAEbsQKC94PrlWmZXnXgtxzdVJL8T0SGYngDRGpngn3N6PT8JMEb7FDi4bBmPhCnZ3/sq6PF/cGcKXWsL5vOteRhyJ45x3ASP7cOB+aao90fcpxSv/EZFbniAbNgZGhIhpIo4H6MIH3MBIGA1UdEwEB/wQIMAYBAf8CAQAwHwYDVR0jBBgwFoAUu7DeoVgziJqkipnevr3rr9rLJKswRgYIKwYBBQUHAQEEOjA4MDYGCCsGAQUFBzABhipodHRwOi8vb2NzcC5hcHBsZS5jb20vb2NzcDAzLWFwcGxlcm9vdGNhZzMwNwYDVR0fBDAwLjAsoCqgKIYmaHR0cDovL2NybC5hcHBsZS5jb20vYXBwbGVyb290Y2FnMy5jcmwwHQYDVR0OBBYEFD8vlCNR01DJmig97bB85c+lkGKZMA4GA1UdDwEB/wQEAwIBBjAQBgoqhkiG92NkBgIBBAIFADAKBggqhkjOPQQDAwNoADBlAjBAXhSq5IyKogMCPtw490BaB677CaEGJXufQB/EqZGd6CSjiCtOnuMTbXVXmxxcxfkCMQDTSPxarZXvNrkxU3TkUMI33yzvFVVRT4wxWJC994OsdcZ4+RGNsYDyR5gmdr0nDGg=';

    public const INTERMEDIATE_SHA256 = 'bdd4ed6e74691f0c2bfd01be0296197af1379e0418e2d300efa9c3bef642ca30';

    public const ROOT_PEM = 'MIICQzCCAcmgAwIBAgIILcX8iNLFS5UwCgYIKoZIzj0EAwMwZzEbMBkGA1UEAwwSQXBwbGUgUm9vdCBDQSAtIEczMSYwJAYDVQQLDB1BcHBsZSBDZXJ0aWZpY2F0aW9uIEF1dGhvcml0eTETMBEGA1UECgwKQXBwbGUgSW5jLjELMAkGA1UEBhMCVVMwHhcNMTQwNDMwMTgxOTA2WhcNMzkwNDMwMTgxOTA2WjBnMRswGQYDVQQDDBJBcHBsZSBSb290IENBIC0gRzMxJjAkBgNVBAsMHUFwcGxlIENlcnRpZmljYXRpb24gQXV0aG9yaXR5MRMwEQYDVQQKDApBcHBsZSBJbmMuMQswCQYDVQQGEwJVUzB2MBAGByqGSM49AgEGBSuBBAAiA2IABJjpLz1AcqTtkyJygRMc3RCV8cWjTnHcFBbZDuWmBSp3ZHtfTjjTuxxEtX/1H7YyYl3J6YRbTzBPEVoA/VhYDKX1DyxNB0cTddqXl5dvMVztK517IDvYuVTZXpmkOlEKMaNCMEAwHQYDVR0OBBYEFLuw3qFYM4iapIqZ3r6966/ayySrMA8GA1UdEwEB/wQFMAMBAf8wDgYDVR0PAQH/BAQDAgEGMAoGCCqGSM49BAMDA2gAMGUCMQCD6cHEFl4aXTQY2e3v9GwOAEZLuN+yRhHFD/3meoyhpmvOwgPUnPWTxnS4at+qIxUCMG1mihDK1A3UT82NQz60imOlM27jbdoXt2QfyFMm+YhidDkLF1vLUagM6BgD56KyKA==';

    public const ROOT_SHA256 = '63343abfb89a6a03ebb57e9b3f5fa7be7c4f5c756f3017b3a8c488c3653e9179';

    public function testTrustLeaf()
    {
        $verifier = new X509ChainVerifier([self::LEAF_SHA256]);

        $pem = $verifier->verify([self::LEAF_PEM]);

        $this->assertPemFingerprint($pem, self::LEAF_SHA256);

        $this->expectExceptionMessage('Unknown certificate.');

        $verifier->verify([self::INTERMEDIATE_PEM]);
    }

    public function testTrustIntermediate()
    {
        $verifier = new X509ChainVerifier([self::INTERMEDIATE_SHA256]);

        $pem = $verifier->verify([
            self::LEAF_PEM,
            self::INTERMEDIATE_PEM,
            self::ROOT_PEM,
        ]);

        $this->assertPemFingerprint($pem, self::LEAF_SHA256);
    }

    public function testTrustRoot()
    {
        $verifier = new X509ChainVerifier([self::ROOT_SHA256]);

        $pem = $verifier->verify([
            self::LEAF_PEM,
            self::INTERMEDIATE_PEM,
            self::ROOT_PEM,
        ]);

        $this->assertPemFingerprint($pem, self::LEAF_SHA256);
    }

    public function testVerifyRootChild()
    {
        $verifier = new X509ChainVerifier([self::ROOT_SHA256]);

        $pem = $verifier->verify([
            self::INTERMEDIATE_PEM,
            self::ROOT_PEM,
        ]);

        $this->assertPemFingerprint($pem, self::INTERMEDIATE_SHA256);
    }

    public function testBrokenChain()
    {
        $verifier = new X509ChainVerifier([self::ROOT_SHA256]);

        $this->expectExceptionMessage('Broken certificate chain.');

        $verifier->verify([
            self::INTERMEDIATE_PEM,
            self::LEAF_PEM,
            self::ROOT_PEM,
        ]);
    }

    public function testUnknownChain()
    {
        $verifier = new X509ChainVerifier([self::LEAF_SHA256]);

        $this->expectExceptionMessage('Unknown certificate chain.');

        $verifier->verify([
            self::INTERMEDIATE_PEM,
            self::ROOT_PEM,
        ]);
    }

    private function assertPemFingerprint(string $pem, string $fingerprint)
    {
        $this->assertSame($fingerprint, openssl_x509_fingerprint(openssl_x509_read($pem), 'sha256'));
    }
}
