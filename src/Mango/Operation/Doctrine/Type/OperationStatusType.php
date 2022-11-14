<?php

declare(strict_types=1);

namespace Manyou\Mango\Operation\Doctrine\Type;

use Manyou\Mango\Doctrine\Type\BackedTinyIntEnum;
use Manyou\Mango\Doctrine\Type\TinyIntEnumType;
use Manyou\Mango\Operation\Enum\OperationStatus;

class OperationStatusType extends TinyIntEnumType
{
    use BackedTinyIntEnum;

    public const NAME = 'operation_status';

    public function getName(): string
    {
        return self::NAME;
    }

    private function getEnumClass(): string
    {
        return OperationStatus::class;
    }
}
