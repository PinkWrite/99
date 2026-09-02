<?php
declare(strict_types=1);

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Cache-bust query for a static file: content hash, so a stale sheet cannot survive a CSS edit. */
function pw99_asset_v(string $abs): string
{
    $h = is_file($abs) ? md5_file($abs) : false;
    return is_string($h) && $h !== '' ? substr($h, 0, 10) : (string) time();
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

/**
 * Named options; value posted is the array key (an id). $empty is the value=0 label.
 * @param array<int|string,string> $options
 */
function form_select(string $name, array $options, $selected = 0, string $empty = '', string $class = 'formselect', string $attrs = ''): string
{
    $html = '<select class="' . h($class) . '" name="' . h($name) . '" id="' . h($name) . '"';
    if ($attrs !== '') {
        $html .= ' ' . $attrs;
    }
    $html .= '>';
    if ($empty !== '') {
        $sel0 = ((string) $selected === '0' || $selected === '' || $selected === null) ? ' selected' : '';
        $html .= '<option value="0"' . $sel0 . '>' . h($empty) . '</option>';
    }
    foreach ($options as $val => $label) {
        $sel = ((string) $val === (string) $selected) ? ' selected' : '';
        $html .= '<option value="' . h((string) $val) . '"' . $sel . '>' . h($label) . '</option>';
    }
    return $html . '</select>';
}

function comments_markup(array $comments, int $writId, bool $canEdit, int $uid, string $csrf): string
{
    $html = '<h4 class="review">Comments</h4>';
    if (!$comments && !$canEdit) {
        return $html . '<p class="sans dk">None yet.</p>';
    }
    foreach ($comments as $c) {
        $mine = $canEdit && (int) $c['observer_id'] === $uid;
        $html .= '<div class="writcontent remarks comment-block"><p class="sans dk"><b>'
            . h((string) ($c['observer_name'] ?? 'Observer')) . '</b> · '
            . h((string) ($c['save_date'] ?? '')) . '</p>';
        if ($mine) {
            $html .= '<form method="post" class="comment-form">' . '<input type="hidden" name="_csrf" value="' . h($csrf) . '">'
                . '<input type="hidden" name="comment_id" value="' . (int) $c['id'] . '">'
                . '<textarea name="comment_body" class="writingBox" rows="3" cols="82">' . h((string) $c['body']) . '</textarea>'
                . '<p class="save-row"><button type="submit" name="save_comment" class="lt_button small" value="1">Save comment</button></p></form>';
        } else {
            $html .= '<section>' . nl_text((string) $c['body']) . '</section>';
        }
        $html .= '</div>';
    }
    if ($canEdit) {
        $html .= '<form method="post" class="comment-form">' . '<input type="hidden" name="_csrf" value="' . h($csrf) . '">'
            . '<p class="sans">New comment</p>'
            . '<textarea name="comment_body" class="writingBox" rows="3" cols="82" placeholder="Observer comment…"></textarea>'
            . '<p class="save-row"><button type="submit" name="new_comment" class="lt_button small" value="1">Post comment</button></p></form>';
    }
    return $html;
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
