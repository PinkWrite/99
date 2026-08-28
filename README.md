# PinkWrite 99
**Typing and Editing for Learners and Teachers**

A composition classroom: Writers draft without paste-cheating, Editors review and redraft, Observers watch, Administrators run a Facility (school), a Superintendent runs several Facilities. Blocks are classes.

## Stack
PHP 8 + MariaDB (InnoDB) + PDO. Object pages import only the modules they use (`$import = ['auth','writ']`).

## Install
1. Clone into the web folder (`/srv/www/99/webdir` or similar).
2. Create a MariaDB database and user.
3. Open `install.php`. Database host should be `127.0.0.1` (TCP), not `localhost` (Unix socket).
4. Host setting is **scheme-less**: `write.pink`, `99.example.org`, or `example.org/99`. The app always uses `https://`.
5. That creates a **Superintendent**. Create a Facility, then an Administrator.
6. Mail, SMTP password, and host live in `config.php` — SysAdmin work, not the in-app Admin.

Walk-in Superintendent recovery: set `allow_create_super` to `true` in config, open `install.php`, then set it `false` again.

## Update
- CLI: `php bin/update.php` or `bash bin/pw99-update`
- Admin / Superintendent locker: **Update app**
- Pulls GitHub `master`, never overwrites `config.php`, runs SQL migrations.

## Migrating an old dump
Import the old database first, then run the updater once. It adds `facilities`, `drafts`/`redrafts` JSON, tests, notifications, 2FA/passkey columns, and maps everyone to a Home facility.

## Roles
| Role | Seat |
|---|---|
| Writer | Student |
| Observer | Parent / tutor |
| Editor | Teacher |
| Supervisor | Facility staff |
| Admin | One Facility |
| Superintendent | Many Facilities; password reset is in-person only |

## Features
- Writs, Assignments (writ + memo instructions), Tests (MC / fill-in / short answer / T-F)
- Fill-in: `||` is OR; `|&` is AND/OR (either or both). No exclusive AND.
- Redraft: editor version becomes the writer's next start. `drafts` and `redrafts` JSON history. **Show history** or gray **no history**.
- Note → Memo; Memo → Assignment.
- Lost-password email (Admin and below). TOTP authenticator. Passkeys.
- Google, Apple, and GitHub login (SysAdmin `oauth` keys in config). Create or link. Authenticator and passkeys still apply after a social login.
- In-app + email notification checkboxes in the Locker. Acknowledge deletes the notice.

## 88
[PinkWrite 88](https://github.com/PinkWrite/88) typing practice ships in `88/`.

## License
GPLv3
