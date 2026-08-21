# Release 2.2 implementation plan

Generated from the open 2.2.0 milestone issues on 2026-08-20.

## Schema Guidance

All release 2.2 schema changes go into `schema/9-schema.sql`.  Do not
edit earlier schema files for this release.

The final statement in `schema/9-schema.sql` sets the version property to
`2.2.0`, after all DDL and migration cleanup have completed.

Migrate data using one-off procedures.  Drop each procedure as soon as
its data migration has run.

## Scope

Release 2.2.0 implements per-directory document ingestion.  It also
hardens the URL, metadata, and `IndexByDate.txt` paths needed to ingest
directories without creating broken or duplicate copies.

Issues:

- #99 Make the site render naturally on mobile devices.
- #106 Allow all unknown documents in a directory to be manually ingested.
- #124 Extract PDF metadata via cron.
- #135 `https` URLs aren't recognized properly.
- #145 Checking for moved files with many unknown paths is very slow.
- #154 Use the `IndexByDate.txt` file to perform existence and moved
  checks more quickly.

Implement one numbered slice at a time.  Each numbered slice fixes one
issue, except #99 which is split into small mobile-layout slices.  When a
slice is complete, remove that slice and leave the remaining numbers
unchanged.

The mobile slices are listed first so responsive changes get the longest
manual testing window.

# Implementation

## 14. Use cached PDF metadata during individual ingestion

Issue: #124

Implementation:

- Add a database read method that returns cached PDF metadata for a
  `site_unknown.id`.
- In the URL Wizard page opened from `whatsnew.php` for an individual
  unknown path, load cached PDF metadata for that unknown path.
- When cached PDF metadata exists with status `ok`, populate the existing
  PDF metadata results block from the cached values.
- When cached PDF metadata is shown, omit the button that fetches PDF
  metadata through the AJAX endpoint.
- Keep the button that copies displayed PDF metadata into editable form
  fields.
- Do not automatically copy cached metadata into editable form fields.
- When cached PDF metadata does not exist, keep the existing AJAX fetch
  button behavior.
- No schema changes are needed for this slice.

Acceptance criteria:

- Opening an unknown PDF path with cached PDF metadata status `ok` shows
  the populated PDF metadata results block.
- The AJAX fetch button is omitted when cached PDF metadata already
  exists.
- The copy-to-form button remains available when cached PDF metadata is
  displayed.
- Opening an unknown PDF path without cached PDF metadata still shows the
  AJAX fetch button.
- Cached title, keywords, abstract, copy notes, and copy credits are
  displayed with HTML escaping.
- Displaying cached PDF metadata does not require an AJAX request or a
  server-side PDF download.

Automated tests:

- Add `ManxDatabaseTest` coverage for cached PDF metadata lookup by
  `site_unknown.id`.
- Add `UrlWizardPageTest` coverage proving cached PDF metadata populates
  the results block for an individual unknown path.
- Add `UrlWizardPageTest` coverage proving the AJAX fetch button is
  omitted when cached PDF metadata exists.
- Add `UrlWizardPageTest` coverage proving the copy-to-form button is
  still rendered with cached PDF metadata.
- Add `UrlWizardPageTest` coverage proving missing cached metadata keeps
  the AJAX fetch button.
- Add page or script coverage proving the copy-to-form button maps cached
  metadata fields into the editable form fields.

Fixes #124

## 15. Recognize `https` URLs correctly

Issue: #135

Implementation:

- Treat `https` archive URLs as matching their configured base site.
- Preserve mirror ranking and mirror rendering for `https` URLs.

Acceptance criteria:

- `https://bitsavers.org/...` recognizes the Bitsavers site.
- Mirrors are listed for the recognized site.
- Existing `http` URL recognition remains unchanged.

Automated tests:

- Add `UrlMetaDataHelpersTest` cases for `https` base-site matching.
- Add `UrlMetaDataTest` coverage for `https` mirror results.
- Add `UrlWizardServiceTest` coverage for `https` URL lookup.

Fixes #135

## 16. Speed moved-file checks for many unknown paths

Issue: #145

Implementation:

- In `schema/9-schema.sql`, add `copy.file_name` and the
  `site_file_sud` lookup key:

```sql
ALTER TABLE `copy`
  ADD COLUMN `file_name` VARCHAR(255) NOT NULL DEFAULT '',
  ADD KEY `site_file_sud` (`site`, `file_name`, `sud_id`);
```

- In `schema/9-schema.sql`, backfill existing copy rows with a one-off
  procedure that is dropped after it runs:

```sql
CREATE PROCEDURE `manx_backfill_copy_file_name`()
BEGIN
UPDATE `copy`
SET `file_name` = SUBSTRING_INDEX(`url`, '/', -1)
WHERE `file_name` = '';
END;
CALL `manx_backfill_copy_file_name`();
DROP PROCEDURE `manx_backfill_copy_file_name`;
```

- Column semantics:
  - `copy.file_name` stores the basename portion of `copy.url`.
  - `copy.file_name` is a derived lookup cache, not user-authored data.
- Populate `copy.file_name` in `ManxDatabase::addCopy()` from the copy
  URL basename.
- Update `copy.file_name` in `ManxDatabase::siteFileMoved()` when the
  copy URL is changed.
- Change `getPossiblyMovedSiteUnknownPaths()` to join candidates with
  `c.site = su.site_id`, `c.file_name = su.path`, `c.md5 <> ''`, and
  `c.size > 0`.
- Filter same-directory rows in SQL with
  `c.sud_id = -1 OR c.sud_id <> su.dir_id`.
- Return the candidate new URL from the query and skip unchanged URLs in
  PHP before any HEAD, size, or MD5 request.
- Remove `SUBSTRING_INDEX(c.url, '/', -1)` and `CONCAT(...)` from the
  moved-file query's `JOIN` and `WHERE` predicates.

Acceptance criteria:

- The moved-file check returns the same candidates as before.
- Existing copy rows have `copy.file_name` backfilled from `copy.url`.
- Newly inserted copies store the basename of `copy.url` in
  `copy.file_name`.
- Moved copies update both `copy.url` and `copy.file_name`.
- The moved-candidate query uses the `site_file_sud` key.
- The moved-candidate query has no computed expressions on `copy.url` in
  its `JOIN` or `WHERE` predicates.
- Rows whose current URL already equals the candidate URL are skipped
  before any network request.
- Moved copies still update URL and unknown-path state correctly.

Automated tests:

- Add schema migration tests for `copy.file_name`, `site_file_sud`, and
  the backfill.
- Add `ManxDatabaseTest` coverage proving `addCopy()` stores
  `copy.file_name`.
- Add `ManxDatabaseTest` coverage proving `siteFileMoved()` updates
  `copy.url`, `copy.file_name`, and unknown-path state.
- Add `ManxDatabaseTest` coverage for moved-candidate lookup by
  `copy.file_name`.
- Add a query regression test proving the moved-candidate SQL does not
  use `SUBSTRING_INDEX(c.url, '/', -1)` or `CONCAT(...)` in `JOIN` or
  `WHERE`.
- Add `WhatsNewCleanerTest` coverage proving unchanged candidate URLs do
  not trigger HEAD, size, or MD5 requests.
- Add `WhatsNewCleanerTest` coverage for processing real moved
  candidates.

Fixes #145

## 17. Use IndexByDate for existence and moved checks

Issue: #154

Implementation:

- Make no persistent schema changes for this issue.
- During each cron run, create this temporary table on the current
  database connection:

```sql
CREATE TEMPORARY TABLE `tmp_site_index_by_date` (
  `site_id` INT(11) NOT NULL,
  `path` VARCHAR(255) NOT NULL,
  `dir_path` VARCHAR(255) NOT NULL DEFAULT '',
  `file_name` VARCHAR(255) NOT NULL DEFAULT '',
  `index_date` DATE NULL DEFAULT NULL,
  UNIQUE KEY `site_path` (`site_id`, `path`),
  KEY `site_dir_file` (`site_id`, `dir_path`(128), `file_name`(128))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

- Table column semantics:
  - `site_id` identifies the source site for the indexed path.
  - `path` stores the full site-relative path from `IndexByDate.txt`.
  - `dir_path` stores the directory portion of `path`, or `''` for the
    site root.
  - `file_name` stores the basename portion of `path`.
  - `index_date` stores the parsed index date, or `NULL` when no valid
    date is available.
- Load parsed `IndexByDate.txt` rows into `tmp_site_index_by_date`.
- Find removed copy candidates with a set query for known copies whose
  site-relative paths are absent from `tmp_site_index_by_date`.
- Find moved copy candidates with a set query that joins known copies to
  `tmp_site_index_by_date` by `site_id` and `file_name`, then compares
  the current copy directory to `dir_path`.
- Limit HEAD and MD5 checks to candidates that need confirmation.
- Drop `tmp_site_index_by_date` at the end of the cron run.  If the
  process exits first, rely on MySQL temporary-table cleanup when the
  connection closes.

Acceptance criteria:

- No persistent table for `IndexByDate.txt` is added to
  `schema/9-schema.sql`.
- The cron job creates `tmp_site_index_by_date` for the duration of the
  run.
- The temporary table uses the columns, unique key, lookup key, and
  `InnoDB` engine listed above.
- A copy missing from the index is marked as a removal candidate.
- A copy present under a different directory is marked as moved.
- Unchanged copies do not trigger HTTP checks.
- Candidate rows can still be verified with HEAD or MD5 before mutation.
- The temporary table is dropped before a normal cron exit.

Automated tests:

- Add a schema migration test proving no persistent `IndexByDate.txt`
  cache table is added.
- Add `WhatsNewIndexTest` coverage for creating, loading, and dropping
  `tmp_site_index_by_date`.
- Add `ManxDatabaseTest` coverage for removed and moved index queries.
- Add `WhatsNewCleanerTest` coverage proving only candidates are checked.

Fixes #154

## Release verification

- Run the full test suite with `composer test`.
- Apply schema migrations to a production-shaped database copy.
- Fetch the VTDA `IndexByDate.txt` into a staging database.
- Fetch Bitsavers index files for `pdf`, `components`, `communications`,
  and `test_equipment`.
- Smoke test URL Wizard special-character and `https` handling.
- Smoke test URL Wizard PDF metadata review.
- Smoke test cron PDF metadata prefetch.
- Smoke test regex editing on a Bitsavers directory.
- Smoke test automatic ingestion with matching and non-matching regexes.
- Smoke test metadata preview on Bitsavers and VTDA directories.
- Smoke test moved-file detection without per-copy HTTP checks.
- Smoke test manual directory ingestion and browser refresh behavior.
- Smoke test public pages at 320px and 375px mobile widths.
- Smoke test URL Wizard and admin forms at 320px and 375px mobile widths.
