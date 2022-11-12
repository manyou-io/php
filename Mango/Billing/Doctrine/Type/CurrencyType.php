<?php

declare(strict_types=1);

namespace Manyou\Mango\Billing\Doctrine\Type;

use Manyou\Mango\Doctrine\Type\BackedTinyIntEnum;
use Manyou\Mango\Doctrine\Type\TinyIntEnumType;
use Manyou\Mango\Enum\Currency;

class CurrencyType extends TinyIntEnumType
{
    use BackedTinyIntEnum;

    public const NAME = 'currency';

    public function getName(): string
    {
        return self::NAME;
    }

    private function getEnumClass(): string
    {
        return Currency::class;
    }
}
