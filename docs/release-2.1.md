# Release 2.1 implementation plan

Generated from the open 2.1.0 milestone issues on 2026-08-20.

## Scope

Ship the outstanding milestone issues:

- #165 Schema change to make transaction-wrapped updates atomic
- #163 Recognize YYYYMM[DD] dates in bitsavers URLs
- #142 Run unit tests in github actions
- #121 Perform keyword search on bitsavers IndexByDate.txt
- #120 Documents are not removed from the WhatsNew list
- #103 Login from WhatsNew does not preserve query params

Implement one numbered slice at a time.  When a slice is complete, remove
that slice and leave the remaining numbers unchanged.

## 1. CI baseline (#142)

The issue body says the workflow did not run tests and the README badge
needed to point at GitHub Actions.  The current checkout already has
`.github/workflows/php.yml` running the Composer test script, and
`README.md` already points at `actions/workflows/php.yml`.

Implementation:

- Check the latest `PHP Composer` Actions runs for `develop` and PRs.
- If tests are not running, update the workflow to run `composer test`.
- Refresh workflow actions only where required by current GitHub support.
- Keep the README badge pointed at `actions/workflows/php.yml`.

Verification:

- `composer validate --strict`
- `composer test`
- A current GitHub Actions run shows the test step executing.

## 2. Transaction-safe schema (#165)

The schema files create MyISAM tables, so PDO transaction calls do not
make multi-statement updates atomic.  The issue comment says to change
the tables to InnoDB.

Implementation:

- Add `schema/9-schema.sql` to convert all application tables to InnoDB.
- Update fresh-install schema definitions, or otherwise prove that
  applying all numbered schema files leaves no MyISAM tables behind.
- Audit indexes, full-text usage, and stored procedures after conversion.
- Document the production path: backup, maintenance window, migration,
  post-migration engine check, and rollback from backup.
- Verify `beginTransaction()` and `commit()` call sites in
  `public/pages/ManxDatabase.php` now use transactional tables.

Verification:

- Apply the full schema to a scratch database.
- Apply `schema/9-schema.sql` to a copy of production-shaped data.
- Query `information_schema.tables` and confirm every app table uses
  InnoDB.
- Interrupt one transaction-wrapped cron or request path in staging and
  confirm partial state is rolled back.
- `composer test`

## 3. Preserve admin login query strings (#103)

`AdminPageBase::renderPage()` redirects unauthenticated users to
`login.php` with `PHP_SELF` only.  WhatsNew pages depend on
`site` and `parentDir`, so session timeout drops the browsing context.

Implementation:

- Add one helper for the current request target, including `PHP_SELF`
  and `QUERY_STRING`.
- Use that helper when `AdminPageBase` builds the login redirect.
- Consider using the same helper in `PageBase::renderLoginLink()` to
  keep explicit login links and forced login redirects consistent.
- Preserve `PATH_INFO`, query strings, and the login-page passthrough
  behavior.

Verification:

- Add `AdminPageBaseTest` coverage for
  `whatsnew.php?site=bitsavers&parentDir=123`.
- Add coverage for no query string and for an existing login page target.
- `composer test`

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
