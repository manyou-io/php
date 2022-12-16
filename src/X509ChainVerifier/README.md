# X.509 Chain Verifier

Verify `x5c` JWT header. Mainly for usage of Apple App Store Server API.

## Installation

Ensure your `composer.json` allows `dev` packages.

```json
{
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

```sh
composer require manyou/x509-chain-verifier
```

## Usage with `lcobucci/jwt`

```php
use Manyou\X509ChainVerifier\SignedWithChain;
use Manyou\X509ChainVerifier\X509ChainVerifier;
use Lcobucci\JWT\Signer\Ecdsa;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Validation\Validator;
use Lcobucci\JWT\UnencryptedToken;

$parser = new Parser(new JoseEncoder());
$validator = new Validator();

// $appleNotification['signedPayload']
// https://developer.apple.com/documentation/appstoreservernotifications/responsebodyv2
$jwt = '...';

/** @var UnencryptedToken $token */
$token = $parser->parse($jwt);

$signedWithChain = new SignedWithChain(
    Ecdsa\Sha256::create(),
    new X509ChainVerifier(X509ChainVerifier::APPLE_WDR),
);

$validator->assert($token, $signedWithChain);

var_dump($token->claims()->all());
```

## Get SHA-256 fingerprints for trusted certificates

For convenience, fingerprints of [Apple Intermediate Certificates - Worldwide Developer Relations - G6-G1](https://www.apple.com/certificateauthority/) are available in constant `X509ChainVerifier::APPLE_WDR`. These fingerprints are produced by:

```sh
php vendor/manyou/x509-chain-verifier/get_fingerprints.php \
    https://www.apple.com/certificateauthority/AppleWWDRCAG6.cer \
    https://www.apple.com/certificateauthority/AppleWWDRCAG5.cer \
    https://www.apple.com/certificateauthority/AppleWWDRCAG4.cer \
    https://www.apple.com/certificateauthority/AppleWWDRCAG3.cer \
    https://www.apple.com/certificateauthority/AppleWWDRCAG2.cer \
    https://developer.apple.com/certificationauthority/AppleWWDRCA.cer
```

### Output

```
[
    "bdd4ed6e74691f0c2bfd01be0296197af1379e0418e2d300efa9c3bef642ca30",
    "53fd008278e5a595fe1e908ae9c5e5675f26243264a5a6438c023e3ce2870760",
    "ea4757885538dd8cb59ff4556f676087d83c85e70902c122e42c0808b5bce14c",
    "dcf21878c77f4198e4b4614f03d696d89c66c66008d4244e1b99161aac91601f",
    "9ed4b3b88c6a339cf1387895bda9ca6ea31a6b5ce9edf7511845923b0c8ac94c",
    "ce057691d730f89ca25e916f7335f4c8a15713dcd273a658c024023f8eb809c2"
]
```
