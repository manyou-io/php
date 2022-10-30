<?php

declare(strict_types=1);

namespace Manyou\BingHomepage;

use DateTimeInterface;
use RuntimeException;
use Throwable;

class RequestException extends RuntimeException
{
    public function __construct(
        string $message,
        RequestParams $params,
        ?DateTimeInterface $responseDate = null,
        ?Throwable $previous = null,
    ) {
        $message .= ". Market: {$params->getMarket()}";
        $message .= ', Date: ' . Utils::formatDateTime($params->getDate());
        $message .= ", Offset: {$params->getOffset()}";

        if ($responseDate !== null) {
            $message .= ', Response Date: ' . Utils::formatDateTime($responseDate);
        }

        parent::__construct($message, 0, $previous);
    }
}
