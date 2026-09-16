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
