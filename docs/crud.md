# CRUD implementation plan

Generated from the shared ChatGPT chat and local code inspection on
2026-08-20.

## Scope

Bring practical CRUD support to the main administrative objects:

- sites
- site mirrors
- copies
- documents
- document history
- revision relationships
- users

Use Adminer Editor for ordinary row editing.  Keep custom Manx forms where
the data model is not ordinary row CRUD: document versioning and password
changes.

Implement one numbered slice at a time.  When a slice is complete, remove
that slice and leave the remaining numbers unchanged.

Each slice must include acceptance criteria and automated tests.  Do not
remove a slice until both sections are satisfied.

## Local facts

- `SitePage` and `MirrorPage` only render tables; their POST handlers
  throw.
- `CompanyPage` already handles create and update, but has no CSRF token.
- `PublicationPage` creates documents only.
- `mirror` rows attach mirror URL stems to a site and order them by rank.
- `AdminPageBase` requires login, then dispatches POST without CSRF checks.
- `User` treats every logged-in user as an administrator.
- `LoginPage` hashes submitted passwords with SHA-1.
- The schema uses MyISAM tables and indexed relationships, not foreign
  keys.
- `pub` plus `pub_history` is a versioned document model.
- `pub_history` acts as an audit log, but there is no page to view it.
- `supersession` links document revisions as `old_pub` to `new_pub`.
- `composer.json` currently declares PHP `>=7.3`.
- Hosting supports PHP 8.4 and 8.5.

## Direction

Add a small embedded Adminer Editor subsystem behind the existing Manx
login.  Expose only approved tables or views.  Do not expose a SQL command
page, raw password hashes, or both sides of the document versioning tables.
Manage revision links through custom code so cached `pub` flags and chain
rewrites stay consistent.

Use these packages:

- `vrana/adminer`, after raising the PHP baseline to `>=8.4.1`
- `paragonie/anti-csrf`, for custom Manx forms

Install the current Adminer package line first.  Confirm that the Editor
entry point exists under `vendor` after Composer updates.  If the current
`6.x` package no longer ships the Editor entry point, pin the newest
`5.5.x` package that does.

Target PHP 8.4.1 as the production baseline.  Treat PHP 8.5 as a
compatibility target until the app and dependency set have passed the full
suite there.

## Schema summary

All schema changes for this plan belong in `schema/9-crud.sql`.

- Added physical tables: none.
- Modified physical tables: `user` only.
- Removed physical tables: none.
- Added columns on `user`: `password_hash VARCHAR(255) NULL`,
  `is_admin TINYINT(1) NOT NULL DEFAULT 0`, and
  `disabled TINYINT(1) NOT NULL DEFAULT 0`.
- Modified columns: none.
- Removed columns: none during this CRUD rollout.
- Deferred column removal: drop `user.pw_sha1` only after every account has
  a verified `password_hash`.
- Added views: `admin_document`.
- `admin_document` columns: `pub_id`, `pub_active`, `ph_id`,
  `ph_active`, `ph_created`, `ph_edited_by`, `ph_pub_type`,
  `ph_company`, `ph_part`, `ph_alt_part`, `ph_revision`,
  `ph_pub_date`, `ph_title`, `ph_keywords`, `ph_notes`,
  `ph_abstract`, and `ph_lang`.
- Modified views: none.
- Removed views: none.
- Added indexes or constraints: none in the initial CRUD rollout.
- Existing tables used without schema changes: `site`, `mirror`, `copy`,
  `pub`, `pub_history`, `supersession`, and `company`.

## Functional CRUD goals

When this plan is complete, the administrator CRUD surface is:

| Table / View | Create | Read | Update | Delete |
|---|---:|---:|---:|---:|
| `site` | Yes | Yes | Yes | Soft delete via `live = 'N'` |
| `mirror` | Yes | Yes | Yes | Yes |
| `copy` | Yes | Yes | Yes | Maybe direct delete or custom delete, depending on cached `pub` flag handling |
| `admin_document` view | No | Yes | No | No |
| `pub` | Via custom document create/edit flow | Indirectly via document views | Custom updates only: `pub_history`, `pub_active`, `pub_superseded` | Soft delete via `pub_active = 0` |
| `pub_history` | Created by custom document create/edit flow | Yes, through history viewer | No | No |
| `supersession` | Custom revision-link editor | Yes, through document edit/history context | Custom replace/link rewrite | Custom unlink |
| `user` | Likely yes through Adminer, with guarded fields | Yes | Yes, limited safe fields | No |
| `company` | Existing page only, or later Adminer if chosen | Yes | Existing page or Adminer if chosen | Not planned |

Ordinary row CRUD belongs in Adminer Editor.  Document versioning,
password changes, revision-chain rewrites, and operations that maintain
cached `pub` flags stay in custom Manx code.

## 1. Dependency and PHP baseline

Implementation:

- Raise the Composer PHP requirement from `>=7.3` to `>=8.4.1`.
- Verify the current lock file with `composer prohibits php 8.4.1`.
- Update `pimple/pimple` to `^3.6`.
- Migrate `slim/slim` to `^4.15`.
- Keep `guzzlehttp/guzzle` on `^7.15`.
- Keep `guzzlehttp/psr7` on `^2.13`; tests use it directly, and
  Slim 4 can use its PSR-17 factory.
- Update `phpunit/phpunit` to `^13.3`.
- Add `vrana/adminer` and `paragonie/anti-csrf` with Composer.
- Rewrite `public/api.php` and API tests for Slim 4.
- Verify the Adminer Editor entry point is present under `vendor`.

Schema changes:

- No database schema changes.

Acceptance criteria:

- `composer.json` requires PHP `>=8.4.1`.
- `composer.lock` resolves with only used direct dependencies.
- The API routes still return the same JSON shape as before.
- The Adminer Editor entry point is present or the package is pinned to the
  newest line that still ships it.
- PHP 8.4 is the supported runtime.
- PHP 8.5 is documented as supported only after the suite passes there.

Automated tests:

- `composer validate --strict`.
- `composer prohibits --locked php 8.4.1`.
- `composer test` under PHP 8.4.
- `composer test` under PHP 8.5 before declaring 8.5 support.
- API route tests covering all existing Slim endpoints.
- A package smoke test that asserts the Adminer Editor entry point exists.

## 2. Real administrative authorization

Implementation:

- Add schema migration `schema/9-crud.sql`.
- Backfill existing users as enabled administrators to avoid lockout.
- Keep `pw_sha1` temporarily for legacy login migration.
- Extend `getUserFromSessionId()` to return `is_admin` and `disabled`.
- Change `User::isAdmin()` to use `is_admin`, not `isLoggedIn()`.
- Reject disabled users when creating or using sessions.
- Update `AdminPageBase` to require `isAdmin()`, not only login.

Schema changes:

- Modify existing table `user`.
- Add column `password_hash VARCHAR(255) NULL`.
- Add column `is_admin TINYINT(1) NOT NULL DEFAULT 0`.
- Add column `disabled TINYINT(1) NOT NULL DEFAULT 0`.
- Do not remove or modify column `pw_sha1` in this slice.
- Do not add a unique key on `email` until existing data is audited.
- No new physical tables.

Acceptance criteria:

- Existing users can still log in after the migration.
- Guests and non-admin users cannot reach admin pages.
- Disabled users cannot log in or continue an existing session.
- Enabled administrators can reach all existing admin pages.
- The app cannot be left with no enabled administrator by this migration.

Automated tests:

- Migration tests for new columns and backfilled values.
- `UserTest` coverage for guest, normal, admin, and disabled rows.
- `ManxTest` coverage for disabled-user session rejection.
- `AdminPageBaseTest` coverage for login-only and admin-only access.

## 3. Password migration

Implementation:

- Change `Manx::loginUser()` to accept the raw submitted password.
- Keep the raw password only as an in-process verification candidate.
- Never store, log, echo, or pass the raw password beyond login handling.
- Verify `password_hash()` values with `password_verify()`.
- When a user still has only `pw_sha1`, accept a matching legacy hash once.
- On successful legacy login, write `password_hash()` and keep login
  working.
- Stop creating sessions for disabled users.
- Update `LoginPage` so it no longer pre-hashes the password.

Schema changes:

- No new tables, views, columns, indexes, or constraints.
- Update values in `user.password_hash` during legacy login migration.
- Leave `user.pw_sha1` in place for this slice.
- Plan a later migration to drop `user.pw_sha1` only after every user has
  a verified `password_hash`.

Acceptance criteria:

- New password hashes use PHP `password_hash()`.
- Existing SHA-1 users can log in once and are upgraded in place.
- Bad passwords do not create sessions.
- Raw submitted passwords never appear in storage, output, or logs.
- Disabling a user prevents both legacy and modern password login.

Automated tests:

- `ManxTest` coverage for modern hash login.
- `ManxTest` coverage for legacy SHA-1 migration.
- `ManxTest` coverage for bad passwords and disabled users.
- `LoginPageTest` coverage proving the page passes the submitted candidate
  without pre-hashing.
- Database tests proving the upgraded hash verifies with
  `password_verify()`.

## 4. Adminer Editor wrapper

Implementation:

- Add `public/admin.php` with the same entrypoint pattern as `site.php`.
- Add `public/pages/ManxAdminer.php`.
- Define `adminer_object()` before including Adminer Editor.
- Reuse Manx database credentials from the existing config path.
- Return fixed Adminer credentials and the fixed Manx database name.
- Authorize through the current Manx session and `User::isAdmin()`.
- Disable Adminer version checks from the wrapper.
- Whitelist only `site`, `mirror`, `copy`, `user`, and `admin_document`.
- Hide all other tables by returning an empty table name.
- Add an Admin menu link to `admin.php`.

Schema changes:

- No database schema changes.

Acceptance criteria:

- Admin users can open `admin.php` without a database login prompt.
- Non-admin users and guests cannot access `admin.php`.
- Only whitelisted tables or views are visible.
- No SQL command page is reachable through the embedded editor.
- The wrapper reads database settings from the existing Manx config path.

Automated tests:

- Page tests for guest, normal user, and admin access.
- Unit tests for fixed database credentials and database name selection.
- Unit tests for table whitelist behavior.
- A route or entrypoint smoke test for `public/admin.php`.

## 5. Adminer presentation and relationships

Implementation:

- Provide friendly table names for whitelisted tables.
- Provide friendly field names and hide unsafe or noisy fields.
- Render `site.low`, `site.live`, `pub.pub_active`, `user.is_admin`, and
  `user.disabled` as boolean-style controls.
- Mark primary keys and generated fields read-only.
- Override `foreignKeys()` for synthetic relationships.
- Link `copy.site` to `site.site_id`.
- Link `mirror.site` to `site.site_id`.
- Link `copy.pub` to `admin_document.pub_id`.
- Show a backward link from each site row to its mirror rows.
- Set row descriptions so sites show names and documents show part plus
  title.

Schema changes:

- No database schema changes.
- Use synthetic relationships in PHP instead of adding database foreign
  keys.

Acceptance criteria:

- Site and document references display useful labels instead of raw ids.
- Primary keys and hidden fields cannot be edited through Adminer.
- Synthetic relationship links work despite missing database foreign keys.
- Boolean-like fields render as constrained choices.
- User password columns are not displayed.

Automated tests:

- Unit tests for table names, field names, and hidden fields.
- Unit tests for read-only field decisions.
- Unit tests for synthetic `foreignKeys()` results.
- Unit tests for row-description formatting.
- Unit tests for boolean field rendering decisions.

## 6. Site CRUD

Implementation:

- Enable Adminer insert and update for `site`.
- Normalize `url` and `copy_base` to trailing slashes before saving.
- Keep `site_id` read-only.
- Treat delete as `live = 'N'`.
- Preserve `display_order`, `low`, and `live`.
- Replace or redirect `site.php` to the Adminer `site` table.

Schema changes:

- No database schema changes.
- Data operations use existing table `site`.
- Use existing columns `site_id`, `name`, `url`, `description`,
  `copy_base`, `low`, `live`, and `display_order`.
- Do not add, modify, or remove columns.

Acceptance criteria:

- Admin users can create a site.
- Admin users can edit name, URL, copy base, description, flags, and order.
- URLs and copy bases are normalized before save.
- Delete does not remove the row; it marks the site not live.
- Existing site list links land on the new site editing flow.

Automated tests:

- Database tests for site create, update, and soft-delete.
- Unit tests for URL and copy-base normalization.
- Page tests for `site.php` redirect or replacement behavior.
- Adminer wrapper tests proving `site_id` is read-only.

## 7. Site mirror CRUD

Implementation:

- Enable Adminer insert, update, and delete for `mirror`.
- Expose `site`, `original_stem`, `copy_stem`, and `rank`.
- Keep `mirror_id` read-only.
- Show `site` as the site name, not a raw integer.
- Order mirror rows by `site`, then `rank`.
- Add a site-row action for viewing mirrors filtered to that site.
- Add a site-row action for creating a mirror with `site` preselected.
- Validate that URL stems are non-empty and preserve trailing slashes.

Schema changes:

- No database schema changes.
- Data operations use existing table `mirror`.
- Use existing columns `mirror_id`, `site`, `original_stem`, `copy_stem`,
  and `rank`.
- Do not add, modify, or remove columns.

Acceptance criteria:

- Admin users can add, edit, and delete mirrors for a site.
- Mirror rows show the site name instead of only the site id.
- Site rows link to a filtered mirror list.
- New mirror links from a site preselect that site.
- Empty URL stems are rejected before save.

Automated tests:

- Database tests for mirror add, edit, and delete.
- Unit tests for mirror URL-stem validation.
- Adminer wrapper tests for site label and read-only `mirror_id`.
- Page or wrapper tests for site-filtered mirror links.

## 8. Copy CRUD

Implementation:

- Enable Adminer insert and update for `copy`.
- Show `site` as the site name, not a raw integer.
- Show `pub` as part number plus title, not a raw integer.
- Preserve `format`, `url`, `notes`, `size`, `md5`, `credits`, and
  `amend_serial`.
- Keep `copy_id` read-only.
- Decide whether deletion may be direct after checking cached
  `pub_has_online_copies` and related flags.
- If cached flags matter, add a small custom `deleteCopy()` path.

Schema changes:

- No database schema changes.
- Data operations use existing table `copy`.
- Use existing columns `copy_id`, `pub`, `format`, `site`, `url`,
  `notes`, `size`, `md5`, `credits`, and `amend_serial`.
- Do not add, modify, or remove columns.

Acceptance criteria:

- Admin users can create and edit copy rows.
- Copy rows display site and document labels.
- Copy edits preserve all non-edited copy fields.
- The chosen delete behavior keeps cached publication flags correct.
- Copy ids cannot be edited.

Automated tests:

- Database tests for copy create and update.
- Database tests for the chosen copy delete behavior.
- Tests proving cached `pub` flags stay correct after deletion.
- Adminer wrapper tests for copy labels and read-only `copy_id`.

## 9. Document browse view

Implementation:

- Add an `admin_document` SQL view in `schema/9-crud.sql`.
- Join `pub` to the current `pub_history` row via `pub.pub_history`.
- Include `pub_id`, `pub_active`, company, type, part, alternate part,
  revision, publication date, title, keywords, notes, abstract, and
  language.
- Expose `admin_document` read-only in Adminer Editor.
- Make its edit link point to `publication.php?id=<pub_id>`.
- Add a history link pointing to `publication-history.php?id=<pub_id>`.
- Do not expose `pub` or `pub_history` as separately editable tables.

Schema changes:

- Add view `admin_document`.
- Do not add or modify physical tables.
- Do not add, modify, or remove columns in `pub` or `pub_history`.
- Define `admin_document.pub_id` from `pub.pub_id`.
- Define `admin_document.pub_active` from `pub.pub_active`.
- Define `admin_document.ph_id` from `pub_history.ph_id`.
- Define `admin_document.ph_active` from `pub_history.ph_active`.
- Define `admin_document.ph_created` from `pub_history.ph_created`.
- Define `admin_document.ph_edited_by` from `pub_history.ph_edited_by`.
- Define `admin_document.ph_pub_type` from `pub_history.ph_pub_type`.
- Define `admin_document.ph_company` from `pub_history.ph_company`.
- Define `admin_document.ph_part` from `pub_history.ph_part`.
- Define `admin_document.ph_alt_part` from `pub_history.ph_alt_part`.
- Define `admin_document.ph_revision` from `pub_history.ph_revision`.
- Define `admin_document.ph_pub_date` from `pub_history.ph_pub_date`.
- Define `admin_document.ph_title` from `pub_history.ph_title`.
- Define `admin_document.ph_keywords` from `pub_history.ph_keywords`.
- Define `admin_document.ph_notes` from `pub_history.ph_notes`.
- Define `admin_document.ph_abstract` from `pub_history.ph_abstract`.
- Define `admin_document.ph_lang` from `pub_history.ph_lang`.
- Join `pub` to `pub_history` on `pub.pub_history = pub_history.ph_id`.

Acceptance criteria:

- The view lists one row per publication using current history.
- The view is searchable and browseable in Adminer Editor.
- Rows link to the custom document editor and history viewer.
- Direct editing of the view is disabled.
- Raw `pub` and `pub_history` tables stay hidden.

Automated tests:

- Schema tests for `admin_document` columns and current-history join.
- Adminer wrapper tests proving `admin_document` is read-only.
- Adminer wrapper tests for document edit and history links.
- Whitelist tests proving `pub` and `pub_history` are hidden.

## 10. Document history viewer

Implementation:

- Add `public/publication-history.php`.
- Add `public/pages/PublicationHistoryPage.php`.
- Require admin access through `AdminPageBase`.
- Load the selected `pub` and every `pub_history` row for that document.
- Show rows newest-first by `ph_created`, then `ph_id`.
- Mark the row currently referenced by `pub.pub_history`.
- Show `ph_created`, `ph_edited_by`, type, company, part, alternate part,
  revision, publication date, title, keywords, notes, abstract, and
  language.
- Resolve `ph_edited_by` to a display name when possible.
- Link back to `details.php/<company>,<pub_id>` and
  `publication.php?id=<pub_id>`.
- Keep the page read-only: no forms, POST path, or mutation methods.

Schema changes:

- No database schema changes.
- Read existing table `pub_history`.
- Read existing table `pub` to identify the current history row.
- Do not add, modify, or remove columns.

Acceptance criteria:

- Admin users can view the full history for one publication.
- History rows are ordered newest-first.
- The current history row is clearly marked.
- Editor ids show display names when available.
- The page has no mutating controls or POST behavior.

Automated tests:

- Database tests for empty, single-row, and multi-row history results.
- Page tests for ordering and current-row marking.
- Page tests for editor-name display.
- Page tests proving POST is rejected or unsupported.

## 11. Version-aware document editor

Implementation:

- Convert `PublicationPage` from create-only to create-or-edit.
- With no `id`, keep the current create behavior.
- With `id`, load the current `pub` and `pub_history` values as defaults.
- On save, insert a new `pub_history` row and update `pub.pub_history`.
- Set `ph_pub` on the new history row to the edited `pub_id`.
- Treat delete as `pub.pub_active = 0`.
- Keep history rows immutable after creation.
- Reuse existing document field names where practical.
- Add CSRF tokens to the create, edit, and delete forms.

Schema changes:

- No database schema changes.
- Insert rows into existing table `pub_history`.
- Update existing column `pub.pub_history` to point at the new history row.
- Update existing column `pub.pub_active` for soft-delete.
- Do not modify existing `pub_history` rows after creation.
- Do not add, modify, or remove columns.

Acceptance criteria:

- Existing create behavior still creates a publication and first history.
- Editing a document creates a new history row instead of mutating old
  history.
- `pub.pub_history` points to the new current history after edit.
- Delete marks the publication inactive.
- Old history rows remain unchanged and visible in the history viewer.

Automated tests:

- `PublicationPageTest` coverage for create mode.
- `PublicationPageTest` coverage for edit defaults.
- Database tests for edit-as-new-history behavior.
- Database tests for soft-delete behavior.
- Tests proving old history rows are immutable after edit.
- CSRF tests for create, edit, and delete submissions.

## 12. Revision relationship editor

Implementation:

- Keep `supersession` hidden or read-only in Adminer Editor.
- Add database methods to list, add, remove, and replace revision links.
- Add a transaction-safe `setRevisionLinks($pubId, $oldPub, $newPub)`.
- Recompute `pub.pub_superseded` for every affected publication.
- Add previous and next revision fields to `PublicationPage`.
- Reuse document search or part lookup to choose linked publications.
- When inserting B between linked A and C, replace `A -> C` with
  `A -> B` and `B -> C` in one transaction.
- Prevent duplicate links, self-links, and cycles.
- Decide whether inactive documents can appear in revision chains.
- Show current previous and next revision links on the edit form.

Schema changes:

- No database schema changes.
- Data operations use existing table `supersession`.
- Use existing columns `old_pub` and `new_pub`.
- Use existing primary key `(old_pub, new_pub)`.
- Update existing column `pub.pub_superseded` after relationship changes.
- Do not add, modify, or remove columns.

Acceptance criteria:

- Admin users can set previous and next revisions for a document.
- Inserting a middle revision rewrites the chain transactionally.
- Duplicate links, self-links, and cycles are rejected.
- `pub.pub_superseded` matches the resulting chain state.
- The edit form shows current previous and next links.

Automated tests:

- Database tests for append, prepend, unlink, and middle insertion.
- Database tests for duplicate-link, self-link, and cycle rejection.
- Database tests for `pub_superseded` recomputation.
- Page tests for rendering current previous and next links.
- Page tests for saving revision-link changes.

## 13. User CRUD

Implementation:

- Expose only `email`, `first_name`, `last_name`, `is_admin`, and
  `disabled` in Adminer Editor.
- Hide `pw_sha1` and `password_hash`.
- Do not allow deletion from Adminer.
- Add a custom set-password action protected by CSRF.
- Hash new passwords with `password_hash()`.
- Prevent disabling or demoting the current account.
- Prevent disabling or demoting the last enabled administrator.

Schema changes:

- No additional database schema changes after slice 2.
- Data operations use existing table `user`.
- Expose existing columns `email`, `first_name`, `last_name`, `is_admin`,
  and `disabled`.
- Hide existing columns `pw_sha1` and `password_hash`.
- Update existing column `password_hash` only through set-password.
- Do not add, modify, or remove columns.

Acceptance criteria:

- Admin users can browse and edit safe user profile fields.
- Password hashes are never displayed or edited as ordinary fields.
- Password changes use the custom set-password flow.
- The current account cannot disable or demote itself.
- The last enabled administrator cannot be disabled or demoted.
- User rows cannot be deleted through Adminer.

Automated tests:

- Adminer wrapper tests for visible and hidden user fields.
- Database tests for set-password hashing and verification.
- Tests for current-account disable and demotion guards.
- Tests for last-enabled-admin disable and demotion guards.
- Adminer wrapper tests proving delete is disabled for users.

## 14. CSRF coverage for custom admin forms

Implementation:

- Add a small Manx wrapper around `paragonie/anti-csrf`.
- Add tokens to `CompanyPage`, `PublicationPage`, reports with POST
  actions, and any custom copy or user actions.
- Verify tokens before `AdminPageBase::postPage()` dispatches.
- Keep GET-only selection forms token-free.

Schema changes:

- No database schema changes.
- Store CSRF state in the existing PHP session mechanism used by the
  CSRF package.

Acceptance criteria:

- Every custom admin POST requires a valid CSRF token.
- GET-only forms keep working without a token.
- Invalid and missing tokens are rejected before page mutation.
- Adminer Editor keeps using its own request protection.
- Existing successful POST flows keep their redirects and output.

Automated tests:

- `AdminPageBaseTest` coverage for valid, missing, and invalid tokens.
- Page tests for each custom admin POST flow.
- Regression tests for GET-only selection forms.
- Tests proving rejected CSRF requests do not call mutation methods.

## 15. Optional adjacent tables

Implementation:

- Keep `company` on its existing page until the core CRUD path is stable.
- Later, either move `company` to Adminer Editor or add CSRF to the current
  `CompanyPage` and leave it custom.
- Do not add broad table access as a shortcut.

Schema changes:

- No database schema changes unless a later company-specific slice adds
  one.
- If `company` moves to Adminer, use existing table `company`.
- Use existing columns `id`, `name`, `short_name`, `sort_name`, `display`,
  and `notes`.

Acceptance criteria:

- `company` remains editable through exactly one approved path.
- If moved to Adminer, the old page redirects cleanly.
- If left custom, `CompanyPage` has CSRF protection.
- No extra tables become visible by default.

Automated tests:

- Page tests for the chosen company editing path.
- CSRF tests for `CompanyPage` if it remains custom.
- Adminer whitelist tests proving optional tables stay hidden by default.

## 16. Release verification

Implementation:

- Run `composer test`.
- Apply schema migrations to a production-shaped database copy.
- Login as an admin, a non-admin, and a disabled user.
- Smoke test Adminer access, table whitelist, and hidden fields.
- Smoke test site create, update, and soft-delete.
- Smoke test site mirror create, update, delete, and filtered listing.
- Smoke test copy create, update, and chosen delete behavior.
- Smoke test document history for one-row and multi-row documents.
- Smoke test document create, edit, history retention, and soft-delete.
- Smoke test inserting a revision between two linked revisions.
- Smoke test user edit, set-password, and last-admin protection.

Schema changes:

- Verify `schema/9-crud.sql` applies cleanly.
- Verify `schema/9-crud.sql` modifies only table `user`.
- Verify `schema/9-crud.sql` adds only view `admin_document`.
- Verify no columns are removed in the CRUD rollout.

Acceptance criteria:

- All CRUD slices pass automated tests.
- The production-shaped database migrates without manual correction.
- Manual smoke tests match the intended administrator workflows.
- No hidden table, password hash, or SQL command page is exposed.
- Rollback steps are documented for schema and Composer changes.

Automated tests:

- Full `composer test` run under the supported PHP version.
- CI run using a matrix for supported PHP versions.
- A migration smoke test against a production-shaped fixture.
- End-to-end admin workflow tests for the highest-risk CRUD paths.

## References

- Shared chat:
  <https://chatgpt.com/share/6a8716b5-6734-83e8-b4f8-f42392a9449d>
- Adminer package:
  <https://packagist.org/packages/vrana/adminer>
- Adminer Editor:
  <https://www.adminer.org/en/editor/>
- Adminer extension API:
  <https://www.adminer.org/en/extension/>
- Anti-CSRF package:
  <https://packagist.org/packages/paragonie/anti-csrf>
