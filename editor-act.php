<?php
declare(strict_types=1);
if (!isset($_POST['type'])) {
    $_POST['type'] = 'editor';
}
if (!isset($_POST['staffsubmit']) && isset($_POST['editorsubmit'])) {
    $_POST['staffsubmit'] = (string) $_POST['editorsubmit'];
}
require __DIR__ . '/staff-act.php';
