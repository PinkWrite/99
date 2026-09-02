<?php
declare(strict_types=1);

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Same shape as the original set_button: <a><button class> so CSS on button.* applies. */
function button(string $text, string $title, string $href, string $class = 'navButton'): string
{
    return '<a title="' . h($title) . '" href="' . h($href) . '"><button type="button" class="' . h($class) . '">' . h($text) . '</button></a>';
}

function post_button(string $text, string $title, string $action, string $name, string $value, string $class, string $csrf): string
{
    return '<form action="' . h($action) . '" method="post" style="display:inline">'
        . '<input type="hidden" name="_csrf" value="' . h($csrf) . '">'
        . '<input type="hidden" name="' . h($name) . '" value="' . h($value) . '">'
        . '<input type="submit" title="' . h($title) . '" value="' . h($text) . '" class="' . h($class) . '">'
        . '</form>';
}

function get_switch(string $text, string $title, string $action, string $name, string $value, string $class): string
{
    return '<form action="' . h($action) . '" method="get" style="display:inline">'
        . '<input type="hidden" name="' . h($name) . '" value="' . h($value) . '">'
        . '<input type="submit" title="' . h($title) . '" value="' . h($text) . '" class="' . h($class) . '">'
        . '</form>';
}

function dead_switch(string $text, string $title, string $class): string
{
    return '<form action="#" method="post" style="display:inline">'
        . '<input type="submit" title="' . h($title) . '" value="' . h($text) . '" class="' . h($class) . '" disabled="disabled">'
        . '</form>';
}

function history_button(bool $hasHistory, string $href): string
{
    if ($hasHistory) {
        return button('Show history', 'Open draft and redraft history', $href, 'lt_button');
    }
    return '<button type="button" class="act_disabled" disabled title="No redraft history">no history</button>';
}

/** First click reveals Cancel + Confirm over a page-dimming overlay. */
function confirm_submit(string $name, string $firstLabel, string $confirmLabel): string
{
    return '<span class="pw-confirm-wrap">'
        . '<button type="button" class="dk_sub_button pw-confirm-go" data-pw-confirm="' . h($name) . '">' . h($firstLabel) . '</button>'
        . '<button type="button" class="dk_sub_button pw-confirm-cancel" hidden>Cancel</button>'
        . '<input type="submit" name="' . h($name) . '" value="' . h($confirmLabel) . '" id="' . h($name) . '" class="ln_button pw-confirm-yes" hidden disabled>'
        . '</span>';
}

function nl_text(?string $s): string
{
    return nl2br(h($s), false);
}

/** First line of a note body, like the original strtok(). */
function note_heading(?string $body): string
{
    $body = str_replace(["\r\n", "\r"], "\n", (string) $body);
    $line = trim((string) strtok($body, "\n"));
    return $line !== '' ? $line : '(untitled)';
}
