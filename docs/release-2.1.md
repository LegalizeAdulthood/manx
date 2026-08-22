# Release 2.1 implementation plan

Generated from the unreleased 2.1.0 schema cleanup needed before
release 2.2.0.

## Schema Guidance

All release 2.1 schema changes go into `schema/8-schema.sql`.  Do not
use `schema/9-schema.sql` for this release.

The final statement in `schema/8-schema.sql` sets the version property to
`2.1.0`, after all DDL and migration cleanup have completed.

Migrate data using one-off procedures.  Drop each procedure as soon as
its data migration has run.

Do not create persistent relationships between known-copy rows and
unknown-path rows or directories.  Keep copy lookup caches limited to
copy data derived from `copy.url`.

## Scope

Release 2.1.0 completes the unknown-directory schema while keeping known
copies independent from unknown paths.  It also adds filename caches
needed to make later unknown-path and moved-file checks efficient.

Issues:

- #145 Checking for moved files with many unknown paths is very slow.

Implement one numbered slice at a time.  Each numbered slice fixes one
issue.  When a slice is complete, remove that slice and leave the
remaining numbers unchanged.

# Implementation

## 1. Remove copy-to-unknown-directory cache

Issue: #145

Implementation:

- Remove the `site_unknown_copy_dir` table from `schema/8-schema.sql`.
  Do not add this table, or any replacement relationship table, in
  `schema/9-schema.sql`.
- Remove the `manx_build_copy_ids` and
  `manx_update_copy_unknown_dir_ids` procedures from
  `schema/8-schema.sql`.
- Update `manx_purge_unused_unknown_directories` so directory cleanup is
  based only on `site_unknown.dir_id` and the `site_unknown_dir` parent
  tree.
- Update `manx_purge_su_copies` so known-copy purging uses reconstructed
  unknown URLs and filename lookup, not `site_unknown_copy_dir`.
- Rename `site_unknown.path` to `site_unknown.filename`.  Leave
  `site_unknown_dir.path` unchanged because it stores directory paths.

```sql
ALTER TABLE `site_unknown`
  CHANGE COLUMN `path` `filename` VARCHAR(255) NOT NULL;
```

- In `schema/8-schema.sql`, add `copy.filename` as a URL basename cache
  and add the `site_filename` lookup key.  The stored value is the
  filename as it exists on disk, not the URL-encoded path component.

```sql
ALTER TABLE `copy`
  ADD COLUMN `filename` VARCHAR(255) NOT NULL DEFAULT '',
  ADD KEY `site_filename` (`site`, `filename`);
```

- In `schema/8-schema.sql`, backfill existing copy rows with one-off
  procedures that URL-decode the basename and are dropped after they
  run:

```sql
CREATE PROCEDURE `manx_decode_url_component`(
    IN `encoded_component` VARCHAR(255),
    OUT `decoded_component` VARCHAR(255))
BEGIN
DECLARE `i` INT DEFAULT 1;
DECLARE `component_len` INT DEFAULT 0;
DECLARE `hex_byte` CHAR(2) DEFAULT '';
SET `decoded_component` = '';
SET `component_len` = CHAR_LENGTH(`encoded_component`);
WHILE `i` <= `component_len` DO
IF SUBSTRING(`encoded_component`, `i`, 1) = '%'
AND `i` + 2 <= `component_len`
AND SUBSTRING(`encoded_component`, `i` + 1, 2)
REGEXP '^[0-9A-Fa-f][0-9A-Fa-f]$' THEN
SET `hex_byte` = SUBSTRING(`encoded_component`, `i` + 1, 2);
SET `decoded_component` = CONCAT(`decoded_component`,
CHAR(CONV(`hex_byte`, 16, 10)));
SET `i` = `i` + 3;
ELSE
SET `decoded_component` = CONCAT(`decoded_component`,
SUBSTRING(`encoded_component`, `i`, 1));
SET `i` = `i` + 1;
END IF;
END WHILE;
END;

CREATE PROCEDURE `manx_backfill_copy_filename`()
BEGIN
DECLARE `done` INT DEFAULT 0;
DECLARE `current_copy_id` INT DEFAULT 0;
DECLARE `encoded_filename` VARCHAR(255) DEFAULT '';
DECLARE `decoded_filename` VARCHAR(255) DEFAULT '';
DECLARE `copy_filenames` CURSOR FOR
SELECT `copy_id`, IFNULL(SUBSTRING_INDEX(`url`, '/', -1), '')
FROM `copy`
WHERE `filename` = '';
DECLARE CONTINUE HANDLER FOR NOT FOUND SET `done` = 1;
OPEN `copy_filenames`;
copy_loop: LOOP
FETCH `copy_filenames` INTO `current_copy_id`, `encoded_filename`;
IF `done` = 1 THEN
LEAVE copy_loop;
END IF;
CALL `manx_decode_url_component`(
    `encoded_filename`, `decoded_filename`);
UPDATE `copy`
SET `filename` = `decoded_filename`
WHERE `copy_id` = `current_copy_id`;
END LOOP;
CLOSE `copy_filenames`;
END;
CALL `manx_backfill_copy_filename`();
DROP PROCEDURE `manx_backfill_copy_filename`;
DROP PROCEDURE `manx_decode_url_component`;
```

- Column semantics:
  - `copy.filename` stores the basename portion of `copy.url` as it
    exists on disk, with no URL special-character encodings.  For
    example, a copy URL ending in `file%20%231.pdf` stores
    `file #1.pdf`.
  - `copy.filename` is a derived lookup cache, not user-authored data.
  - `site_unknown.filename` stores the basename portion of an unknown
    site path as it exists on disk, with no URL special-character
    encodings.
  - `site_unknown_dir.path` stores the directory portion of unknown site
    paths.
- Populate `copy.filename` in `ManxDatabase::addCopy()` from the
  decoded copy URL basename.
- Update `copy.filename` in `ManxDatabase::siteFileMoved()` when the
  copy URL is changed, using the decoded copy URL basename.
- Change `getPossiblyMovedSiteUnknownPaths()` to join candidates by
  `c.site = su.site_id`, `c.filename = su.filename`, `c.md5 <> ''`,
  and `c.size > 0`.
- Return the candidate new URL from the query and skip unchanged URLs in
  PHP before any HEAD, size, or MD5 request.
- Remove `SUBSTRING_INDEX(c.url, '/', -1)` and `CONCAT(...)` from the
  moved-file query's `JOIN` and `WHERE` predicates.
- Remove `updateCopySiteUnknownDirIds()` and `setCopySiteUnknownDirId()`
  from `IManxDatabase`, `ManxDatabase`, cron calls, URL Wizard, and
  WhatsNew ingestion paths.

Acceptance criteria:

- `schema/8-schema.sql` no longer creates `site_unknown_copy_dir`.
- `schema/9-schema.sql` contains no `site_unknown_copy_dir` migration.
- No application code reads from or writes to `site_unknown_copy_dir`.
- `site_unknown.filename` stores only the filename component of an
  unknown path.
- Existing copy rows have `copy.filename` backfilled from decoded
  `copy.url` basenames.
- Newly inserted copies store the basename of `copy.url` in
  `copy.filename`, without URL special-character encodings.
- Moved copies update `copy.url` and `copy.filename`, and remove the
  matched `site_unknown` row.
- No migration adds a `site_unknown_dir` id column to `copy`.
- No migration creates a persistent relationship between `copy` and
  unknown-path tables.
- The moved-candidate query uses the `site_filename` key.
- The moved-candidate query has no computed expressions on `copy.url` in
  its `JOIN` or `WHERE` predicates.
- Rows whose current URL already equals the candidate URL are skipped
  before any network request.
- Moved copies still update URL and unknown-path state correctly.

Automated tests:

- Add schema migration tests proving `site_unknown_copy_dir` is absent
  from `schema/8-schema.sql` and `schema/9-schema.sql`.
- Add schema migration tests for the `site_unknown.path` to
  `site_unknown.filename` rename.
- Add schema migration tests for `copy.filename`, `site_filename`, and
  the decoded backfill.
- Add a schema migration test proving no unknown-directory id column is
  added to `copy`.
- Add a schema migration test proving no persistent relationship table is
  added between `copy` and unknown-path tables.
- Add `ManxDatabaseTest` coverage proving `addCopy()` stores
  `copy.filename` without URL special-character encodings, for example
  `file #1.pdf` from a URL ending in `file%20%231.pdf`.
- Add `ManxDatabaseTest` coverage proving `siteFileMoved()` updates
  `copy.url`, decoded `copy.filename`, and unknown-path state.
- Add `ManxDatabaseTest` coverage for moved-candidate lookup by
  `copy.filename` and `site_unknown.filename`.
- Add a query regression test proving the moved-candidate SQL does not
  use `SUBSTRING_INDEX(c.url, '/', -1)` or `CONCAT(...)` in `JOIN` or
  `WHERE`, and does not reference `copy.sud_id` or
  `site_unknown_copy_dir`.
- Add `WhatsNewCleanerTest` coverage proving unchanged candidate URLs do
  not trigger HEAD, size, or MD5 requests.
- Add `WhatsNewCleanerTest` coverage for processing real moved
  candidates.
- Update URL Wizard and WhatsNew page tests so ingesting from an unknown
  path removes the unknown path without writing a copy-to-unknown cache.

Fixes #145

## Release verification

- Run the full test suite with `composer test`.
- Apply schema migrations to a production-shaped database copy.
- Smoke test URL Wizard ingestion from an unknown path.
- Smoke test WhatsNew directory ingestion.
- Smoke test unknown-path cleanup for existing copies.
- Smoke test moved-file detection without per-copy HTTP checks.
