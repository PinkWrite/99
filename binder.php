<?php
declare(strict_types=1);
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: memos.php' . ($query !== '' ? '?' . $query : ''));
exit;
