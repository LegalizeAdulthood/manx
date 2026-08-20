# Release 2.1 implementation plan

Generated from the open 2.1.0 milestone issues on 2026-08-20.

## Scope

Ship the outstanding milestone issues:

- #163 Recognize YYYYMM[DD] dates in bitsavers URLs
- #121 Perform keyword search on bitsavers IndexByDate.txt
- #120 Documents are not removed from the WhatsNew list

Implement one numbered slice at a time.  When a slice is complete, remove
that slice and leave the remaining numbers unchanged.

## 4. Parse packed Bitsavers dates (#163)

`UrlMetaData::extractPubDate()` handles month-name forms, but not packed
numeric suffixes now used by Bitsavers.

Implementation:

- Extend `UrlMetaData::extractPubDate()` to recognize `YYYYMM` and
  `YYYYMMDD` suffixes in the filename base.
- Validate month and day values before accepting a match.
- Strip the date suffix from the returned title base.
- Keep existing month-name and separated date behavior unchanged.

Verification:

- Add `UrlMetaDataTest` cases for `Name_202603` and `Name_20260307`.
- Add rejection cases for invalid months and invalid days.
- Add at least one full Bitsavers URL metadata test using the new form.
- `composer test`

## 5. Remove processed WhatsNew entries (#120)

`UrlWizardPage::postPage()` has a cleanup branch for `site_unknown_id`,
but the issue reports that a processed row remains visible.  Treat this
as a flow bug until a regression test proves the exact fault.

Implementation:

- Reproduce the WhatsNew-to-wizard flow with an `id` query parameter.
- Verify the hidden `site_unknown_id` field survives the GET-to-POST
  wizard flow.
- Ensure successful wizard completion calls `setCopySiteUnknownDirId()`,
  `updateIgnoredUnknownSingleDir()`, and `removeSiteUnknownPathById()`
  with the site unknown row id.
- If cleanup is skipped because the id is missing, fix the form flow.
- If cleanup runs but the row remains, fix the delete or directory state
  update path in `ManxDatabase`.
- Do not remove the WhatsNew row when validation or copy creation fails.

Verification:

- Add `UrlWizardPageTest` coverage for successful cleanup.
- Add coverage that cleanup is not attempted without `site_unknown_id`.
- Confirm the parent WhatsNew directory recomputes ignored state.
- `composer test`

## 6. Search Bitsavers IndexByDate (#121)

Normal search only returns catalog publications.  Bitsavers
`IndexByDate.txt` is already parsed into `site_unknown` and
`site_unknown_dir`, but those rows are not searched from the search page.

Implementation:

- Add a database query for Bitsavers unknown paths matching search words.
- Reuse `Searcher::filterSearchKeywords()` so ignored words match normal
  search behavior.
- Narrow by the selected company through `site_company_dir` associations
  when any exist for that company.
- Support multiple associated Bitsavers directories for the same company.
- Deduplicate any unknown path that already has a known copy.
- Render supplemental Bitsavers path results after catalog results.
- Link supplemental results to the Bitsavers document URL, and to the URL
  wizard for logged-in admins if the current page already has admin
  context.

Verification:

- Add database tests for keyword matching, company narrowing, multiple
  directories, and known-copy deduplication.
- Add search rendering coverage for supplemental Bitsavers results.
- Manually test public search and admin search for a company with known
  Bitsavers directory associations.
- `composer test`

## 7. Release verification

- Run the full test suite with `composer test`.
- Check a GitHub Actions run for the branch.
- Apply schema migrations to a scratch database from an empty schema.
- Apply schema migrations to a production-shaped database copy.
- Smoke test login timeout recovery from a WhatsNew subdirectory.
- Smoke test URL wizard completion from a WhatsNew file.
- Smoke test Bitsavers search results for a company with and without
  directory associations.
