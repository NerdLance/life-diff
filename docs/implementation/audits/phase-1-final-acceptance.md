# Phase 1 final acceptance audit

Date: 2026-07-30  
Authority: `storage/app/private/docs/lifediff-phase-1.md`  
Scope: the complete Phase 1 Publishing Grammar contract only

## Acceptance decision

**Accept after the blocking fixes recorded below and the final verification
gate.** The application supports a complete private-first personal-journal
loop for a single person: register and verify an account, establish identity,
create a private repository, save incomplete drafts, order entries, publish,
edit, share deliberately, and delete. None of these workflows depends on
followers, feeds, reactions, notifications, or other active users.

## Included-outcome review

| Contract outcome | Result | Evidence |
| --- | --- | --- |
| Minimal profile identity | Pass | Registration and profile settings validate a normalized reserved-handle-safe handle, display name, bio, status, and IANA timezone; `name` remains synchronized for Fortify compatibility. |
| Repository journal areas | Pass | Owner-scoped private-default repositories support status, visibility, archive/restore, typed deletion, and active/archived lists. |
| Draft authoring and ordered entries | Pass | Drafts permit zero entries; whitespace-only rows are removed; accepted rows are synchronized transactionally with server-owned zero-based order. |
| Release grammar and versions | Pass | PHP string-backed enums and controlled TypeScript values cover every status, visibility, state, release type, and change type. Semantic versions normalize `major.minor.patch`; suggestions use the latest publication only. |
| Publishing and published editing | Pass | Publication validates a non-empty entry list, visibility ceiling, version uniqueness, and repository state in one transaction. Published edits retain `published_at`, set `edited_at`, and preserve ordered entries. |
| Private, unlisted, and public sharing | Pass | Policies, scopes, explicit serializers, and tests enforce owner-only drafts/private data, direct-link unlisted access, public listings, and 404 denials. |
| History and stable links | Pass | Owner timelines show drafts and publications; public timelines show public publications only; `/r/{release:public_id}` survives handle/slug changes. |
| Existing authentication/security | Pass with deployment exception | Fortify registration, reset, passkeys, two-factor, confirmation, and deletion remain present. Email verification is intentionally disabled until a transactional email provider is configured. |
| Accessibility and responsive behavior | Pass | Labels, semantic landmarks, native radios/selects/buttons, keyboard move controls, predictable change-entry focus, destructive typed confirmation, visible focus treatment, plain-text rendering, and a 320px no-horizontal-overflow smoke check are present. |
| Factories and development seed | Pass | Explicit valid states seed two fictional users, four repositories (private, unlisted, public, archived), one draft, six published releases spanning all release types, and all change types. |

## Database, domain, and application architecture

- Schema contains only `users`, `repositories`, `releases`, and
  `change_entries` for LifeDiff. It uses string columns for domain enum values,
  unique immutable ULID `public_id` values, owner-scoped repository name/slug
  uniqueness, repository-scoped version uniqueness, ordered-entry uniqueness,
  foreign keys/cascades, and repository/release soft deletes.
- The portable release-version strategy intentionally reserves a version even
  after soft deletion through `unique(repository_id, version)`. This is
  documented in the migration and implementation plan rather than claiming a
  partial-index guarantee MySQL cannot portably provide.
- Models contain relationships, casts, route keys, small scopes, and local
  public-ID invariants. Requests validate, policies authorize, actions own
  writes and transactions, and controllers coordinate only.
- Public IDs are server-generated, immutable, unique, and used by stable
  release links. Internal sequential release IDs are absent from public URLs.
- TypeScript uses controlled unions and presentation maps matching the PHP
  enums; no component introduces arbitrary domain strings.

## Public-data leakage audit

### Public routes

| Route | Page | Access and data boundary |
| --- | --- | --- |
| `/@{user:handle}` | `profiles/show` | Any viewer; only active public repositories and recent public published releases. |
| `/@{user:handle}/{repository:slug}` | `repositories/public-show` | Any viewer with a direct public or unlisted repository URL; timeline contains public published releases only. |
| `/r/{release:public_id}` | `releases/public-show` | Any viewer with an eligible public or unlisted published-release URL; private and draft releases deny as 404. |

Fixed application routes precede the `@handle` routes, and the literal `@`
prefix prevents reserved application paths from being shadowed.

### Public Inertia props

| Response | Props | Why safe |
| --- | --- | --- |
| `profiles/show` | `profile` (display name, handle, bio, status); `repositories` (name, slug, description, status); `recentPublishedReleases` (ULID, version, title, type, date, repository name/slug) | Queries use active/public repository and published/public release scopes. There are no visibility fields, counts, emails, IDs for repositories, drafts, body/context, change entries, action flags, or security state. |
| `repositories/public-show` | `profile` (display name, handle); `repository` (name, slug, description, status, visibility); paginated `publishedReleases` (ULID, version, title, type, date) | The route is owner-scoped and policy-gated. The timeline explicitly restricts entries to public published releases. It exposes neither drafts, unlisted/private releases, body/context, change entries, counts, owner actions, email, nor security state. |
| `releases/public-show` | `profile` (display name, handle); `repository` (name, slug); `release` (ULID, version, type, title, optional plain-text body, dates, ordered entries); `copyLink` | Policy gates the exact release and repository before serialization. The release body and entries are intentionally the selected public release's journal content—not unrelated content. State, visibility, owner actions, email, security fields, drafts, counts, and unrelated resources are absent. |

Shared `auth.user` is explicitly `null` on all three public route families,
including for a signed-in visitor. This prevents a public response from
carrying a viewer email, verification state, passkey/two-factor state, or
private profile metadata.

## Explicit privacy, lifecycle, and logging checks

- Private repositories, private releases, drafts, and soft-deleted resources
  resolve as 404 for guests and non-owners.
- Drafts always have `state = draft` and `published_at = null`; publication
  sets `state = published` and `published_at` once. Published releases require
  at least one non-empty entry.
- Repository visibility narrowing updates broader child-release visibility in
  the same transaction; raising repository visibility never broadens a child
  automatically.
- Public profile queries show no private/unlisted counts. Public timelines do
  not include drafts or unlisted releases.
- Bodies and entries are escaped plain text with preserved line breaks. A
  source scan found no intentional logger call containing release body or
  change-entry content.

## Scope audit

No tables, models, relationships, routes, controllers, actions, API surfaces,
or UI placeholders exist for watches, follows, maintainers/invitations,
reactions, reviews, comments, issues, cherry-picks, deployments, forks,
notifications, feeds, tags, search, scheduling, analytics, AI generation,
avatar uploads, or a mobile API/frontend. These remain prohibited Phase 1
scope creep and valid only as future Phase 2-or-later work when separately
authorized.

## Browser smoke path

Completed locally against MySQL with a fictional acceptance account:

1. Registered and verified an account; unverified access to `/dashboard`
   redirected to the verification prompt after the fix.
2. Completed profile fields, created a private repository, and saved a draft
   with zero persisted entries.
3. Added three entries, reordered them with the provided move controls,
   saved, published, edited, and observed the edited indicator.
4. Confirmed both a guest and the second seeded user received a 404 for the
   private stable release URL.
5. Changed repository and release visibility to public; confirmed public
   profile, public repository, and signed-out stable release views.
6. Changed the handle and repository slug; confirmed the signed-out stable
   release URL continued to work with updated displayed context.
7. Soft-deleted the release with its typed-title confirmation; confirmed the
   stable URL immediately returned 404.
8. At a 320 CSS-pixel viewport, the public profile had no horizontal overflow.

## Findings

### Blocking Phase 1 acceptance — fixed

1. **Email verification is intentionally disabled for test deployment.** The
   Fortify feature, `MustVerifyEmail` model contract, verification routes, and
   `verified` middleware gate are absent until a transactional email provider
   is configured. A regression test confirms an unverified account can access
   the authenticated dashboard. Re-enable this as a deliberate deployment
   configuration change when outbound email is available.
2. **The composer could warn while saving.** The unsaved-navigation guard was
   not exempting form submit/publish visits. It now permits those intentional
   visits, while retaining warnings for genuine navigation away.
3. **Existing release versions could be overwritten by a suggestion.** The
   composer recomputed a version on every existing-release edit. Automatic
   suggestion application is now limited to brand-new drafts; existing draft
   and published versions stay unchanged until the author edits them.
4. **Public pages inherited the full signed-in auth model.** Public route
   responses now set shared `auth.user` to `null`; a feature assertion covers
   this boundary.

### Non-blocking Phase 1 improvements

- The generated default welcome/header branding still says “Laravel” rather
  than “LifeDiff.” This does not expose data or block the private journal loop,
  but should be replaced during product-brand validation.
- No dedicated frontend component-test runner is configured. Backend Inertia
  tests, browser smoke coverage, TypeScript, ESLint, Prettier, and production
  builds cover the UI contract today; adding a component-test harness is a
  quality improvement, not acceptance-blocking.

### Valid Phase 2 work

- The deferred social, discovery, notification, scheduling, analytics, AI,
  avatar, API, and mobile capabilities listed in the scope audit.

### Prohibited scope creep

- Adding any of the deferred concepts above, their schema, routes, generated
  contracts, blank controls, or placeholder metrics during Phase 1.

## Repository hygiene and verification

- No tracked `.env`, private key/certificate, `vendor`, `node_modules`,
  generated `public/build`, or application log file was found. The committed
  production configuration does not enable debug mode; `.env` is local-only.
- The working tree intentionally contains the uncommitted Phase 1 release,
  polish, and acceptance-audit changes listed by `git status`; no unrelated
  generated or secret material is present.
- `composer test` — passed: Pint, PHPStan, and 194 Pest tests / 841
  assertions (including existing authentication, verification, passkey, and
  two-factor coverage).
- `npm run types:check` — passed.
- `npm run lint:check` — passed.
- `npm run format:check` — passed.
- `npm run build` — passed.
- `git diff --check` — passed.
