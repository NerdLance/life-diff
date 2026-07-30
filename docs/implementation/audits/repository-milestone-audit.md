# Repository milestone audit

Date: 2026-07-30  
Scope: Milestones 1 and 2 — registration/profile identity, authorization,
repository workflows, and repository/profile pages.  
Authority: `storage/app/private/docs/lifediff-phase-1.md`

## Result

**Pass.** The completed work is safe to begin release-draft implementation.
No Phase 1 defect was found in the audited milestone scope. Release composer,
release-detail, and release-public-link workflows remain deliberately absent
for their later implementation prompts.

## Review matrix

| Area | Evidence | Result |
| --- | --- | --- |
| Registration | `CreateNewUser`, shared handle rules, and registration feature tests normalize lower case handles, reject reserved/invalid handles, and preserve `name` from display name. | Pass |
| Profile settings | Form request, `UpdateProfile` action, controller, shared status metadata, and settings page cover handle, display name, bio, status, and IANA timezone without avatar uploads. | Pass |
| Authentication regressions | Existing login, reset, verification, passkey, two-factor, password confirmation, and account-deletion surfaces remain present; complete test suite is green. | Pass |
| Policies | User, repository, and release policies control reads/writes. Private repository and draft/release read denials use `denyAsNotFound()`. Owner-only repository page access also resolves as 404 for non-owners. | Pass |
| Visibility scopes | Listing scopes are explicitly documented as query narrowing, not policy replacements. Public listings require active public repositories and public published releases. | Pass |
| Repository actions and requests | Owner comes from auth; normalized owner-scoped name/slug validation; private/stable defaults; archive, restore, typed delete confirmation; visibility narrowing occurs in the update action transaction. | Pass |
| Routes | Authenticated routes use `auth`, `verified`, and `{repository:public_id}`. Public routes use `@{user:handle}` and a scoped `{repository:slug}` child binding. | Pass |
| Dashboard | Private owner dashboard contains active repositories, actual drafts only, actual recent releases only, and a repository-first empty state. No social/feed modules exist. | Pass |
| Owner pages | Index separates active/archived; owner show provides drafts, published releases, and authorized action flags; forms use shared visibility descriptions and narrowing warning. | Pass |
| Public pages | Profile lists public active repositories and public published releases only. Direct public/unlisted repository views list public published releases only. | Pass |
| Inertia props | Owner pages receive their own private data only. Public serializers use explicit array shapes rather than serializing models. Inventory below. | Pass |
| Accessibility | Form inputs have labels/help text/error associations; status/visibility choices are keyboard-accessible native controls; headings/sections are semantic; focus indicators and responsive single-column fallback are present. | Pass by code review |
| Tests and diff | Feature coverage includes component, prop, action-flag, empty-state, visibility-filter, and public-prop-absence assertions. Diff contains no deferred-feature artifacts. | Pass |

## Targeted privacy-leak review

### Public profile response: `profiles/show`

| Prop | Values exposed | Why it is safe |
| --- | --- | --- |
| `profile` | Display name, handle, optional bio, self-described status | These are the contract-defined public identity fields. Email, timezone, internal ID, verification/security state, and private profile fields are absent. |
| `repositories` | Name, slug, optional description, status | Queried through `publiclyListed()`: active public repositories only. It excludes visibility, internal/public IDs, counts, ownership IDs, and any private/unlisted record. |
| `recentPublishedReleases` | Version, title, release type, publication date, repository name/slug | Queried through `Release::publiclyListed()`, so each item is published, public, and belongs to an active public repository. Body, change entries, release ID, visibility, draft state, and counts are absent. |
| Shared `auth` | The signed-in viewer only, or `null` for guests | This existing application-wide prop describes the current session, never the viewed profile. It does not change the target-profile serializer. |
| Shared `name` and `sidebarOpen` | Application name and viewer UI preference | Non-domain application chrome only. |

### Public repository response: `repositories/public-show`

| Prop | Values exposed | Why it is safe |
| --- | --- | --- |
| `profile` | Owner display name and handle | Required public context only; no owner email, bio, timezone, security state, or IDs. |
| `repository` | Name, slug, optional description, status, visibility | The scoped route resolves only to that user's repository and policy denies private resources as 404. No identifiers, counts, archive metadata, drafts, or owner action flags are present. |
| `publishedReleases` | Version, title, release type, publication date | Query requires both published state and public release visibility. Unlisted and private releases, drafts, body, change entries, IDs, visibility, and counts are absent. |
| Shared `auth` | The signed-in viewer only, or `null` for guests | Existing session context only; it never serializes private data from the viewed repository or owner. |
| Shared `name` and `sidebarOpen` | Application name and viewer UI preference | Non-domain application chrome only. |

No public controller passes an Eloquent model directly to Inertia. Tests assert
the absence of target email, visibility/count fields on profile cards, draft
props, owner action flags, release bodies, and internal public IDs.

## Explicit contract checks

| Check | Result | Evidence |
| --- | --- | --- |
| 1. New repositories default to private | Pass | Store request defaults `visibility` to `private`; create action accepts only validated data; workflow/page tests cover it. |
| 2. Other users receive 404 for private repositories | Pass | Repository policy returns `denyAsNotFound()`; policy tests cover verified and unverified non-owners. |
| 3. Guests receive 404 for private repositories | Pass | Same policy path is covered for a guest. |
| 4. Unlisted repositories resolve directly but stay out of profiles | Pass | Public repository route test resolves direct unlisted URL; public profile uses `publiclyListed()`. |
| 5. Public profiles disclose no private or unlisted counts | Pass | Explicit public prop test asserts absence of visibility and count properties; serializer contains neither. |
| 6. Archived repositories reject writes | Pass | Update/archive policy requires active state; archive workflow test verifies write rejection and restore behavior. |
| 7. Visibility reduction updates broader releases atomically | Pass | `UpdateRepository` uses one `DB::transaction()` for repository and child-release updates; workflow test verifies narrowing only broader children. |
| 8. Route ordering protects application paths | Pass | Authenticated application routes are registered before public routes; public identity routes require a literal `@` prefix, so reserved paths such as `/dashboard`, `/settings`, `/login`, and `/register` cannot match them. Reserved-handle validation remains server-side. |
| 9. Existing auth and security tests remain green | Pass | Complete suite includes existing authentication/security tests and passes. |
| 10. No Phase 2 UI or database concepts exist | Pass | Diff/schema/UI review found no feeds, follows, reactions, comments, tags, notifications, analytics, moderation, invitations, teams, search, or placeholders. |
| 11. Mobile layouts work at 320 CSS pixels | Pass by code review | New pages use full-width containers, `p-4` narrow-screen padding, wrapping action rows, and single-column defaults before responsive breakpoints; no fixed desktop widths/min-widths were introduced. |
| 12. No journal content is written to logs | Pass | Targeted search found no application log calls that include release bodies or change entries; controllers serialize body-free public props. |

## Findings and fixes

No Phase 1 defects were found in this audit, so no corrective production code
was required. The audit added this durable privacy-prop inventory.

## Deferred work, not findings

- Release-draft/composer, change-entry, publish, release-detail, and stable
  public-release-link workflows are later Phase 1 milestones and were not
  started here.
- The dashboard intentionally offers a repository-first action because the
  release composer route/action does not yet exist; it does not expose a dead
  release creation control.

## Verification

- `composer test` — passed: Pint, PHPStan, and 169 tests / 587 assertions.
- `npm run types:check` — passed.
- `npm run lint:check` — passed.
- `npm run format:check` — passed.
- `npm run build` — passed.
- `git diff --check` — passed.
