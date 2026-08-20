# Release 2.2 implementation plan

Generated from the open 2.2.0 milestone issues on 2026-08-20.

## Scope

Release 2.2.0 implements per-directory document ingestion.

Issues:

- #98 Associate directories with a regex for part numbers to aid ingestion.
- #105 Preview ingested metadata for a directory.
- #106 Allow all unknown documents in a directory to be manually ingested.

Implement one numbered slice at a time.  When a slice is complete, remove
that slice and leave the remaining numbers unchanged.

## 1. Directory part-number regex editing

- Add database methods and tests for updating the directory part regex.
- Render the current directory regex on `whatsnew.php` directory pages.
- Add a small POST path to save the regex for the current directory.
- Preserve the existing ignore-path POST behavior.

Refs #98

## 2. Regex-gated automatic ingestion

- Include each unknown path's directory regex in automatic ingestion rows.
- Apply a non-empty regex before adding a copy.
- Leave regex-skipped rows visible for review or regex changes.
- Cover matching, non-matching, invalid, and empty regex cases.

Refs #98

## 3. Directory metadata preview

- Build a preview model for files in the current unknown directory.
- Show extracted part number, publication date, title, and format per file.
- Show whether the directory regex accepts the extracted part number.
- Show whether a matching publication or existing copy was found.
- Avoid mutating database state while rendering the preview.

Refs #105

## 4. Manual directory ingestion

- Add an ingest form to the directory preview page.
- Recompute preview data on submit rather than trusting hidden metadata.
- Add copies for preview rows that pass validation and regex checks.
- Remove ingested unknown paths and update unknown-directory ignore state.
- Report skipped rows with enough detail to curate the directory regex.

Refs #106

## 5. Release verification

- Run the full test suite with `composer test`.
- Apply schema migrations to a production-shaped database copy.
- Fetch the VTDA `IndexByDate.txt` into a staging database.
- Smoke test regex editing on a Bitsavers directory.
- Smoke test automatic ingestion with matching and non-matching regexes.
- Smoke test metadata preview on Bitsavers and VTDA directories.
- Smoke test manual directory ingestion and browser refresh behavior.
