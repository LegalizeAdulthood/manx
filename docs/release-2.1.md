# Release 2.1 implementation plan

Generated from the open 2.1.0 milestone issues on 2026-08-20.

## Scope

Ship the outstanding milestone issues:

- #121 Perform keyword search on bitsavers IndexByDate.txt

Implement one numbered slice at a time.  When a slice is complete, remove
that slice and leave the remaining numbers unchanged.

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
