<?php

declare(strict_types=1);

namespace Manyou\Mango\Doctrine\Type;

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
        return $platform->getBinaryTypeDeclarationSQL([
            'length' => '16',
            'fixed' => true,
        ]);
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
        if ($value === null) {
            return null;
        }

        $value = $this->convertToDatabaseValueBinary($value, $platform);

        if (
            $platform instanceof OraclePlatform
            || $platform instanceof PostgreSQLPlatform
        ) {
            return bin2hex($value);
        }

        return $value;
    }

    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform): string
    {
        if ($platform instanceof OraclePlatform) {
            return 'HEXTORAW(' . $sqlExpr . ')';
        }

        if ($platform instanceof PostgreSQLPlatform) {
            return 'decode(' . $sqlExpr . ", 'hex')";
        }

        return $sqlExpr;
    }

    private function convertToDatabaseValueBinary($value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof AbstractUid) {
            return $value->toBinary();
        }

        if (null === $value || '' === $value) {
            return null;
        }

        if (! is_string($value)) {
            throw ConversionException::conversionFailedInvalidType($value, static::class, ['null', 'string', $this->getUidClass()]);
        }

        try {
            return $this->getUidClass()::fromString($value)->toBinary();
        } catch (InvalidArgumentException) {
            throw ConversionException::conversionFailed($value, static::class);
        }
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
