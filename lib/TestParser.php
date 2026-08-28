<?php
declare(strict_types=1);

/**
 * Editor shorthand:
 *   I: heading (not numbered)
 *   1) MC / FI / SA / TF
 *   Q: prompt
 *   [x] wrong  [v] correct   (plural [v] → checkboxes)
 *   ___a || b___   OR
 *   ___a |& b___   AND/OR (either, or both)
 *   WR: 50-100
 *   T: / F:  statement; letter is the key
 */
final class TestParser
{
    public function parse(string $source): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $source) ?: [];
        $items = [];
        $cur = null;
        $flush = function () use (&$items, &$cur) {
            if ($cur !== null) {
                $items[] = $cur;
                $cur = null;
            }
        };
        foreach ($lines as $raw) {
            $line = rtrim($raw);
            $trim = trim($line);
            if ($trim === '') {
                continue;
            }
            if (preg_match('/^I:\s*(.*)$/u', $trim, $m)) {
                $flush();
                $items[] = ['kind' => 'I', 'text' => $m[1]];
                continue;
            }
            if (preg_match('/^(\d+)\)\s*(MC|FI|SA|TF)\b/i', $trim, $m)) {
                $flush();
                $cur = ['kind' => strtoupper($m[2]), 'n' => (int) $m[1], 'q' => '', 'choices' => [], 'blank' => '', 'wr' => '', 'tf' => null, 'statement' => ''];
                continue;
            }
            if ($cur === null) {
                continue;
            }
            if (preg_match('/^Q:\s*(.*)$/u', $trim, $m)) {
                $cur['q'] = $m[1];
                if ($cur['kind'] === 'FI' && preg_match('/___(.+?)___/u', $m[1], $b)) {
                    $cur['blank'] = trim($b[1]);
                }
                continue;
            }
            if (preg_match('/^WR:\s*(.*)$/u', $trim, $m)) {
                $cur['wr'] = trim($m[1]);
                continue;
            }
            if ($cur['kind'] === 'TF' && preg_match('/^([TF]):\s*(.*)$/u', $trim, $m)) {
                $cur['tf'] = $m[1] === 'T';
                $cur['statement'] = $m[2];
                if ($cur['q'] === '') {
                    $cur['q'] = $m[2];
                }
                continue;
            }
            if (preg_match('/^\[([xv])\]\s*(.*)$/iu', $trim, $m)) {
                $cur['choices'][] = [
                    'text' => $m[2],
                    'ok' => strtolower($m[1]) === 'v',
                ];
                continue;
            }
        }
        $flush();
        return $items;
    }

    /** Sequential question numbers. Instructions untouched. Returns [items, rewrittenSource, changed]. */
    public function renumber(string $source): array
    {
        $items = $this->parse($source);
        $n = 0;
        $changed = false;
        $out = [];
        foreach ($items as &$it) {
            if ($it['kind'] === 'I') {
                $out[] = 'I: ' . $it['text'];
                continue;
            }
            $n++;
            if ((int) ($it['n'] ?? 0) !== $n) {
                $changed = true;
            }
            $it['n'] = $n;
            $out[] = $n . ') ' . $it['kind'];
            if ($it['kind'] === 'TF') {
                $flag = !empty($it['tf']) ? 'T' : 'F';
                $out[] = $flag . ': ' . ($it['statement'] !== '' ? $it['statement'] : $it['q']);
            } else {
                if ($it['q'] !== '') {
                    $out[] = 'Q: ' . $it['q'];
                }
                foreach ($it['choices'] as $c) {
                    $out[] = '[' . ($c['ok'] ? 'v' : 'x') . '] ' . $c['text'];
                }
                if ($it['kind'] === 'SA' && $it['wr'] !== '') {
                    $out[] = 'WR: ' . $it['wr'];
                }
            }
            $out[] = '';
        }
        unset($it);
        $rewritten = trim(implode("\n", $out)) . "\n";
        return [$items, $rewritten, $changed];
    }

    public function grade(array $items, array $answers): array
    {
        $autoPossible = 0;
        $autoGot = 0;
        $needHuman = false;
        $detail = [];
        foreach ($items as $it) {
            if ($it['kind'] === 'I') {
                continue;
            }
            $n = (int) $it['n'];
            $key = (string) $n;
            $ans = $answers[$key] ?? $answers[$n] ?? null;
            if ($it['kind'] === 'SA') {
                $needHuman = true;
                $detail[$n] = ['auto' => false, 'ok' => null, 'answer' => $ans];
                continue;
            }
            $autoPossible++;
            $ok = false;
            if ($it['kind'] === 'MC') {
                $okKeys = [];
                foreach ($it['choices'] as $i => $c) {
                    if ($c['ok']) {
                        $okKeys[] = $i;
                    }
                }
                $picked = is_array($ans) ? array_map('intval', $ans) : [(int) $ans];
                sort($okKeys);
                sort($picked);
                $ok = $okKeys === $picked;
            } elseif ($it['kind'] === 'TF') {
                $val = is_string($ans) ? strtoupper($ans) : $ans;
                $want = !empty($it['tf']);
                $got = ($val === 'T' || $val === '1' || $val === 1 || $val === true);
                $ok = $got === $want;
            } elseif ($it['kind'] === 'FI') {
                $ok = $this->gradeFill((string) $ans, (string) $it['blank']);
            }
            if ($ok) {
                $autoGot++;
            }
            $detail[$n] = ['auto' => true, 'ok' => $ok, 'answer' => $ans];
        }
        return [
            'auto_got' => $autoGot,
            'auto_possible' => $autoPossible,
            'need_human' => $needHuman,
            'detail' => $detail,
        ];
    }

    public function gradeFill(string $student, string $spec): bool
    {
        $student = normalize_answer($student);
        if ($student === '' || $spec === '') {
            return false;
        }
        if (strpos($spec, '|&') !== false) {
            $alts = array_map('normalize_answer', explode('|&', $spec));
            $alts = array_values(array_filter($alts, fn ($a) => $a !== ''));
            if (in_array($student, $alts, true)) {
                return true;
            }
            foreach ($alts as $a) {
                if (!str_contains($student, $a)) {
                    return false;
                }
            }
            return $alts !== [];
        }
        if (strpos($spec, '||') !== false) {
            $alts = array_map('normalize_answer', explode('||', $spec));
            $alts = array_values(array_filter($alts, fn ($a) => $a !== ''));
            return in_array($student, $alts, true);
        }
        return $student === normalize_answer($spec);
    }

    public function multiChoice(array $it): bool
    {
        $n = 0;
        foreach ($it['choices'] as $c) {
            if ($c['ok']) {
                $n++;
            }
        }
        return $n > 1;
    }
}
