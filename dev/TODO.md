- Observer page to see all memos from all observed blocks
  - Use observer-block-notes-fiddle.sql
  - After migrating to MariaDB, test and remove DEV tags in list_notes_editor_view.ins.php for binder_observer.php


- Add Writing Time to writ.ins
  - writs.writing_time SQL col
    - `writing_time` INT UNSIGNED DEFAULT 0,
  - JavaScript start writing time onchange onkeyup
  - Writing time is stored in seconds as INT
  - Updated by JavaScript as scalable: 2 years, 3 months, 4 days, 13:45:32
  - Also updated by JavaScript in hidden field with the INT total
  - Recognize $_POST['writing_time'] in the POST processor

- Add Observer > Blocks
  - Use
    - https://stackoverflow.com/questions/72515789/sql-join-two-json-columns-by-two-related-ids
    - https://dbfiddle.uk/?rdbms=mariadb_10.6&fiddle=016fe5724f2deb57bc8f2e017868dfb4
  - Blocks link to:
    - Memos per block: binder_observer.php
    - Writs per block: writs_observer.php
      - Modify list_observees.ins
        - Sort by block
        - If GET b (filter block) is set, display link to all blocks (writs_observer.php without GET b)
- Add to `notes` table:
  - `type` ENUM('note', 'memo', 'task') NOT NULL,
  - `status` ENUM('live', 'draft', 'archived') NOT NULL,
  - `seen_writer` ENUM('new', 'read', 'archived') NOT NULL,
  - `seen_observer` ENUM('new', 'read', 'archived') NOT NULL,
  - `writing_time` INT UNSIGNED DEFAULT 0,
  - `possible_outof` INT UNSIGNED DEFAULT 100,
- Implement `notes` changes for memos ('memos')
  - All `FROM notes` SQL queries to use `WHERE status='live' AND type='editor_note'`
  - Radio options in editor_note `<form>` for `status` and `type`
  - Filters and special row colors for `status` and `type`
  - Writers have option to start writ from a task
  - `seen_`
    - note_editor.php to write note as "new" on UPDATE
    - note_view.php to write note as "read"
    - list_notes_editor_10.ins & list_notes_editor_view.ins special color for row on new
    - archive only applies to writer & observer on personal memos
    - observer can see whether writer has seen the memo
    - archive option as bulk actions
    - archived memos page in writer, observer & editor lockers, bulk action to restore, but no delete option