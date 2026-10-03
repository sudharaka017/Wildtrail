<?php
namespace WildTrail\Support;

/**
 * Small offline QR encoder for short permit codes.
 * Generates a standards-compliant Version 1-L QR symbol in byte mode.
 * Payload limit: 17 bytes (enough for WildTrail booking codes).
 */
final class QrCodeV1
{
    private const SIZE = 21;

    public static function svg(string $text, int $scale = 7, int $border = 4): string
    {
        $matrix = self::matrix($text);
        $size = self::SIZE;
        $view = $size + ($border * 2);
        $rects = [];
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($matrix[$y][$x]) {
                    $rects[] = '<rect x="'.($x+$border).'" y="'.($y+$border).'" width="1" height="1"/>';
                }
            }
        }
        $px = $view * max(1, $scale);
        return '<svg xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Permit QR code" viewBox="0 0 '.$view.' '.$view.'" width="'.$px.'" height="'.$px.'" shape-rendering="crispEdges">'
            .'<rect width="100%" height="100%" fill="#fff"/>'
            .'<g fill="#111">'.implode('', $rects).'</g></svg>';
    }

    public static function matrix(string $text): array
    {
        if ($text === '' || strlen($text) > 17) {
            throw new \InvalidArgumentException('QR payload must contain 1 to 17 bytes.');
        }

        $data = self::makeDataCodewords($text);
        $ecc = self::reedSolomonRemainder($data, 7);
        $all = array_merge($data, $ecc);
        $bits = [];
        foreach ($all as $byte) {
            for ($i = 7; $i >= 0; $i--) $bits[] = (($byte >> $i) & 1) === 1;
        }

        $m = array_fill(0, self::SIZE, array_fill(0, self::SIZE, false));
        $fn = array_fill(0, self::SIZE, array_fill(0, self::SIZE, false));

        self::drawFinder($m, $fn, 3, 3);
        self::drawFinder($m, $fn, self::SIZE - 4, 3);
        self::drawFinder($m, $fn, 3, self::SIZE - 4);

        for ($i = 8; $i < self::SIZE - 8; $i++) {
            self::setFunction($m, $fn, 6, $i, $i % 2 === 0);
            self::setFunction($m, $fn, $i, 6, $i % 2 === 0);
        }

        // Reserve and draw format information for error correction L, mask 0.
        self::drawFormatBits($m, $fn, 0);

        $i = 0;
        $up = true;
        for ($right = self::SIZE - 1; $right >= 1; $right -= 2) {
            if ($right === 6) $right = 5;
            for ($vert = 0; $vert < self::SIZE; $vert++) {
                $y = $up ? self::SIZE - 1 - $vert : $vert;
                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j;
                    if ($fn[$y][$x]) continue;
                    $bit = $i < count($bits) ? $bits[$i++] : false;
                    // Mask pattern 0: (row + column) mod 2 == 0.
                    if ((($x + $y) & 1) === 0) $bit = !$bit;
                    $m[$y][$x] = $bit;
                }
            }
            $up = !$up;
        }

        return $m;
    }

    private static function makeDataCodewords(string $text): array
    {
        $bits = [];
        self::appendBits($bits, 0x4, 4);              // Byte mode.
        self::appendBits($bits, strlen($text), 8);    // Version 1 byte count.
        foreach (str_split($text) as $ch) self::appendBits($bits, ord($ch), 8);

        $capacity = 19 * 8;
        self::appendBits($bits, 0, min(4, $capacity - count($bits)));
        while ((count($bits) & 7) !== 0) $bits[] = false;

        $bytes = [];
        for ($i = 0; $i < count($bits); $i += 8) {
            $v = 0;
            for ($j = 0; $j < 8; $j++) $v = ($v << 1) | ($bits[$i+$j] ? 1 : 0);
            $bytes[] = $v;
        }
        $pad = [0xEC, 0x11];
        $p = 0;
        while (count($bytes) < 19) $bytes[] = $pad[$p++ & 1];
        return $bytes;
    }

    private static function appendBits(array &$bits, int $value, int $length): void
    {
        for ($i = $length - 1; $i >= 0; $i--) $bits[] = (($value >> $i) & 1) === 1;
    }

    private static function reedSolomonRemainder(array $data, int $degree): array
    {
        $divisor = self::reedSolomonDivisor($degree);
        $result = array_fill(0, $degree, 0);
        foreach ($data as $byte) {
            $factor = $byte ^ $result[0];
            array_shift($result);
            $result[] = 0;
            for ($i = 0; $i < $degree; $i++) {
                $result[$i] ^= self::gfMultiply($divisor[$i], $factor);
            }
        }
        return $result;
    }

    private static function reedSolomonDivisor(int $degree): array
    {
        $result = array_fill(0, $degree, 0);
        $result[$degree - 1] = 1;
        $root = 1;
        for ($i = 0; $i < $degree; $i++) {
            for ($j = 0; $j < $degree; $j++) {
                $result[$j] = self::gfMultiply($result[$j], $root);
                if ($j + 1 < $degree) $result[$j] ^= $result[$j + 1];
            }
            $root = self::gfMultiply($root, 0x02);
        }
        return $result;
    }

    private static function gfMultiply(int $x, int $y): int
    {
        $z = 0;
        for ($i = 7; $i >= 0; $i--) {
            $z = (($z << 1) ^ ((($z >> 7) & 1) * 0x11D));
            if ((($y >> $i) & 1) !== 0) $z ^= $x;
        }
        return $z & 0xFF;
    }

    private static function drawFinder(array &$m, array &$fn, int $cx, int $cy): void
    {
        for ($dy = -4; $dy <= 4; $dy++) {
            for ($dx = -4; $dx <= 4; $dx++) {
                $x = $cx + $dx;
                $y = $cy + $dy;
                if ($x < 0 || $x >= self::SIZE || $y < 0 || $y >= self::SIZE) continue;
                $dist = max(abs($dx), abs($dy));
                self::setFunction($m, $fn, $x, $y, $dist !== 2 && $dist !== 4);
            }
        }
    }

    private static function drawFormatBits(array &$m, array &$fn, int $mask): void
    {
        // L = 01. Data bits are 01mmm, with mmm = mask pattern.
        $data = (1 << 3) | $mask;
        $rem = $data;
        for ($i = 0; $i < 10; $i++) $rem = ($rem << 1) ^ ((($rem >> 9) & 1) * 0x537);
        $bits = (($data << 10) | $rem) ^ 0x5412;

        for ($i = 0; $i <= 5; $i++) self::setFunction($m, $fn, 8, $i, (($bits >> $i) & 1) !== 0);
        self::setFunction($m, $fn, 8, 7, (($bits >> 6) & 1) !== 0);
        self::setFunction($m, $fn, 8, 8, (($bits >> 7) & 1) !== 0);
        self::setFunction($m, $fn, 7, 8, (($bits >> 8) & 1) !== 0);
        for ($i = 9; $i < 15; $i++) self::setFunction($m, $fn, 14 - $i, 8, (($bits >> $i) & 1) !== 0);

        for ($i = 0; $i < 8; $i++) self::setFunction($m, $fn, self::SIZE - 1 - $i, 8, (($bits >> $i) & 1) !== 0);
        for ($i = 8; $i < 15; $i++) self::setFunction($m, $fn, 8, self::SIZE - 15 + $i, (($bits >> $i) & 1) !== 0);
        self::setFunction($m, $fn, 8, self::SIZE - 8, true);
    }

    private static function setFunction(array &$m, array &$fn, int $x, int $y, bool $dark): void
    {
        $m[$y][$x] = $dark;
        $fn[$y][$x] = true;
    }
}
