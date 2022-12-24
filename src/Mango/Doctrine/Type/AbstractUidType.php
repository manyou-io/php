<?php

declare(strict_types=1);

namespace Manyou\Mango\Doctrine\Type;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use Symfony\Component\Uid\AbstractUid;

use function bin2hex;
use function is_string;

abstract class AbstractUidType extends Type
{
    /** @return class-string<AbstractUid> */
    abstract protected function getUidClass(): string;

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return match (true) {
            $platform instanceof PostgreSQLPlatform => 'UUID',
            default => $platform->getBinaryTypeDeclarationSQL([
                'length' => '16',
                'fixed' => true,
            ]),
        };
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?AbstractUid
    {
        if ($value instanceof AbstractUid || null === $value) {
            return $value;
        }

        if (! is_string($value)) {
            throw ConversionException::conversionFailedInvalidType($value, static::class, ['null', 'string', $this->getUidClass()]);
        }

        try {
            return $this->getUidClass()::fromString($value);
        } catch (InvalidArgumentException $e) {
            throw ConversionException::conversionFailed($value, static::class, $e);
        }
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        $value = $this->convertToAbstractUid($value);

        return match (true) {
            $value === null => null,
            $platform instanceof OraclePlatform => bin2hex($value->toBinary()),
            $platform instanceof PostgreSQLPlatform => $value->toRfc4122(),
            default => $value->toBinary(),
        };
    }

    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform): string
    {
        return match (true) {
            $platform instanceof OraclePlatform => 'HEXTORAW(' . $sqlExpr . ')',
            default => $sqlExpr,
        };
    }

    private function convertToAbstractUid($value): ?AbstractUid
    {
        if ($value instanceof AbstractUid) {
            return $value;
        }

        if (null === $value || '' === $value) {
            return null;
        }

        if (is_string($value)) {
            return $this->getUidClass()::fromString($value);
        }

        throw ConversionException::conversionFailedInvalidType($value, static::class, ['null', 'string', $this->getUidClass()]);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    public function getBindingType(): int
    {
        return ParameterType::STRING;
    }
}
