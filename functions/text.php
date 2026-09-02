<?php
declare(strict_types=1);

function wordcount(?string $s): int
{
    $s = trim((string) $s);
    if ($s === '') {
        return 0;
    }
    $s = str_replace(["\u{2013}", "\u{2014}"], ' ', $s);
    $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
    return count(explode(' ', $s));
}

function clean_title(?string $s, int $max = 122): string
{
    $s = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $s)) ?? '');
    if (function_exists('mb_substr')) {
        return mb_substr($s, 0, $max);
    }
    return substr($s, 0, $max);
}

/** Blank title stores as Untitled. The form stays empty; this is the list/DB name. */
function writ_title(?string $s): string
{
    $t = clean_title($s);
    return $t !== '' ? $t : 'Untitled';
}

/** Blank work stores as task-{id} so the list always has a work name. */
function writ_work(?string $s, int $id): string
{
    $w = clean_title($s);
    return $w !== '' ? $w : 'task-' . $id;
}

function clean_body(?string $s): string
{
    $s = strip_tags((string) $s);
    $s = preg_replace("/[ \t]+/u", ' ', $s) ?? $s;
    $s = preg_replace("/\r\n?/", "\n", $s) ?? $s;
    $s = preg_replace("/\n{3,}/", "\n\n", $s) ?? $s;
    return trim($s);
}

function json_arr($v): array
{
    if (is_array($v)) {
        return $v;
    }
    if ($v === null || $v === '' || $v === 'null') {
        return [];
    }
    $d = json_decode((string) $v, true);
    return is_array($d) ? $d : [];
}

function json_enc($v): string
{
    $j = json_encode($v, JSON_UNESCAPED_UNICODE);
    return $j === false ? '[]' : $j;
}

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    return '';
}

function normalize_answer(string $s): string
{
    $s = strtolower(trim($s));
    $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
    $s = preg_replace("/[^\p{L}\p{N} ]+/u", '', $s) ?? $s;
    return $s;
}
