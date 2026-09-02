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
    $cols = pw99_ensure_rewrite_columns($db);
    if ($cols !== '') {
        $notes[] = $cols;
    }
    $soft = pw99_soften_legacy_notnull($db);
    if ($soft !== '') {
        $notes[] = $soft;
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
        $ver = 2;
    }
    if ($ver < 3) {
        if (!$db->tableExists('writ_comments')) {
            $db->pdo()->exec("CREATE TABLE writ_comments (
              id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              writ_id BIGINT UNSIGNED NOT NULL,
              observer_id BIGINT UNSIGNED NOT NULL,
              body MEDIUMTEXT,
              save_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              KEY writ_id (writ_id),
              KEY observer_id (observer_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        $db->run('INSERT INTO schema_version (version, note) VALUES (3, ?)', ['observer comments on writs']);
        $notes[] = 'schema_version=3 comments';
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

function pw99_soften_legacy_notnull(Db $db): string
{
    $pdo = $db->pdo();
    $did = [];
    foreach (['groups', 'blocks', 'observing'] as $col) {
        if (!$db->columnExists('users', $col)) {
            continue;
        }
        try {
            $pdo->exec("ALTER TABLE users MODIFY `{$col}` JSON NULL");
            $did[] = 'users.' . $col;
        } catch (PDOException $e) {
        }
    }
    if ($db->columnExists('notes', 'group')) {
        try {
            $pdo->exec('ALTER TABLE notes MODIFY `group` BIGINT UNSIGNED NULL DEFAULT 0');
            $did[] = 'notes.group';
        } catch (PDOException $e) {
        }
    }
    return $did ? 'legacy not-null ' . implode(',', $did) : '';
}

/** Columns the rewrite needs. Runs every update so a leftover mysqli dump cannot 500 the home page. */
function pw99_ensure_rewrite_columns(Db $db): string
{
    if (!$db->tableExists('users')) {
        return '';
    }
    $pdo = $db->pdo();
    $did = [];
    $adds = [
        'users' => [
            'facility_id' => "ADD COLUMN facility_id BIGINT UNSIGNED DEFAULT NULL",
            'groups_json' => "ADD COLUMN groups_json JSON NULL",
            'blocks_json' => "ADD COLUMN blocks_json JSON NULL",
            'observing_json' => "ADD COLUMN observing_json JSON NULL",
            'editor_id' => "ADD COLUMN editor_id BIGINT UNSIGNED DEFAULT NULL",
            'totp_secret' => "ADD COLUMN totp_secret VARCHAR(64) DEFAULT NULL",
            'totp_enabled' => "ADD COLUMN totp_enabled TINYINT(1) NOT NULL DEFAULT 0",
            'notify_prefs' => "ADD COLUMN notify_prefs JSON NULL",
        ],
        'writs' => [
            'facility_id' => "ADD COLUMN facility_id BIGINT UNSIGNED DEFAULT NULL",
            'block_id' => "ADD COLUMN block_id BIGINT UNSIGNED DEFAULT 0",
            'kind' => "ADD COLUMN kind ENUM('writ','assignment','test') NOT NULL DEFAULT 'writ'",
            'memo_id' => "ADD COLUMN memo_id BIGINT UNSIGNED DEFAULT NULL",
            'test_id' => "ADD COLUMN test_id BIGINT UNSIGNED DEFAULT NULL",
            'drafts' => "ADD COLUMN drafts JSON NULL",
            'redrafts' => "ADD COLUMN redrafts JSON NULL",
            'writing_time' => "ADD COLUMN writing_time INT UNSIGNED DEFAULT 0",
            'test_answers' => "ADD COLUMN test_answers JSON DEFAULT NULL",
            'test_auto_score' => "ADD COLUMN test_auto_score INT UNSIGNED DEFAULT NULL",
        ],
        'notes' => [
            'type' => "ADD COLUMN type ENUM('note','memo','task') NOT NULL DEFAULT 'note'",
            'status' => "ADD COLUMN status ENUM('live','draft','archived') NOT NULL DEFAULT 'live'",
            'seen_writer' => "ADD COLUMN seen_writer ENUM('new','read','archived') NOT NULL DEFAULT 'new'",
            'seen_observer' => "ADD COLUMN seen_observer ENUM('new','read','archived') NOT NULL DEFAULT 'new'",
            'writing_time' => "ADD COLUMN writing_time INT UNSIGNED DEFAULT 0",
        ],
        'blocks' => [
            'facility_id' => "ADD COLUMN facility_id BIGINT UNSIGNED DEFAULT NULL",
            'group_id' => "ADD COLUMN group_id BIGINT UNSIGNED DEFAULT 0",
        ],
    ];
    foreach ($adds as $table => $cols) {
        if (!$db->tableExists($table)) {
            continue;
        }
        foreach ($cols as $name => $frag) {
            if ($db->columnExists($table, $name)) {
                continue;
            }
            try {
                $pdo->exec("ALTER TABLE `{$table}` {$frag}");
                $did[] = $table . '.' . $name;
            } catch (PDOException $e) {
            }
        }
    }
    if ($db->columnExists('writs', 'block') && $db->columnExists('writs', 'block_id')) {
        try {
            $pdo->exec('UPDATE writs SET block_id = `block` WHERE (block_id = 0 OR block_id IS NULL) AND `block` > 0');
        } catch (PDOException $e) {
        }
    }
    if ($db->columnExists('users', 'editor') && $db->columnExists('users', 'editor_id')) {
        try {
            $pdo->exec('UPDATE users SET editor_id = editor WHERE editor_id IS NULL');
        } catch (PDOException $e) {
        }
    }
    if ($db->columnExists('users', 'blocks') && $db->columnExists('users', 'blocks_json')) {
        try {
            $pdo->exec("UPDATE users SET blocks_json = IFNULL(blocks, '[]') WHERE blocks_json IS NULL");
        } catch (PDOException $e) {
        }
    }
    if ($db->tableExists('writs')) {
        try {
            $pdo->exec("UPDATE writs SET drafts = JSON_ARRAY() WHERE drafts IS NULL OR drafts = 'null'");
            $pdo->exec("UPDATE writs SET redrafts = JSON_ARRAY() WHERE redrafts IS NULL OR redrafts = 'null'");
        } catch (PDOException $e) {
        }
    }
    return $did ? 'added ' . implode(',', $did) : '';
}

