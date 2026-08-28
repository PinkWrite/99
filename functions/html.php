<?php
declare(strict_types=1);

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function button(string $text, string $title, string $href, string $class = 'navButton'): string
{
    return '<a class="' . h($class) . '" title="' . h($title) . '" href="' . h($href) . '">' . h($text) . '</a>';
}

function post_button(string $text, string $title, string $action, string $name, string $value, string $class, string $csrf): string
{
    return '<form action="' . h($action) . '" method="post" style="display:inline">'
        . '<input type="hidden" name="_csrf" value="' . h($csrf) . '">'
        . '<input type="hidden" name="' . h($name) . '" value="' . h($value) . '">'
        . '<input type="submit" title="' . h($title) . '" value="' . h($text) . '" class="' . h($class) . '">'
        . '</form>';
}

function history_button(bool $hasHistory, string $href): string
{
    if ($hasHistory) {
        return button('Show history', 'Open draft and redraft history', $href, 'lt_button');
    }
    return '<button type="button" class="act_disabled" disabled title="No redraft history">no history</button>';
}

function nl_text(?string $s): string
{
    return nl2br(h($s), false);
}
