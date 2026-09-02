<?php
declare(strict_types=1);

/**
 * Byte-mode QR Code (Model 2, ECC L, versions 1–10) as SVG.
 * For authenticator otpauth URIs. Quiet zone included. White on black page.
 */
final class QrSvg
{
    /** @var int[] */
    private static array $exp = [];
    /** @var int[] */
    private static array $log = [];

    public static function make(string $text): string
    {
        $grid = self::encode($text);
        $n = count($grid);
        $q = 4;
        $dim = $n + 2 * $q;
        $out = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $dim . ' ' . $dim . '" width="168" height="168" shape-rendering="crispEdges" aria-hidden="true">';
        $out .= '<rect width="' . $dim . '" height="' . $dim . '" fill="#ffffff"/>';
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if ($grid[$y][$x]) {
                    $out .= '<rect x="' . ($x + $q) . '" y="' . ($y + $q) . '" width="1" height="1" fill="#111111"/>';
                }
            }
        }
        return $out . '</svg>';
    }

    /** @return list<list<bool>> */
    private static function encode(string $text): array
    {
        $bytes = array_values(unpack('C*', $text) ?: []);
        $len = count($bytes);
        $ver = 1;
        for (; $ver <= 10; $ver++) {
            if ($len <= self::byteCap($ver)) {
                break;
            }
        }
        if ($ver > 10) {
            $bytes = array_slice($bytes, 0, self::byteCap(10));
            $len = count($bytes);
            $ver = 10;
        }
        $bits = '0100';
        $bits .= str_pad(decbin($len), $ver >= 10 ? 16 : 8, '0', STR_PAD_LEFT);
        foreach ($bytes as $b) {
            $bits .= str_pad(decbin($b), 8, '0', STR_PAD_LEFT);
        }
        $dataCw = self::ECL[$ver][1] * self::ECL[$ver][2] + self::ECL[$ver][3] * self::ECL[$ver][4];
        $need = $dataCw * 8;
        $bits .= '0000';
        if (strlen($bits) > $need) {
            $bits = substr($bits, 0, $need);
        }
        while (strlen($bits) % 8 !== 0) {
            $bits .= '0';
        }
        $pad = true;
        while (strlen($bits) < $need) {
            $bits .= $pad ? '11101100' : '00010001';
            $pad = !$pad;
        }
        $data = [];
        for ($i = 0; $i < $dataCw; $i++) {
            $data[] = bindec(substr($bits, $i * 8, 8));
        }
        $code = self::rsBlocks($ver, $data);
        $size = 17 + 4 * $ver;
        $dark = array_fill(0, $size, array_fill(0, $size, false));
        $func = array_fill(0, $size, array_fill(0, $size, false));
        self::drawPatterns($dark, $func, $ver);
        self::placeData($dark, $func, $code, $ver);
        $best = null;
        $bestScore = PHP_INT_MAX;
        $bestMask = 0;
        for ($m = 0; $m < 8; $m++) {
            $cand = $dark;
            self::applyMask($cand, $func, $m);
            self::drawFormat($cand, $func, $m);
            $sc = self::penalty($cand);
            if ($sc < $bestScore) {
                $bestScore = $sc;
                $best = $cand;
                $bestMask = $m;
            }
        }
        unset($bestMask);
        return $best ?? $dark;
    }

    /** ECC L: [ecPerBlock, g1blocks, g1data, g2blocks, g2data] */
    private const ECL = [
        1 => [7, 1, 19, 0, 0],
        2 => [10, 1, 34, 0, 0],
        3 => [15, 1, 55, 0, 0],
        4 => [20, 1, 80, 0, 0],
        5 => [26, 1, 108, 0, 0],
        6 => [18, 2, 68, 0, 0],
        7 => [20, 2, 78, 0, 0],
        8 => [24, 2, 97, 0, 0],
        9 => [30, 2, 116, 0, 0],
        10 => [18, 2, 68, 2, 69],
    ];

    private const ALIGN = [
        1 => [],
        2 => [6, 18],
        3 => [6, 22],
        4 => [6, 26],
        5 => [6, 30],
        6 => [6, 34],
        7 => [6, 22, 38],
        8 => [6, 24, 42],
        9 => [6, 26, 46],
        10 => [6, 28, 50],
    ];

    private static function byteCap(int $ver): int
    {
        $dataCw = self::ECL[$ver][1] * self::ECL[$ver][2] + self::ECL[$ver][3] * self::ECL[$ver][4];
        $hdr = 4 + ($ver >= 10 ? 16 : 8);
        return intdiv($dataCw * 8 - $hdr, 8);
    }

    /** @param list<int> $data */
    private static function rsBlocks(int $ver, array $data): array
    {
        self::gfInit();
        [$ec, $n1, $d1, $n2, $d2] = self::ECL[$ver];
        $blocks = [];
        $off = 0;
        for ($i = 0; $i < $n1; $i++) {
            $slice = array_slice($data, $off, $d1);
            $off += $d1;
            $blocks[] = array_merge($slice, self::rs($slice, $ec));
        }
        for ($i = 0; $i < $n2; $i++) {
            $slice = array_slice($data, $off, $d2);
            $off += $d2;
            $blocks[] = array_merge($slice, self::rs($slice, $ec));
        }
        $out = [];
        $max = $d1 > $d2 ? $d1 : $d2;
        for ($i = 0; $i < $max; $i++) {
            foreach ($blocks as $b) {
                $dataLen = count($b) - $ec;
                if ($i < $dataLen) {
                    $out[] = $b[$i];
                }
            }
        }
        for ($i = 0; $i < $ec; $i++) {
            foreach ($blocks as $b) {
                $out[] = $b[count($b) - $ec + $i];
            }
        }
        return $out;
    }

    /** @param list<int> $data */
    private static function rs(array $data, int $ec): array
    {
        $gen = [1];
        for ($i = 0; $i < $ec; $i++) {
            $next = array_fill(0, count($gen) + 1, 0);
            $c = self::$exp[$i];
            for ($j = 0; $j < count($gen); $j++) {
                $next[$j] ^= self::gfMul($gen[$j], $c);
                $next[$j + 1] ^= $gen[$j];
            }
            $gen = $next;
        }
        $msg = array_merge($data, array_fill(0, $ec, 0));
        $dlen = count($data);
        for ($i = 0; $i < $dlen; $i++) {
            $f = $msg[$i];
            if ($f === 0) {
                continue;
            }
            for ($j = 1; $j <= $ec; $j++) {
                $msg[$i + $j] ^= self::gfMul($gen[$j], $f);
            }
        }
        return array_slice($msg, $dlen);
    }

    private static function gfInit(): void
    {
        if (self::$exp) {
            return;
        }
        self::$exp = array_fill(0, 256, 0);
        self::$log = array_fill(0, 256, 0);
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$exp[$i] = $x;
            self::$log[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11d;
            }
        }
        self::$exp[255] = self::$exp[0];
    }

    private static function gfMul(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }
        return self::$exp[(self::$log[$a] + self::$log[$b]) % 255];
    }

    /** @param list<list<bool>> $dark */
    private static function drawPatterns(array &$dark, array &$func, int $ver): void
    {
        $n = count($dark);
        foreach ([[0, 0], [$n - 7, 0], [0, $n - 7]] as [$x, $y]) {
            self::finder($dark, $func, $x, $y);
        }
        for ($i = 8; $i < $n - 8; $i++) {
            $on = $i % 2 === 0;
            $dark[6][$i] = $on;
            $func[6][$i] = true;
            $dark[$i][6] = $on;
            $func[$i][6] = true;
        }
        $pos = self::ALIGN[$ver];
        foreach ($pos as $y) {
            foreach ($pos as $x) {
                if (($x < 9 && $y < 9) || ($x > $n - 10 && $y < 9) || ($x < 9 && $y > $n - 10)) {
                    continue;
                }
                self::align($dark, $func, $x, $y);
            }
        }
        $dark[$n - 8][8] = true;
        $func[$n - 8][8] = true;
        for ($i = 0; $i < 9; $i++) {
            $func[8][$i] = true;
            $func[$i][8] = true;
        }
        for ($i = 0; $i < 8; $i++) {
            $func[8][$n - 1 - $i] = true;
            $func[$n - 1 - $i][8] = true;
        }
        if ($ver >= 7) {
            for ($r = 0; $r < 6; $r++) {
                for ($c = 0; $c < 3; $c++) {
                    $func[$r][$n - 11 + $c] = true;
                    $func[$n - 11 + $c][$r] = true;
                }
            }
            self::drawVersion($dark, $ver);
        }
    }

    /** @param list<list<bool>> $dark */
    private static function finder(array &$dark, array &$func, int $x, int $y): void
    {
        for ($dy = -1; $dy <= 7; $dy++) {
            for ($dx = -1; $dx <= 7; $dx++) {
                $xx = $x + $dx;
                $yy = $y + $dy;
                if ($yy < 0 || $xx < 0 || $yy >= count($dark) || $xx >= count($dark)) {
                    continue;
                }
                $in = $dx >= 0 && $dx <= 6 && $dy >= 0 && $dy <= 6;
                $on = $in && (($dx === 0 || $dx === 6 || $dy === 0 || $dy === 6) || ($dx >= 2 && $dx <= 4 && $dy >= 2 && $dy <= 4));
                $dark[$yy][$xx] = $on;
                $func[$yy][$xx] = true;
            }
        }
    }

    /** @param list<list<bool>> $dark */
    private static function align(array &$dark, array &$func, int $cx, int $cy): void
    {
        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $on = abs($dx) === 2 || abs($dy) === 2 || ($dx === 0 && $dy === 0);
                $dark[$cy + $dy][$cx + $dx] = $on;
                $func[$cy + $dy][$cx + $dx] = true;
            }
        }
    }

    /** @param list<list<bool>> $dark */
    private static function drawVersion(array &$dark, int $ver): void
    {
        $n = count($dark);
        $bits = $ver << 12;
        $g = 0x1f25;
        for ($i = 17; $i >= 12; $i--) {
            if ($bits & (1 << $i)) {
                $bits ^= $g << ($i - 12);
            }
        }
        $v = ($ver << 12) | $bits;
        for ($i = 0; $i < 18; $i++) {
            $on = (($v >> $i) & 1) === 1;
            $r = intdiv($i, 3);
            $c = $i % 3;
            $dark[$r][$n - 11 + $c] = $on;
            $dark[$n - 11 + $c][$r] = $on;
        }
    }

    /** @param list<int> $code */
    private static function placeData(array &$dark, array $func, array $code, int $ver): void
    {
        $n = count($dark);
        $bits = '';
        foreach ($code as $b) {
            $bits .= str_pad(decbin($b), 8, '0', STR_PAD_LEFT);
        }
        $remain = [0, 0, 7, 7, 7, 7, 7, 0, 0, 0, 0];
        $bits .= str_repeat('0', $remain[$ver] ?? 0);
        $k = 0;
        $max = strlen($bits);
        $dir = -1;
        $x = $n - 1;
        while ($x > 0) {
            if ($x === 6) {
                $x--;
            }
            for ($y = ($dir < 0 ? $n - 1 : 0); $y >= 0 && $y < $n; $y += ($dir < 0 ? -1 : 1)) {
                for ($dx = 0; $dx < 2; $dx++) {
                    $xx = $x - $dx;
                    if ($func[$y][$xx]) {
                        continue;
                    }
                    $dark[$y][$xx] = $k < $max && $bits[$k] === '1';
                    $k++;
                }
            }
            $dir = -$dir;
            $x -= 2;
        }
    }

    /** @param list<list<bool>> $dark */
    private static function applyMask(array &$dark, array $func, int $mask): void
    {
        $n = count($dark);
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if ($func[$y][$x]) {
                    continue;
                }
                $flip = match ($mask) {
                    0 => ($x + $y) % 2 === 0,
                    1 => $y % 2 === 0,
                    2 => $x % 3 === 0,
                    3 => ($x + $y) % 3 === 0,
                    4 => (intdiv($y, 2) + intdiv($x, 3)) % 2 === 0,
                    5 => ($x * $y) % 2 + ($x * $y) % 3 === 0,
                    6 => (($x * $y) % 2 + ($x * $y) % 3) % 2 === 0,
                    default => (($x * $y) % 3 + ($x + $y) % 2) % 2 === 0,
                };
                if ($flip) {
                    $dark[$y][$x] = !$dark[$y][$x];
                }
            }
        }
    }

    /** @param list<list<bool>> $dark */
    private static function drawFormat(array &$dark, array $func, int $mask): void
    {
        $n = count($dark);
        $data = (1 << 3) | $mask;
        $bits = $data << 10;
        for ($i = 14; $i >= 10; $i--) {
            if ($bits & (1 << $i)) {
                $bits ^= 0x537 << ($i - 10);
            }
        }
        $fmt = (($data << 10) | ($bits & 0x3ff)) ^ 0x5412;
        $map = [
            [8, 0], [8, 1], [8, 2], [8, 3], [8, 4], [8, 5], [8, 7], [8, 8],
            [7, 8], [5, 8], [4, 8], [3, 8], [2, 8], [1, 8], [0, 8],
        ];
        for ($i = 0; $i < 15; $i++) {
            $on = (($fmt >> $i) & 1) === 1;
            [$y, $x] = $map[$i];
            $dark[$y][$x] = $on;
        }
        for ($i = 0; $i < 7; $i++) {
            $on = (($fmt >> $i) & 1) === 1;
            $dark[$n - 1 - $i][8] = $on;
        }
        for ($i = 7; $i < 15; $i++) {
            $on = (($fmt >> $i) & 1) === 1;
            $dark[8][$n - 15 + $i] = $on;
        }
    }

    /** @param list<list<bool>> $d */
    private static function penalty(array $d): int
    {
        $n = count($d);
        $s = 0;
        for ($y = 0; $y < $n; $y++) {
            $run = 1;
            for ($x = 1; $x <= $n; $x++) {
                if ($x < $n && $d[$y][$x] === $d[$y][$x - 1]) {
                    $run++;
                } else {
                    if ($run >= 5) {
                        $s += $run - 2;
                    }
                    $run = 1;
                }
            }
        }
        for ($x = 0; $x < $n; $x++) {
            $run = 1;
            for ($y = 1; $y <= $n; $y++) {
                if ($y < $n && $d[$y][$x] === $d[$y - 1][$x]) {
                    $run++;
                } else {
                    if ($run >= 5) {
                        $s += $run - 2;
                    }
                    $run = 1;
                }
            }
        }
        for ($y = 0; $y < $n - 1; $y++) {
            for ($x = 0; $x < $n - 1; $x++) {
                if ($d[$y][$x] === $d[$y][$x + 1] && $d[$y][$x] === $d[$y + 1][$x] && $d[$y][$x] === $d[$y + 1][$x + 1]) {
                    $s += 3;
                }
            }
        }
        $pat = [true, false, true, true, true, false, true, false, false, false, false];
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x <= $n - 11; $x++) {
                $ok = true;
                for ($k = 0; $k < 11; $k++) {
                    if ($d[$y][$x + $k] !== $pat[$k] && $d[$y][$x + $k] !== $pat[10 - $k]) {
                        // check exact 1011101 0000 or 0000 1011101 via two patterns
                    }
                }
                if (self::finderLike($d, $y, $x, true)) {
                    $s += 40;
                }
            }
        }
        for ($x = 0; $x < $n; $x++) {
            for ($y = 0; $y <= $n - 11; $y++) {
                if (self::finderLike($d, $y, $x, false)) {
                    $s += 40;
                }
            }
        }
        $darkn = 0;
        foreach ($d as $row) {
            foreach ($row as $c) {
                if ($c) {
                    $darkn++;
                }
            }
        }
        $s += intdiv(abs(intdiv($darkn * 100, $n * $n) - 50), 5) * 10;
        return $s;
    }

    /** @param list<list<bool>> $d */
    private static function finderLike(array $d, int $y, int $x, bool $horiz): bool
    {
        $seq = [];
        for ($k = 0; $k < 11; $k++) {
            $seq[] = $horiz ? $d[$y][$x + $k] : $d[$y + $k][$x];
        }
        $a = [true, false, true, true, true, false, true, false, false, false, false];
        $b = [false, false, false, false, true, false, true, true, true, false, true];
        return $seq === $a || $seq === $b;
    }
}
