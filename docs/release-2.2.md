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

## Slice 1: Edit directory-ingestion preview metadata

Issue: #106 follow-up.

Schema changes: none.

Add an initially collapsed editor under each ingestion preview row so a
nearly-correct row can be adjusted before submitting the directory
ingestion form.  Keep the main preview row compact with the existing
`Ingest?`, `File`, and `Status` columns.  The expanded area contains
editable fields for part number, publication title, and publication date,
plus a direct source-document link.

Implementation:

- Render the expanded editor as an additional row below each preview row,
  keyed by the `site_unknown.id` value so submitted edits cannot be
  confused when rows are filtered or reordered.
- Pre-fill the editor fields from the extracted preview values.  Omit
  empty extracted values from the compact metadata display, but show empty
  editable inputs in the expanded editor.
- Preserve the existing checkbox eligibility rule: only rows whose current
  status is `Accepted`, `New`, or `Uncertain` can be selected for bulk
  ingestion.
- On submit, apply posted editor values only to selected rows.  Trim the
  edited part number, title, and date before use.
- Re-evaluate publication matching and final row status on the server from
  the edited values before ingesting.  Do not trust the status or enabled
  checkbox state from the browser.
- If the edited row re-evaluates to `Accepted`, add the copy to the exact
  matching publication.  If it re-evaluates to `New` or `Uncertain`, create
  the publication with the edited part number, title, and date, then add
  the copy.  If it re-evaluates to `Rejected` or `Duplicate`, leave the
  unknown path in place.
- Keep the current `Check All` and `Uncheck All` behavior: those buttons
  change only enabled checkboxes.

Acceptance criteria:

- Every preview row has an expandable editor with part number, title, date,
  and source-document link controls.
- The preview table remains narrow enough for the existing mobile layout;
  the edit controls appear only inside the expanded area.
- Edited values are used for both publication matching and publication
  creation during bulk ingestion.
- Rows that cannot be ingested after server-side re-evaluation are not
  inserted and are not removed from `site_unknown`.
- No schema objects or persistent metadata override records are added.

Automated tests:

- `WhatsNewPageTest` renders the collapsed editor for preview rows with
  escaped field values, stable field names keyed by `site_unknown.id`, and
  a direct source-document link.
- `WhatsNewPageTest` submits an eligible row with edited part, title, and
  date and verifies that a new publication is created with the edited
  values before the copy is added.
- `WhatsNewPageTest` submits an eligible row whose edited part number
  matches exactly one existing publication and verifies that no new
  publication is created and the copy is added to that publication.
- `WhatsNewPageTest` submits an edited selected row that re-evaluates to
  `Rejected` or `Duplicate` and verifies that no copy is added and the
  unknown path is not removed.
- `WhatsNewPageTest` verifies that `Check All` and `Uncheck All` still
  leave disabled checkboxes unchanged.

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
