# PinkWrite 99
**Typing and Editing for Learners and Teachers**

Teachers, tutors, parents, and other educators can use this to ensure that students focus on learning content and core skill.

## Features

- Useful for English Composition, writing assignments, and typing practice
- Copy-paste is disabled, so there is no cheating!
- Typing page displays a live word-counter
- A "writer" can type-up "writs" for review and scoring
- An "editor" can review and recommend changes
- An "observer" (such as a parent) can watch the writer's progress
- An "admin" can create "blocks" (such as classes), enroll writers, and assign observers
- Everyone can keep "notes", either in a special area per user or within each writ project
- Full supervisor features, including account control and repeat failed login IPs
- Ready for K12 school or other educational use
- Easy one-step install as a web app
- Free because it's OpenSource!

### Includes:
- [PinkWrite 88](https://github.com/PinkWrite/88) (Learn typing to thoughtlessly use correct fingers within hours, included in PinkWrite 99)
- [PinkWrite 99](https://github.com/PinkWrite/99) (Writing composition and editing, where you are now)

## Web cloud requirements
(same as WordPress)

- Simple LAMP stack
  - Linux
  - Apache web server
  - MySQL/MariaDB database
  - PHP
- FTP or CLI access to a hosted web folder
- Database and user already setup

## Install Instructions
- Unzip the [zip file](https://github.com/PinkWrite/99/archive/refs/heads/master.zip) or `git clone` the [git repo](https://github.com/PinkWrite/99.git) into your web folder
- Make sure you have a MySQL/MariaDB database and user setup
- Go to your web folder: example.com/`install.php`
- Follow the one-step instructions

## Admin recovery
- Download and place `install.php` from the web folder to create a new admin to regain access

## Bugs & Contribution

This is a collaborative effort. ***If you find any bugs or security vulnerabilities, please*** fork and request a pull on [GitHub](https://github.com/PinkWrite/99)!

You are also welcome to help rollout the product roadmap. SysAdmins and ITs for schools are especially welcome to contribute!

## Product Roadmap
- Score reporting
  - GUI to see a writer's overall score and per block
  - XML export with XSL stylesheet
- Export content per user
  - Export database information
  - Can become new database, ready OOB for user to view old assignments on self-hosted PinkWrite 99
  - Can make things easy for a student (writer) to transfer to another school using PinkWrite 99
- Tasks
  - Universal from Admin & per Editor
  - Templates
  - Assignable to blocks and writers
  - Added to writ meta, linked to original template
- Tests
  - Text-based creation
  - Default "correct" answers
  - Scoring
  - Assignable to blocks and writers
- Level management (ie grade/year in school)
  - Applies to blocks and writers
- Groups
  - Spanning blocks, editors, levels
  - Assignable to blocks, editors, levels
  - Can receive assigned tasks & tests
  - Can share common notes
