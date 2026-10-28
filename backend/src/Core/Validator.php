<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    public static function month(mixed $value): bool
    {
        return is_string($value) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) === 1;
    }

    public static function date(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    public static function positiveNumber(mixed $value, bool $allowZero = false): bool
    {
        if (!is_numeric($value)) {
            return false;
        }
        return $allowZero ? (float) $value >= 0 : (float) $value > 0;
    }

    public static function id(mixed $value): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new HttpException(400, 'Identificador inválido.');
        }
        return $id;
    }

    public static function requireMonth(mixed $value): string
    {
        if (!self::month($value)) {
            throw new HttpException(422, 'Dados inválidos.', ['mes' => 'Use o formato YYYY-MM.']);
        }
        return $value;
    }

    public static function throwIf(array $errors): void
    {
        if ($errors !== []) {
            throw new HttpException(422, 'Dados inválidos.', $errors);
        }
    }
}
