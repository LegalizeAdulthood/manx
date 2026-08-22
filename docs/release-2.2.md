# Release 2.2 implementation plan

Generated from the open 2.2.0 milestone issues on 2026-08-20.

## Schema Guidance

All release 2.2 schema changes go into `schema/9-schema.sql`.  Do not
edit earlier schema files for this release.

The final statement in `schema/9-schema.sql` sets the version property to
`2.2.0`, after all DDL and migration cleanup have completed.

Migrate data using one-off procedures.  Drop each procedure as soon as
its data migration has run.

Do not create persistent relationships between known-copy rows and
unknown-path rows or directories.  Keep copy lookup caches limited to
copy data derived from `copy.url`.

## Scope

Release 2.2.0 implements per-directory document ingestion.  It also
hardens the URL, metadata, and `IndexByDate.txt` paths needed to ingest
directories without creating broken or duplicate copies.

Issues:

- #99 Make the site render naturally on mobile devices.
- #106 Allow all unknown documents in a directory to be manually ingested.
- #124 Extract PDF metadata via cron.
- #135 `https` URLs aren't recognized properly.
- #154 Use the `IndexByDate.txt` file to perform existence and moved
  checks more quickly.

Implement one numbered slice at a time.  Each numbered slice fixes one
issue, except #99 which is split into small mobile-layout slices.  When a
slice is complete, remove that slice and leave the remaining numbers
unchanged.

The mobile slices are listed first so responsive changes get the longest
manual testing window.

# Implementation

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
  `filename` VARCHAR(255) NOT NULL DEFAULT '',
  `index_date` DATE NULL DEFAULT NULL,
  UNIQUE KEY `site_path` (`site_id`, `path`),
  KEY `site_dir_filename` (`site_id`, `dir_path`(128), `filename`(128))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

- Table column semantics:
  - `site_id` identifies the source site for the indexed path.
  - `path` stores the full site-relative path from `IndexByDate.txt`.
  - `dir_path` stores the directory portion of `path`, or `''` for the
    site root.
  - `filename` stores the basename portion of `path` as it exists on
    disk, with no URL special-character encodings.  For example, an
    index path ending in `file%20%231.pdf` stores `file #1.pdf`.
  - `index_date` stores the parsed index date, or `NULL` when no valid
    date is available.
- Load parsed `IndexByDate.txt` rows into `tmp_site_index_by_date`.
- Find removed copy candidates with a set query for known copies whose
  site-relative paths are absent from `tmp_site_index_by_date`.
- Find moved copy candidates with a set query that joins known copies to
  `tmp_site_index_by_date` by `site_id` and `filename`, then compares
  the current copy directory derived from `copy.url` and `site.copy_base`
  to `dir_path`.
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
- Temporary index rows store decoded on-disk filenames in `filename`,
  matching `copy.filename` from the 2.1 schema.
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
- Add `WhatsNewIndexTest` coverage proving encoded filenames from
  `IndexByDate.txt`, such as `file%20%231.pdf`, are loaded into
  `filename` as `file #1.pdf`.
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
