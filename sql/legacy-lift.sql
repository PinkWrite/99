-- PinkWrite 99: lift a pre-PDO / mysqli dump (dev/sql.sql era) into the current schema.
--
-- Not drop-in compatible. The new app reads groups_json, editor_id, block_id, kind,
-- created_at, facilities, tests, etc. Old names (groups, editor, block, type, date_created)
-- are copied, then left in place so nothing is destroyed.
--
-- Use AFTER importing the old dump into the target database (do not import on top of a
-- freshly installed empty schema — drop those empty tables first, or import into a new DB).
--
--   mysql -u USER -p DATABASE < sql/legacy-lift.sql
--
-- This file lives on the legacy branch. Master lifts the same dump via php bin/update.php.
-- tasks / task_templates / groups / group_members from the old "future dev" block are
-- left unused; memos/assignments/tests now live on notes + writs.kind + tests.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DELIMITER //

DROP PROCEDURE IF EXISTS pw99_addcol //
CREATE PROCEDURE pw99_addcol(IN tbl VARCHAR(64), IN col VARCHAR(64), IN defn TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = tbl AND column_name = col
  ) THEN
    SET @pw99s = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN `', col, '` ', defn);
    PREPARE pw99x FROM @pw99s; EXECUTE pw99x; DEALLOCATE PREPARE pw99x;
  END IF;
END //

DROP PROCEDURE IF EXISTS pw99_copycol //
CREATE PROCEDURE pw99_copycol(IN tbl VARCHAR(64), IN src VARCHAR(64), IN dst VARCHAR(64))
BEGIN
  IF EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = tbl AND column_name = src
  ) AND EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = tbl AND column_name = dst
  ) THEN
    SET @pw99s = CONCAT('UPDATE `', tbl, '` SET `', dst, '` = `', src, '` WHERE `', dst, '` IS NULL');
    PREPARE pw99x FROM @pw99s; EXECUTE pw99x; DEALLOCATE PREPARE pw99x;
  END IF;
END //

DROP PROCEDURE IF EXISTS pw99_innodb //
CREATE PROCEDURE pw99_innodb(IN tbl VARCHAR(64))
BEGIN
  IF EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = tbl AND engine <> 'InnoDB'
  ) THEN
    SET @pw99s = CONCAT('ALTER TABLE `', tbl, '` ENGINE=InnoDB, DEFAULT CHARSET=utf8mb4');
    PREPARE pw99x FROM @pw99s; EXECUTE pw99x; DEALLOCATE PREPARE pw99x;
  END IF;
END //

DELIMITER ;

-- Core tables from the old dump
CALL pw99_innodb('users');
CALL pw99_innodb('notes');
CALL pw99_innodb('blocks');
CALL pw99_innodb('writs');
CALL pw99_innodb('clickathon');
CALL pw99_innodb('tasks');
CALL pw99_innodb('task_templates');
CALL pw99_innodb('groups');
CALL pw99_innodb('group_members');

-- New tables (CREATE IF NOT EXISTS; ignore if already present)
CREATE TABLE IF NOT EXISTS schema_version (
  version INT UNSIGNED NOT NULL PRIMARY KEY,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  note VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS facilities (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  code VARCHAR(16) DEFAULT NULL,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oauth_identities (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  provider VARCHAR(16) NOT NULL,
  subject VARCHAR(191) NOT NULL,
  email VARCHAR(160) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY provider_subject (provider, subject),
  KEY user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS passkeys (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  credential_id VARBINARY(255) NOT NULL,
  public_key TEXT NOT NULL,
  sign_count INT UNSIGNED NOT NULL DEFAULT 0,
  name VARCHAR(80) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY credential_id (credential_id),
  KEY user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_resets (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tests (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  editor_id BIGINT UNSIGNED NOT NULL,
  facility_id BIGINT UNSIGNED DEFAULT NULL,
  block_id BIGINT UNSIGNED DEFAULT 0,
  title VARCHAR(122) DEFAULT NULL,
  source MEDIUMTEXT NOT NULL,
  parsed JSON NOT NULL,
  status ENUM('draft','current','archived') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY editor_id (editor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  type VARCHAR(40) NOT NULL,
  title VARCHAR(160) NOT NULL,
  body VARCHAR(255) DEFAULT NULL,
  link VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO facilities (name, code, status)
SELECT 'Home facility', 'HOME', 'open'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM facilities LIMIT 1);

-- users
CALL pw99_addcol('users', 'facility_id', 'BIGINT UNSIGNED DEFAULT NULL');
CALL pw99_addcol('users', 'groups_json', 'JSON NULL');
CALL pw99_addcol('users', 'blocks_json', 'JSON NULL');
CALL pw99_addcol('users', 'observing_json', 'JSON NULL');
CALL pw99_addcol('users', 'editor_id', 'BIGINT UNSIGNED DEFAULT NULL');
CALL pw99_addcol('users', 'totp_secret', 'VARCHAR(64) DEFAULT NULL');
CALL pw99_addcol('users', 'totp_enabled', 'TINYINT(1) NOT NULL DEFAULT 0');
CALL pw99_addcol('users', 'notify_prefs', 'JSON NULL');
CALL pw99_addcol('users', 'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL pw99_addcol('users', 'modified_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL pw99_copycol('users', 'groups', 'groups_json');
CALL pw99_copycol('users', 'blocks', 'blocks_json');
CALL pw99_copycol('users', 'observing', 'observing_json');
CALL pw99_copycol('users', 'editor', 'editor_id');
CALL pw99_copycol('users', 'date_created', 'created_at');
CALL pw99_copycol('users', 'date_modified', 'modified_at');

UPDATE users SET groups_json = '[]' WHERE groups_json IS NULL;
UPDATE users SET blocks_json = '[]' WHERE blocks_json IS NULL;
UPDATE users SET observing_json = '[]' WHERE observing_json IS NULL;
UPDATE users SET notify_prefs = '{"inapp":{},"email":{}}' WHERE notify_prefs IS NULL;
UPDATE users SET facility_id = (SELECT id FROM facilities ORDER BY id LIMIT 1)
  WHERE facility_id IS NULL AND type != 'superintendent';

ALTER TABLE users
  MODIFY type ENUM('writer','observer','editor','supervisor','admin','superintendent') NOT NULL,
  MODIFY email VARCHAR(120) NOT NULL,
  MODIFY status ENUM('signup','active','dormant','grad') NOT NULL DEFAULT 'active';

-- notes
CALL pw99_addcol('notes', 'type', "ENUM('note','memo','task') NOT NULL DEFAULT 'note'");
CALL pw99_addcol('notes', 'status', "ENUM('live','draft','archived') NOT NULL DEFAULT 'live'");
CALL pw99_addcol('notes', 'seen_writer', "ENUM('new','read','archived') NOT NULL DEFAULT 'new'");
CALL pw99_addcol('notes', 'seen_observer', "ENUM('new','read','archived') NOT NULL DEFAULT 'new'");
CALL pw99_addcol('notes', 'writing_time', 'INT UNSIGNED DEFAULT 0');
ALTER TABLE notes MODIFY body MEDIUMTEXT;

-- blocks
CALL pw99_addcol('blocks', 'facility_id', 'BIGINT UNSIGNED DEFAULT NULL');
CALL pw99_addcol('blocks', 'group_id', 'BIGINT UNSIGNED DEFAULT 0');
CALL pw99_addcol('blocks', 'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL pw99_copycol('blocks', 'group', 'group_id');
CALL pw99_copycol('blocks', 'creation_date', 'created_at');
SET @pw99has_bgroup := (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'blocks' AND column_name = 'group'
);
SET @pw99s := IF(@pw99has_bgroup > 0,
  'UPDATE blocks SET group_id = `group` WHERE (group_id = 0 OR group_id IS NULL) AND `group` IS NOT NULL',
  'SELECT 1');
PREPARE pw99x FROM @pw99s; EXECUTE pw99x; DEALLOCATE PREPARE pw99x;
UPDATE blocks SET facility_id = (SELECT id FROM facilities ORDER BY id LIMIT 1) WHERE facility_id IS NULL;
ALTER TABLE blocks MODIFY name VARCHAR(120) DEFAULT NULL;

-- writs
CALL pw99_addcol('writs', 'facility_id', 'BIGINT UNSIGNED DEFAULT NULL');
CALL pw99_addcol('writs', 'block_id', 'BIGINT UNSIGNED DEFAULT 0');
CALL pw99_addcol('writs', 'kind', "ENUM('writ','assignment','test') NOT NULL DEFAULT 'writ'");
CALL pw99_addcol('writs', 'memo_id', 'BIGINT UNSIGNED DEFAULT NULL');
CALL pw99_addcol('writs', 'test_id', 'BIGINT UNSIGNED DEFAULT NULL');
CALL pw99_addcol('writs', 'drafts', 'JSON NULL');
CALL pw99_addcol('writs', 'redrafts', 'JSON NULL');
CALL pw99_addcol('writs', 'writing_time', 'INT UNSIGNED DEFAULT 0');
CALL pw99_addcol('writs', 'test_answers', 'JSON DEFAULT NULL');
CALL pw99_addcol('writs', 'test_auto_score', 'INT UNSIGNED DEFAULT NULL');

CALL pw99_copycol('writs', 'block', 'block_id');
CALL pw99_copycol('writs', 'task', 'memo_id');

-- block_id defaults to 0, so copy even when not NULL
SET @pw99has_block := (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'writs' AND column_name = 'block'
);
SET @pw99s := IF(@pw99has_block > 0,
  'UPDATE writs SET block_id = `block` WHERE (block_id = 0 OR block_id IS NULL) AND `block` IS NOT NULL',
  'SELECT 1');
PREPARE pw99x FROM @pw99s; EXECUTE pw99x; DEALLOCATE PREPARE pw99x;

SET @pw99has_task := (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'writs' AND column_name = 'task'
);
SET @pw99s := IF(@pw99has_task > 0,
  'UPDATE writs SET memo_id = `task` WHERE memo_id IS NULL AND `task` IS NOT NULL AND `task` <> 0',
  'SELECT 1');
PREPARE pw99x FROM @pw99s; EXECUTE pw99x; DEALLOCATE PREPARE pw99x;

-- old writs.type: writ | task | test  →  kind: writ | assignment | test
SET @pw99has_type := (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'writs' AND column_name = 'type'
);
SET @pw99s := IF(@pw99has_type > 0,
  "UPDATE writs SET kind = IF(`type`='test','test', IF(`type`='task','assignment','writ')) WHERE kind = 'writ' OR kind IS NULL",
  'SELECT 1');
PREPARE pw99x FROM @pw99s; EXECUTE pw99x; DEALLOCATE PREPARE pw99x;

UPDATE writs SET drafts = JSON_ARRAY() WHERE drafts IS NULL OR drafts = 'null';
UPDATE writs SET redrafts = JSON_ARRAY() WHERE redrafts IS NULL OR redrafts = 'null';
UPDATE writs SET facility_id = (SELECT id FROM facilities ORDER BY id LIMIT 1) WHERE facility_id IS NULL;

ALTER TABLE writs
  MODIFY draft_status ENUM('saved','submitted','reviewed','redraft') NOT NULL DEFAULT 'saved';

-- clickathon
ALTER TABLE clickathon MODIFY username_list MEDIUMTEXT NOT NULL;
UPDATE clickathon SET ip = LEFT(ip, 45) WHERE CHAR_LENGTH(ip) > 45;
ALTER TABLE clickathon MODIFY ip VARCHAR(45) NOT NULL;

INSERT IGNORE INTO schema_version (version, note) VALUES
  (1, 'initial pdo/oop schema'),
  (2, 'oauth google apple github');

DROP PROCEDURE IF EXISTS pw99_addcol;
DROP PROCEDURE IF EXISTS pw99_copycol;
DROP PROCEDURE IF EXISTS pw99_innodb;

SET FOREIGN_KEY_CHECKS = 1;
