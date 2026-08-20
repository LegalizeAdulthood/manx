# Release 2.1 implementation plan

Generated from the open 2.1.0 milestone issues on 2026-08-20.

## Scope

All implementation slices are complete.

Implement one numbered slice at a time.  When a slice is complete, remove
that slice and leave the remaining numbers unchanged.

## 7. Release verification

- Run the full test suite with `composer test`.
- Check a GitHub Actions run for the branch.
- Apply schema migrations to a scratch database from an empty schema.
- Apply schema migrations to a production-shaped database copy.
- Smoke test login timeout recovery from a WhatsNew subdirectory.
- Smoke test URL wizard completion from a WhatsNew file.
- Smoke test Bitsavers search results for a company with and without
  directory associations.
