<?php
namespace WildTrail\Support;

final class Validator
{
    private static function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    public static function name(string $value): bool
    {
        $value = trim($value);
        return $value !== ''
            && self::length($value) >= 2
            && self::length($value) <= 120
            && preg_match("/^[\\p{L} .'-]+$/u", $value) === 1;
    }

    public static function email(string $value): bool
    {
        return filter_var(trim($value), FILTER_VALIDATE_EMAIL) !== false
            && strlen(trim($value)) <= 190;
    }

    /** Accept Sri Lankan numbers and international tourist numbers in E.164-style format. */
    public static function sriLankanPhone(string $value, bool $required = true): bool
    {
        $value = trim($value);
        if ($value === '') return !$required;
        $normal = preg_replace('/[\s()\-]/', '', $value);
        if (preg_match('/^07\d{8}$/', $normal) === 1) return true;
        return preg_match('/^\+[1-9]\d{6,14}$/', $normal) === 1;
    }

    public static function password(string $value): bool
    {
        $length = strlen($value);
        return $length >= 8
            && $length <= 72
            && preg_match('/[A-Z]/', $value) === 1
            && preg_match('/[a-z]/', $value) === 1
            && preg_match('/\d/', $value) === 1
            && preg_match('/[^A-Za-z0-9]/', $value) === 1;
    }

    /** Accept old/new Sri Lankan NICs, or a simple passport identifier. */
    public static function nicOrPassport(string $value, bool $required = false): bool
    {
        $value = strtoupper(trim($value));
        if ($value === '') {
            return !$required;
        }

        $compact = preg_replace('/\s+/', '', $value);
        $oldNic = preg_match('/^\d{9}[VX]$/', $compact) === 1;
        $newNic = preg_match('/^\d{12}$/', $compact) === 1;
        $passport = preg_match('/^[A-Z0-9]{5,20}$/', $compact) === 1;
        return $oldNic || $newNic || $passport;
    }

    public static function vehiclePlate(string $value): bool
    {
        $value = strtoupper(trim($value));
        return preg_match('/^[A-Z0-9 -]{4,15}$/', $value) === 1;
    }

    public static function plainText(string $value, int $maxLength, bool $required = false): bool
    {
        $value = trim($value);
        if ($value === '') {
            return !$required;
        }
        return self::length($value) <= $maxLength;
    }
}
