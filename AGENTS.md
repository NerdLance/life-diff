# LifeDiff repository guidance

## Authority and scope

- The LifeDiff Phase 1 contract is authoritative. Use
  `storage/app/private/docs/lifediff-phase-1.md` together with the LifeDiff
  sections of `storage/app/private/docs/Interview_Familiar_and_LifeDiff_MVP_Reference_Guide.pdf`.
- Phase 1 is private-first personal journaling through structured release
  notes. New repositories and releases default to private; drafts are always
  private.
- Do not implement or scaffold deferred social functionality. In particular,
  do not add speculative tables, endpoints, relationships, API surfaces, or UI
  placeholders for social, discovery, analytics, moderation, scheduling, AI,
  or mobile features.

## Architecture

- Keep controllers thin: authorize, validate through a form request, invoke one
  action, then return an Inertia response or redirect.
- Form requests own validation. Policies own authorization. Invokable actions
  own domain writes. Models contain only relationships, casts, small scopes,
  and local invariants.
- Domain enums are PHP string-backed enums. TypeScript domain values must come
  from controlled shared types or generated contracts, never arbitrary strings
  duplicated across components.
- Public release URLs use immutable public IDs. Use explicit bindings for
  different repository identifiers; never expose sequential release IDs.
- Local application development runs on MySQL 8.4 through the ignored `.env`.
  Keep migrations portable and verify MySQL behavior; the current automated
  suite remains an intentional in-memory SQLite compatibility lane until a
  dedicated MySQL test configuration is approved.

## Privacy and security

- Return 404, not 403, to guests and non-owners denied a private resource.
- Enforce visibility server-side with policies and visibility-aware queries.
  UI hiding is not authorization.
- Never write journal bodies or change-entry content to logs. Preserve existing
  authentication, verification, rate limiting, passkey, two-factor, password,
  and account-security behavior.

## Delivery discipline

- Every implementation prompt adds or updates tests for its behavior.
- Inspect `composer.json` and `package.json` before documenting or running
  commands; do not invent scripts.
- Work is complete only after the repository-defined applicable verification
  commands pass. See `docs/implementation/lifediff-phase-1-plan.md` for the
  Phase 1 implementation sequence and verification gate.
