# Release 2.1 implementation plan

Generated from the unreleased 2.1.0 schema cleanup needed before
release 2.2.0.

## Schema Guidance

All release 2.1 schema changes go into `schema/8-schema.sql`.  Do not
use `schema/9-schema.sql` for this release.

The final statement in `schema/8-schema.sql` sets the version property to
`2.1.0`, after all DDL and migration cleanup have completed.

Migrate data using one-off procedures.  Drop each procedure as soon as
its data migration has run.

Do not create persistent relationships between known-copy rows and
unknown-path rows or directories.  Keep copy lookup caches limited to
copy data derived from `copy.url`.

## Scope

Release 2.1.0 completes the unknown-directory schema while keeping known
copies independent from unknown paths.  It also adds filename caches
needed to make later unknown-path and moved-file checks efficient.

Issues:

- #145 Checking for moved files with many unknown paths is very slow.

Implement one numbered slice at a time.  Each numbered slice fixes one
issue.  When a slice is complete, remove that slice and leave the
remaining numbers unchanged.

# Implementation

## Release verification

- Run the full test suite with `composer test`.
- Apply schema migrations to a production-shaped database copy.
- Smoke test URL Wizard ingestion from an unknown path.
- Smoke test WhatsNew directory ingestion.
- Smoke test unknown-path cleanup for existing copies.
- Smoke test moved-file detection without per-copy HTTP checks.
