<?php
declare(strict_types=1);

/**
 * Apply pending SQL. Also lifts a legacy mysqli-era dump into this schema.
 * Safe to run many times. Called from install, bin/pw99-update, and Admin Locker.
 */
function pw99_migrate(App $app): string
{
    $db = $app->db;
    if (!$db) {
        return 'no database';
    }
    $notes = [];
    if (!$db->tableExists('schema_version')) {
        $notes[] = pw99_apply_schema_file($db, $app->root . '/sql/schema.sql');
    }
    if ($db->tableExists('users') && !$db->columnExists('users', 'facility_id')) {
        $notes[] = pw99_legacy_lift($db);
    }
    $promo = pw99_ensure_superintendent($db);
    if ($promo !== '') {
        $notes[] = $promo;
    }
    $ver = (int) ($db->val('SELECT MAX(version) FROM schema_version') ?? 0);
    if ($ver < 1) {
        $db->run('INSERT INTO schema_version (version, note) VALUES (1, ?)', ['initial']);
        $notes[] = 'schema_version=1';
        $ver = 1;
    }
    if ($ver < 2) {
        if (!$db->tableExists('oauth_identities')) {
            $db->pdo()->exec("CREATE TABLE oauth_identities (
              id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              user_id BIGINT UNSIGNED NOT NULL,
              provider VARCHAR(16) NOT NULL,
              subject VARCHAR(191) NOT NULL,
              email VARCHAR(160) DEFAULT NULL,
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              UNIQUE KEY provider_subject (provider, subject),
              KEY user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        $db->run('INSERT INTO schema_version (version, note) VALUES (2, ?)', ['oauth google apple github']);
        $notes[] = 'schema_version=2 oauth';
    }
    return $notes ? implode('; ', $notes) : 'schema current';
}

function pw99_apply_schema_file(Db $db, string $file): string
{
    $sql = file_get_contents($file);
    if ($sql === false) {
        return 'schema.sql missing';
    }
    $sql = preg_replace('/^--.*$/m', '', $sql) ?? $sql;
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
        if ($stmt === '' || stripos($stmt, 'SET ') === 0) {
            continue;
        }
        try {
            $db->pdo()->exec($stmt);
        } catch (PDOException $e) {
            // table-exists races are fine
        }
    }
    return 'applied schema.sql';
}

function pw99_legacy_lift(Db $db): string
{
    $pdo = $db->pdo();
    $pdo->exec('CREATE TABLE IF NOT EXISTS facilities (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      name VARCHAR(120) NOT NULL,
      code VARCHAR(16) DEFAULT NULL,
      status ENUM(\'open\',\'closed\') NOT NULL DEFAULT \'open\',
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $fid = $db->val('SELECT id FROM facilities ORDER BY id LIMIT 1');
    if (!$fid) {
        $db->run('INSERT INTO facilities (name, code) VALUES (?,?)', ['Home facility', 'HOME']);
        $fid = $db->lastId();
    }

    $adds = [
        'users' => [
            "ADD COLUMN facility_id BIGINT UNSIGNED DEFAULT NULL",
            "ADD COLUMN groups_json JSON NULL",
            "ADD COLUMN blocks_json JSON NULL",
            "ADD COLUMN observing_json JSON NULL",
            "ADD COLUMN editor_id BIGINT UNSIGNED DEFAULT NULL",
            "ADD COLUMN totp_secret VARCHAR(64) DEFAULT NULL",
            "ADD COLUMN totp_enabled TINYINT(1) NOT NULL DEFAULT 0",
            "ADD COLUMN notify_prefs JSON NULL",
        ],
        'writs' => [
            "ADD COLUMN facility_id BIGINT UNSIGNED DEFAULT NULL",
            "ADD COLUMN block_id BIGINT UNSIGNED DEFAULT 0",
            "ADD COLUMN kind ENUM('writ','assignment','test') NOT NULL DEFAULT 'writ'",
            "ADD COLUMN memo_id BIGINT UNSIGNED DEFAULT NULL",
            "ADD COLUMN test_id BIGINT UNSIGNED DEFAULT NULL",
            "ADD COLUMN drafts JSON NULL",
            "ADD COLUMN redrafts JSON NULL",
            "ADD COLUMN writing_time INT UNSIGNED DEFAULT 0",
            "ADD COLUMN test_answers JSON DEFAULT NULL",
            "ADD COLUMN test_auto_score INT UNSIGNED DEFAULT NULL",
        ],
        'notes' => [
            "ADD COLUMN type ENUM('note','memo','task') NOT NULL DEFAULT 'note'",
            "ADD COLUMN status ENUM('live','draft','archived') NOT NULL DEFAULT 'live'",
            "ADD COLUMN seen_writer ENUM('new','read','archived') NOT NULL DEFAULT 'new'",
            "ADD COLUMN seen_observer ENUM('new','read','archived') NOT NULL DEFAULT 'new'",
            "ADD COLUMN writing_time INT UNSIGNED DEFAULT 0",
        ],
        'blocks' => [
            "ADD COLUMN facility_id BIGINT UNSIGNED DEFAULT NULL",
            "ADD COLUMN group_id BIGINT UNSIGNED DEFAULT 0",
        ],
    ];
    foreach ($adds as $table => $cols) {
        foreach ($cols as $frag) {
            if (preg_match('/ADD COLUMN (\w+)/', $frag, $m) && $db->columnExists($table, $m[1])) {
                continue;
            }
            try {
                $pdo->exec("ALTER TABLE `{$table}` {$frag}");
            } catch (PDOException $e) {
                // already there
            }
        }
    }

    if ($db->columnExists('users', 'groups') && $db->columnExists('users', 'groups_json')) {
        try {
            $pdo->exec('UPDATE users SET groups_json = IFNULL(groups, \'[]\') WHERE groups_json IS NULL');
            $pdo->exec('UPDATE users SET blocks_json = IFNULL(blocks, \'[]\') WHERE blocks_json IS NULL');
            $pdo->exec('UPDATE users SET observing_json = IFNULL(observing, \'[]\') WHERE observing_json IS NULL');
        } catch (PDOException $e) {
        }
    }
    if ($db->columnExists('users', 'editor') && $db->columnExists('users', 'editor_id')) {
        try {
            $pdo->exec('UPDATE users SET editor_id = editor WHERE editor_id IS NULL');
        } catch (PDOException $e) {
        }
    }
    $pdo->exec('UPDATE users SET facility_id = ' . (int) $fid . ' WHERE facility_id IS NULL AND type != \'superintendent\'');
    $pdo->exec("UPDATE users SET notify_prefs = '{\"inapp\":{},\"email\":{}}' WHERE notify_prefs IS NULL");
    $pdo->exec("UPDATE users SET groups_json = '[]' WHERE groups_json IS NULL");
    $pdo->exec("UPDATE users SET blocks_json = '[]' WHERE blocks_json IS NULL");
    $pdo->exec("UPDATE users SET observing_json = '[]' WHERE observing_json IS NULL");

    if ($db->columnExists('writs', 'block') && $db->columnExists('writs', 'block_id')) {
        try {
            $pdo->exec('UPDATE writs SET block_id = `block` WHERE block_id = 0 OR block_id IS NULL');
        } catch (PDOException $e) {
        }
    }
    $pdo->exec("UPDATE writs SET drafts = JSON_ARRAY() WHERE drafts IS NULL OR drafts = 'null'");
    $pdo->exec("UPDATE writs SET redrafts = JSON_ARRAY() WHERE redrafts IS NULL OR redrafts = 'null'");
    $pdo->exec('UPDATE writs SET facility_id = ' . (int) $fid . ' WHERE facility_id IS NULL');
    if ($db->columnExists('writs', 'type') && $db->columnExists('writs', 'kind')) {
        try {
            $pdo->exec("UPDATE writs SET kind = IF(`type`='test','test', IF(`type`='task','assignment','writ')) WHERE kind = 'writ'");
        } catch (PDOException $e) {
        }
    }
    $pdo->exec('UPDATE blocks SET facility_id = ' . (int) $fid . ' WHERE facility_id IS NULL');

    try {
        $pdo->exec("ALTER TABLE users MODIFY type ENUM('writer','observer','editor','supervisor','admin','superintendent') NOT NULL");
    } catch (PDOException $e) {
    }
    try {
        $pdo->exec("ALTER TABLE writs MODIFY draft_status ENUM('saved','submitted','reviewed','redraft') NOT NULL DEFAULT 'saved'");
    } catch (PDOException $e) {
    }

    pw99_apply_schema_file($db, dirname(__DIR__) . '/sql/schema.sql');
    return 'legacy dump lifted into facility ' . $fid;
}

function pw99_ensure_superintendent(Db $db): string
{
    if (!$db->tableExists('users') || !$db->columnExists('users', 'type')) {
        return '';
    }
    $sid = $db->val("SELECT id FROM users WHERE type = 'superintendent' ORDER BY id LIMIT 1");
    if ($sid) {
        return '';
    }
    $aid = $db->val("SELECT id FROM users WHERE type = 'admin' ORDER BY id LIMIT 1");
    if (!$aid) {
        $aid = $db->val('SELECT id FROM users ORDER BY id LIMIT 1');
    }
    if (!$aid) {
        return '';
    }
    $db->run('UPDATE users SET type = ?, facility_id = NULL WHERE id = ?', ['superintendent', (int) $aid]);
    return 'promoted user ' . $aid . ' to superintendent';
}
