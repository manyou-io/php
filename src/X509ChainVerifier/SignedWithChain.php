<?php

declare(strict_types=1);

namespace Manyou\X509ChainVerifier;

use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\ConstraintViolation;
use Lcobucci\JWT\Validation\SignedWith as SignedWithInterface;
use RuntimeException;

class SignedWithChain implements SignedWithInterface
{
    public function __construct(
        private Signer $algorithm,
        private X509ChainVerifier $chainVerifier,
    ) {
    }

    public function assert(Token $token): void
    {
        $chain = $token->headers()->get('x5c', []);

        try {
            $certificate = $this->chainVerifier->verify($chain);
        } catch (RuntimeException $e) {
            throw ConstraintViolation::error($e->getMessage(), $this);
        }

        (new SignedWith($this->algorithm, InMemory::plainText($certificate)))->assert($token);
    }
}
