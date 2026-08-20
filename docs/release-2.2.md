# Release 2.2 implementation plan

Generated from the open 2.2.0 milestone issues on 2026-08-20.

## Scope

Release 2.2.0 implements per-directory document ingestion.  It also
hardens the URL, metadata, and `IndexByDate.txt` paths needed to ingest
directories without creating broken or duplicate copies.

Issues:

- #46 Allow the user to review extracted metadata from the PDF file in
  the URL wizard.
- #66 Documents with `#` in file name have wrong URL.
- #67 URLs with special characters don't get MD5 computed properly.
- #68 URLs with special characters aren't checked for existence
  properly.
- #69 URL Wizard doesn't recognize document dates with day.
- #73 If site URL and copy base URL differ, wizard produces incorrect
  URLs.
- #98 Associate directories with a regex for part numbers to aid ingestion.
- #105 Preview ingested metadata for a directory.
- #106 Allow all unknown documents in a directory to be manually ingested.
- #124 Extract PDF metadata via cron.
- #135 `https` URLs aren't recognized properly.
- #145 Checking for moved files with many unknown paths is very slow.
- #154 Use the `IndexByDate.txt` file to perform existence and moved
  checks more quickly.

Implement one numbered slice at a time.  Each numbered slice fixes one
issue.  When a slice is complete, remove that slice and leave the
remaining numbers unchanged.

## 1. Review PDF metadata in URL Wizard

Issue: #46

Implementation:

- Extract PDF metadata into a service that the URL Wizard can call.
- Populate URL Wizard defaults from PDF title, keywords, abstract, copy
  notes, and credits.
- Keep user-entered values authoritative when the form is submitted.

Acceptance criteria:

- Given a PDF URL with metadata, the wizard shows extracted metadata
  before save.
- Given manual edits, saving the wizard stores the edited values rather
  than re-extracted values.
- Given a PDF with no usable metadata, existing wizard behavior is
  unchanged.

Automated tests:

- Add `UrlWizardPageTest` coverage for rendered PDF metadata fields.
- Add `UrlWizardPageTest` coverage proving manual edits win on submit.
- Add metadata parser tests for populated and empty PDF metadata.

Fixes #46

## 2. Encode `#` in document paths

Issue: #66

Implementation:

- Encode `#` and other unsafe characters in any path segment before
  rendering document links.
- Keep stored copy URLs stable unless a save path explicitly updates
  them.

Acceptance criteria:

- A path containing `#` in a directory or file name renders a usable
  `%23` link.
- The readable link text still shows the original path text.
- Existing already-encoded URLs are not double encoded.

Automated tests:

- Add `WhatsNewPageTest` or link-formatting coverage for `#` in a
  directory segment and in a file segment.
- Add `DetailsPageTest` coverage for copy links containing `#` in any
  path segment.
- Add a regression case for an already encoded `%23` path.

Fixes #66

## 3. Compute MD5 for URLs with special characters

Issue: #67

Implementation:

- Normalize unsafe characters before fetching a copy for MD5.
- Do not double encode existing `%xx` escapes while normalizing.
- Keep the database update tied to the original copy row.

Acceptance criteria:

- A copy URL containing `#` is fetched with an encoded URL.
- A copy URL already containing `%23` is fetched without becoming
  `%2523`.
- A successful fetch stores the computed MD5 on the original copy.
- A failed fetch clears or preserves MD5 exactly as existing failure
  rules require.

Automated tests:

- Add `WhatsNewCleanerTest` coverage for MD5 fetch with `#`.
- Add `WhatsNewCleanerTest` coverage for an already encoded `%23` path.
- Add `UrlInfoTest` or `UrlTransferTest` coverage for encoded fetch
  input.
- Add a regression test proving spaces still work.

Fixes #67

## 4. Normalize copy URLs before duplicate checks

Issue: #68

Implementation:

- Make no table or column changes for this issue.
- Add one-off migration logic that normalizes existing copy URLs into the
  canonical encoded form.
- Detect normalized URL collisions before updating rows.
- Leave no stored normalization procedure behind after the migration.
- Make copy lookup normalize input and query the canonical URL form.

Acceptance criteria:

- No new persistent table, column, index, trigger, function, or procedure
  remains after the migration.
- After migration, decoded path characters in `copy.url` are encoded.
- Already encoded URLs are not double encoded.
- Collisions between encoded and decoded forms are reported before data
  changes are applied.
- The migration does not leave a URL-normalization procedure installed.
- Copy lookup finds existing rows by canonical URL only.
- New duplicate copies cannot be inserted using decoded path variants.

Automated tests:

- Add a migration test proving no persistent schema object is added.
- Add migration tests for decoded, encoded, and collision cases.
- Add a migration test proving no normalization procedure remains.
- Add an idempotence test for running the migration twice.
- Add `ManxDatabaseTest` coverage for canonical-only copy lookup.
- Add an ingestion duplicate-prevention regression test.

Fixes #68

## 5. Parse URL Wizard dates that include a day

Issue: #69

Implementation:

- Extend publication-date extraction for compact day-month-year forms.
- Preserve all existing year and month-year date extraction behavior.

Acceptance criteria:

- `1Jul1989` extracts `1989-07-01`.
- Existing `Jul1989`, `Jul89`, and year-only forms still parse.
- Invalid day or month text is left in the title, as before.

Automated tests:

- Add `UrlMetaDataHelpersTest` cases for `1Jul1989` and `01Jul1989`.
- Add `UrlMetaDataHelpersTest` cases for invalid day-bearing dates.
- Keep existing date parser tests passing.

Fixes #69

## 6. Use site URL when copy base URL differs

Issue: #73

Implementation:

- Preserve the configured site URL for wizard lookup and copy links.
- Use copy base URL only when constructing the stored copy URL.
- Keep mirror selection consistent with the recognized base site.

Acceptance criteria:

- A site with different `site_url` and `copy_base` produces a valid copy
  link.
- Wizard lookup keeps the original document URL unchanged.
- Mirror choices are based on the recognized site.

Automated tests:

- Add `UrlWizardServiceTest` coverage for differing site URL and copy
  base URL.
- Add `UrlMetaDataTest` coverage for mirror selection in this case.
- Add `ManxDatabaseTest` coverage for copy URL construction if needed.

Fixes #73

## 7. Associate directory regex with ingestion

Issue: #98

Implementation:

- Store a part-number regex on each unknown directory.
- In the release migration, alter only the existing column default:

```sql
ALTER TABLE `site_unknown_dir`
  ALTER COLUMN `part_regex`
  SET DEFAULT '^([^_]*[0-9][0-9][^_]*)_';
```

- In the release migration, backfill existing rows with a one-off
  procedure that is dropped after it runs:

```sql
CREATE PROCEDURE `manx_backfill_site_unknown_dir_part_regex`()
BEGIN
  UPDATE `site_unknown_dir`
  SET `part_regex` = '^([^_]*[0-9][0-9][^_]*)_'
  WHERE `part_regex` = '';
END;
CALL `manx_backfill_site_unknown_dir_part_regex`();
DROP PROCEDURE `manx_backfill_site_unknown_dir_part_regex`;
```

- Render and save the regex from `whatsnew.php` directory pages.
- Apply the regex to the date-stripped filename base before automatic
  ingestion accepts a row.
- Treat capture group 1 as the candidate part number, matching the URL
  metadata extractor's leading-token behavior.

Acceptance criteria:

- A logged-in user can view, edit, clear, and save a directory regex.
- New `site_unknown_dir` rows get the default regex from the column
  definition.
- Upgraded databases have existing `site_unknown_dir.part_regex` values
  backfilled by the migration procedure.
- New directories use a default regex that captures the leading
  underscore-delimited token only when it contains two consecutive digits.
- Matching rows are eligible for ingestion.
- Non-matching rows remain visible for review.
- Invalid regex input is rejected with a useful validation error.

Automated tests:

- Add `ManxDatabaseTest` coverage for reading and saving directory regex.
- Add schema migration tests for the `part_regex` column default.
- Add migration procedure tests for backfilling existing rows.
- Add `WhatsNewPageTest` coverage for regex rendering and POST handling.
- Add tests proving the default regex captures the same part number as
  `UrlMetaData::extractPartNumber()` for underscore-delimited filenames.
- Add `WhatsNewCleanerTest` coverage for match, no-match, empty, and
  invalid regex ingestion cases.

Fixes #98

## 8. Preview directory ingestion metadata

Issue: #105

Implementation:

- Build a preview model for unknown files in the current directory.
- Skip preview generation entirely when the viewed directory has no known
  company association.
- Exclude files that would have their ignore checkbox set by default.
- Render the preview table before the existing bullet lists of files and
  subdirectories.
- Show extracted part number, date, title, format, regex result, matching
  publication, and existing-copy state.
- Render preview data without mutating database state.

Acceptance criteria:

- Directory pages show one preview row per unignored unknown file.
- Files that would have the ignore checkbox set by default do not appear
  in the preview table.
- Directories with no known company association do not show a preview
  table or run preview metadata extraction.
- The preview table appears before the file and subdirectory bullet
  lists.
- Each row identifies accepted, rejected, duplicate, and uncertain states.
- Reloading the preview does not mark paths scanned or add copies.

Automated tests:

- Add preview model tests for extracted metadata and row status.
- Add preview model tests proving default-ignored files are excluded.
- Add preview model or page tests proving no preview work runs without a
  directory company association.
- Add `WhatsNewPageTest` coverage for rendering preview rows.
- Add `WhatsNewPageTest` coverage for preview placement before the file
  and subdirectory bullet lists.
- Add `ManxDatabaseTest` or mock assertions proving preview is read-only.

Fixes #105

## 9. Manually ingest all documents in a directory

Issue: #106

Implementation:

- Add a directory ingest form to the metadata preview page.
- Add one checkbox per preview row for selecting documents to ingest.
- Add a submit button that immediately ingests selected rows.
- Do not provide metadata editing in the directory ingest form.
- Recompute selected preview rows on submit before ingesting them.
- Leave unselected rows for one-by-one ingestion or later review.
- Add copies for selected rows that still pass validation.

Acceptance criteria:

- Each preview row has a checkbox controlling whether that row is
  ingested.
- Submitting the form immediately ingests selected valid rows.
- Submitted ingestion does not trust hidden metadata values.
- The form does not contain editable metadata fields.
- Valid selected rows create publications or copies according to existing
  rules.
- Ingested unknown paths are removed or marked scanned.
- Unselected rows remain visible for later one-by-one ingestion.
- Selected rows that fail validation remain visible with a reason.

Automated tests:

- Add `WhatsNewPageTest` POST coverage proving preview is recomputed.
- Add `WhatsNewPageTest` coverage for one checkbox per preview row and a
  submit button.
- Add `WhatsNewPageTest` coverage proving metadata fields are not
  editable in the ingest form.
- Add `WhatsNewCleanerTest` or ingestion service tests for selected valid
  rows.
- Add tests proving unselected, duplicate, regex-rejected, and invalid
  rows are not ingested.

Fixes #106

## 10. Extract PDF metadata via cron

Issue: #124

Implementation:

- In the base schema, add these columns to `site_unknown`:

```sql
`pdf_title` VARCHAR(255) NOT NULL DEFAULT '',
`pdf_keywords` VARCHAR(100) NOT NULL DEFAULT '',
`pdf_abstract` VARCHAR(2048) NOT NULL DEFAULT '',
`pdf_notes` VARCHAR(200) NOT NULL DEFAULT '',
`pdf_credits` VARCHAR(200) NOT NULL DEFAULT '',
`pdf_metadata_status` VARCHAR(16) NOT NULL DEFAULT '',
`pdf_metadata_error` VARCHAR(255) NOT NULL DEFAULT '',
`pdf_metadata_checked` DATETIME NULL DEFAULT NULL
```

- In the release migration, add those columns:

```sql
ALTER TABLE `site_unknown`
  ADD COLUMN `pdf_title` VARCHAR(255) NOT NULL DEFAULT '',
  ADD COLUMN `pdf_keywords` VARCHAR(100) NOT NULL DEFAULT '',
  ADD COLUMN `pdf_abstract` VARCHAR(2048) NOT NULL DEFAULT '',
  ADD COLUMN `pdf_notes` VARCHAR(200) NOT NULL DEFAULT '',
  ADD COLUMN `pdf_credits` VARCHAR(200) NOT NULL DEFAULT '',
  ADD COLUMN `pdf_metadata_status` VARCHAR(16) NOT NULL DEFAULT '',
  ADD COLUMN `pdf_metadata_error` VARCHAR(255) NOT NULL DEFAULT '',
  ADD COLUMN `pdf_metadata_checked` DATETIME NULL DEFAULT NULL;
```

- Column semantics:
  - `pdf_title` stores the cached PDF title, or `''` when none is
    available.
  - `pdf_keywords` stores cached PDF keywords, or `''` when none are
    available.
  - `pdf_abstract` stores cached PDF abstract or subject text, or `''`
    when none is available.
  - `pdf_notes` stores cached copy notes derived from PDF metadata, or
    `''` when none are available.
  - `pdf_credits` stores cached author, creator, or producer credit text,
    or `''` when none is available.
  - `pdf_metadata_status` stores the cache state: `''` means unchecked,
    `ok` means checked with at least one useful metadata field, `none`
    means checked successfully with no useful metadata, and `error`
    means the last check failed.
  - `pdf_metadata_error` stores the last failure message when
    `pdf_metadata_status` is `error`; otherwise it is `''`.
  - `pdf_metadata_checked` stores the time of the last metadata check, or
    `NULL` when the row has never been checked.
- Add a cron path that fetches metadata for unknown PDF paths.
- Give the cron command a `--time-limit-seconds` option.  The default is
  `1800`, and the command stops starting new PDF fetches after the
  elapsed wall-clock time reaches that limit.
- Cache extracted metadata with the unknown path.
- Record fetch and parse failures without stopping the cron run.

Acceptance criteria:

- Upgraded databases expose all PDF metadata cache columns on
  `site_unknown`.
- Unknown PDF paths receive cached title and related metadata.
- Non-PDF unknown paths are ignored.
- Failed PDF metadata fetches are recorded and retried according to the
  chosen retry rule.
- The cron metadata command defaults to a 30 minute run limit.
- The cron metadata command accepts a command-line override for the run
  limit.
- When the run limit is reached, the cron command exits cleanly without
  starting another PDF fetch.

Automated tests:

- Add schema migration tests for all PDF metadata cache columns.
- Add `WhatsNewProcessorTest` coverage for the cron command dispatch.
- Add `WhatsNewProcessorTest` coverage for the default and overridden
  cron metadata time limit.
- Add `WhatsNewCleanerTest` coverage for success, non-PDF, and failure.
- Add `WhatsNewCleanerTest` coverage proving the time limit prevents
  starting another PDF fetch.
- Add `ManxDatabaseTest` coverage for storing cached PDF metadata.

Fixes #124

## 11. Recognize `https` URLs correctly

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

## 12. Speed moved-file checks for many unknown paths

Issue: #145

Implementation:

- In the base schema, add this column and lookup key to `copy`:

```sql
`file_name` VARCHAR(255) NOT NULL DEFAULT '',
KEY `site_file_sud` (`site`, `file_name`, `sud_id`)
```

- In the release migration, add and backfill the column:

```sql
ALTER TABLE `copy`
  ADD COLUMN `file_name` VARCHAR(255) NOT NULL DEFAULT '',
  ADD KEY `site_file_sud` (`site`, `file_name`, `sud_id`);

UPDATE `copy`
SET `file_name` = SUBSTRING_INDEX(`url`, '/', -1)
WHERE `file_name` = '';
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

## 13. Use IndexByDate for existence and moved checks

Issue: #154

Implementation:

- Make no base schema or release migration changes for this issue.
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

- No persistent table for `IndexByDate.txt` is added by the base schema
  or release migration.
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
