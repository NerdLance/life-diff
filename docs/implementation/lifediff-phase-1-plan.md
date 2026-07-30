# LifeDiff Phase 1 implementation plan

## Authority and planning boundary

This plan implements the Phase 1 Publishing Grammar contract as a private-first
personal journal, not a social network. It was prepared against:

- `storage/app/private/docs/lifediff-phase-1.md` (authoritative contract)
- `storage/app/private/docs/implementation/lifediff-baseline.md`
- `storage/app/private/docs/implementation/lifediff-gap-report.md`
- `storage/app/private/docs/Interview_Familiar_and_LifeDiff_MVP_Reference_Guide.pdf`, sections 2.1, 2.2, 2.4, 2.6-2.8, and 3.0

The requested `docs/contracts/lifediff-phase-1.md` is not present. Until that
path is intentionally created or redirected, the first path above remains the
authoritative source. This document plans work only; it does not authorize
product implementation.

## Cross-cutting design decisions

- Use Laravel 13, React 19, TypeScript, Inertia 3, existing authentication,
  Wayfinder, Pest, Pint, PHPStan/Larastan, ESLint, and Prettier. No dependency
  is needed for Phase 1.
- Store UTC timestamps and format them using the authenticated user's configured
  or browser timezone.
- Use PHP string-backed enums and matching controlled TypeScript unions/maps.
  Keep database enum columns as strings for portability.
- Local application development uses MySQL 8.4. Keep migrations portable and
  exercise MySQL behavior through the dedicated local test database before
  Phase 1 relies on MySQL-specific semantics; the current automated suite is
  still an intentional SQLite compatibility lane.
- Controllers authorize, use a form request, invoke one action, then return an
  Inertia response or redirect. Requests validate, policies authorize, and
  invokable actions perform domain writes. Models own relationships, casts,
  small scopes, route-key behavior, and local invariants only.
- Use opaque immutable ULID `public_id` values for release public links;
  repository public IDs for authenticated owner routes; and explicit scoped
  `{user:handle}/{repository:slug}` binding for public repository routes. Fixed
  routes precede `@handle` routes. Exclude soft-deleted rows by default.
- Enforce privacy with policies and visibility-aware queries. Private-resource
  denials return 404 to guests and non-owners. Drafts are always private and
  never enter public or unlisted queries or props.
- Treat journal body and change-entry content as sensitive: render escaped plain
  text with preserved line breaks and never log it.

## Prompt sequence

Current implementation status: framework-light domain primitives, schema
migrations, models, relationships, scopes, and factories are complete.
Policies, requests, actions, routes, and product workflows remain pending.

| Prompt | Scope and acceptance boundary                                                                                                                                                                                                      |
| ------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1      | Correct identity and verification: authoritative-path decision, email-verification enforcement, profile-status enum, profile columns, normalized handle strategy, and `name`/`display_name` decision. No repositories or redesign. |
| 2      | Establish domain schema: remaining enums, repository/release/change-entry migrations, portable soft-delete/version decision, models, casts, relationships, route keys, factories, and unit tests. No HTTP routes.                  |
| 3      | Enforce privacy: user/repository/release policies, visibility ceiling, visibility scopes, 404 behavior, and owner/other/guest tests. No public pages.                                                                              |
| 4      | Complete identity UX: registration and profile settings for handle, display name, bio, status, and timezone; warn before a handle change; preserve security behavior.                                                              |
| 5      | Repository backend: requests, actions, controllers, authenticated routes, archive/restore/delete, and transactional child-visibility reduction.                                                                                    |
| 6      | Repository and profile pages: owner repository pages, public profile, public/unlisted repository access, scoped binding, and only necessary navigation seams.                                                                      |
| 7      | Release behavior: semantic-version parser/suggestion, draft/update/delete/publish actions, transactional change-entry synchronization, state-aware validation, and lifecycle tests.                                                |
| 8      | Composer: typed create/edit UI, repeatable keyboard-safe ordered rows, draft save, publish now, validation rendering, and unsaved-change warning.                                                                                  |
| 9      | Release details and public sharing: owner/public details, stable `/r/{public_id}`, editing indicator, delete/copy link, and privacy-safe public props.                                                                             |
| 10     | Journal dashboard and product shell: journal overview, LifeDiff branding/navigation, and removal of starter/deferred controls without changing security/settings shell.                                                            |
| 11     | Hardening: factories and fictional local seed data, remaining test matrix, accessibility review, browser smoke path, public-prop audit, and full verification/build.                                                               |

## Contract traceability matrix

An em dash means the requirement has no artifact in that category. All listed
actions are invokable actions; all listed tests include feature, policy, unit,
or Inertia coverage as appropriate.

| Contract requirement                                                                                                                                   | Planned migration                                                                                                           | Model or enum                                                                 | Action                                                                                               | Form request                                   | Policy                                                             | Controller or route                                                                       | Inertia page or component                                                                              | Test coverage                                                                                                              | Prompt        |
| ------------------------------------------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------- | ---------------------------------------------- | ------------------------------------------------------------------ | ----------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------- | ------------- |
| Preserve Laravel/React/Inertia stack; existing auth, rate limits, passkeys, 2FA, password reset, and account deletion                                  | —                                                                                                                           | `User` retains starter traits                                                 | —                                                                                                    | Existing security requests retained            | —                                                                  | Existing auth/settings routes retained                                                    | Existing auth/settings UI retained                                                                     | Existing regression suite; verification journey tests                                                                      | 1, 4, 11      |
| Enforce verified access to protected Phase 1 routes                                                                                                    | User change only if needed for verification contract                                                                        | `User` implements `MustVerifyEmail`                                           | —                                                                                                    | Existing registration request/action extension | —                                                                  | `auth`, `verified` route middleware                                                       | Verification screen                                                                                    | Registration notification and unverified redirect tests                                                                    | 1             |
| Profile identity: handle, display name, bio, status, timezone; UTC storage and local presentation                                                      | Add nullable migration-safe `handle`, `display_name`, `bio`, `status`, `timezone`, and normalized handle key if chosen      | `ProfileStatus`; `User` casts/fields                                          | `UpdateProfile`                                                                                      | Registration/profile update requests           | `UserPolicy` view/update                                           | Registration; settings profile route                                                      | Registration; profile settings; shared status presentation map                                         | Labels, reserved/duplicate/normalized handle, profile update, timezone tests                                               | 1, 4          |
| Handle rules and public identity: lower case 3-30 characters, reserved list, warning on breaking public links                                          | Unique normalized handle index                                                                                              | `User` handle scope/binding                                                   | `UpdateProfile`                                                                                      | Handle validation rules                        | `UserPolicy`                                                       | `/@{user:handle}` fixed-route-safe binding                                                | Settings warning and public profile identity                                                           | Handle parser/normalizer and public binding tests                                                                          | 1, 4, 6       |
| Repositories: owner-scoped name/slug, description, status, private default, immutable public ID, archive, soft delete                                  | `repositories` table: IDs, owner FK, public ID, normalized name if selected, slug, fields, timestamps, soft delete; indexes | `Repository`, `ProfileStatus`, `RepositoryVisibility`                         | `CreateRepository`, `UpdateRepository`, `ArchiveRepository`, `RestoreRepository`, `DeleteRepository` | Store/update/delete confirmation requests      | `RepositoryPolicy`                                                 | Authenticated `repositories.*` routes using `{repository:public_id}`                      | Index, create, edit, owner show                                                                        | Factory states; create/update/archive/restore/delete; owner slug uniqueness; default private; soft-delete resolution tests | 2, 3, 5, 6    |
| Repository active lists and journal continuity                                                                                                         | Repository indexes for owner/archived and visibility lists                                                                  | `Repository` scopes: `active`, `ownedBy`, `publiclyListed`, `visibleTo`       | —                                                                                                    | —                                              | `RepositoryPolicy::view`                                           | Repository index/show routes                                                              | Index separates active/archived; owner timeline                                                        | Pagination/listing and no popularity-metric tests                                                                          | 2, 3, 6       |
| Repository deletion requires confirmation; archive is read-only and does not alter visibility                                                          | `archived_at`, `deleted_at`                                                                                                 | `Repository` local archive invariant                                          | Archive/restore/delete actions                                                                       | Archive/restore/delete confirmation requests   | Repository archive/restore/delete abilities                        | Authenticated archive/restore/destroy routes                                              | Settings confirmation and read-only state                                                              | Archived rejects draft/publication; deletion stops resolution                                                              | 5, 7, 9       |
| Release schema: opaque public ID, version, type, state, title/body, visibility, publication/edit dates, soft delete                                    | `releases` table with FK, IDs, strings, dates, soft delete and indexes; selected active-version strategy                    | `Release`, `ReleaseState`, `ReleaseType`, `RepositoryVisibility` casts/scopes | `CreateReleaseDraft`, `UpdateRelease`, `PublishRelease`, `DeleteRelease`, `SuggestReleaseVersion`    | Store/update/publish/delete requests           | `ReleasePolicy`                                                    | Authenticated `repositories.releases.*` and `releases.*` routes                           | Composer and release detail                                                                            | Factories; route-key; state/date invariant; deletion and version tests                                                     | 2, 3, 7-9     |
| Semantic versions: normalized `major.minor.patch`, 0-9999 segments, suggestions from latest published version, override and uniqueness                 | Active-version uniqueness index/key chosen for production portability                                                       | Version value object/service; `ReleaseType`                                   | `SuggestReleaseVersion`; create/update/publish recheck                                               | Release rules                                  | Release update/publish abilities                                   | Composer endpoints                                                                        | Suggested-version field                                                                                | Parsing/normalization; every type's suggestion; same/different repository uniqueness tests                                 | 2, 7, 8       |
| Release lifecycle: incomplete private drafts; transactional publication; published edits retain `published_at` and set `edited_at`; soft deletion      | Release state/date columns                                                                                                  | `Release` draft/published scopes and invariant                                | Create/update/publish/delete actions                                                                 | State-aware release requests                   | Release create/update/publish/delete abilities                     | Store/update/publish/destroy routes                                                       | Composer; detail edited badge and owner controls                                                       | Draft CRUD; atomic publication; edited indicator; deleted route tests                                                      | 7-9           |
| Change entries: 0-50 draft rows; 1-50 published rows; typed, contiguous ordered entries; no standalone route                                           | `change_entries` table with FK, type, content, sort order, unique release/order index                                       | `ChangeEntry`, `ChangeType`; ordered relationship                             | Release actions synchronize entries transactionally                                                  | Nested change-entry rules                      | Inherited through `ReleasePolicy`; no `ChangeEntryPolicy` endpoint | Nested only in release payloads                                                           | Composer row editor, move controls, grouped detail display                                             | Empty-row removal; ownership of submitted IDs; order persistence; publication requirement tests                            | 2, 7, 8, 9    |
| Visibility values and ceiling; reduce broader child releases atomically                                                                                | Visibility columns/indexes above                                                                                            | `RepositoryVisibility`; repository/release `visibleTo` scopes                 | Update repository; release create/update/publish actions                                             | Repository/release enum and ceiling rules      | Repository/release view abilities                                  | Repository update; all release reads/writes                                               | Plain-language visibility fields/badges                                                                | Ceiling matrix; atomic narrowing tests                                                                                     | 2, 3, 5, 7, 8 |
| Viewer and listing matrix: owner sees authorized private resources; unlisted direct links work; public listing excludes unlisted/private/drafts/counts | Query indexes above                                                                                                         | Visibility scopes                                                             | —                                                                                                    | —                                              | User/repository/release `view`                                     | `/@{user:handle}`, `/{slug}`, `/r/{release:public_id}` reads                              | Public profile/repository/release variants                                                             | Owner/non-owner/guest 404 matrix; guest public/unlisted direct-link; listing exclusion; public-prop absence tests          | 3, 6, 9       |
| Public routes are durable and cannot be shadowed; public release link survives handle/slug changes                                                     | Public IDs and owner/slug unique index                                                                                      | Explicit route-key/binding methods                                            | —                                                                                                    | —                                              | View policies                                                      | `profiles.show`, `public.repositories.show`, `public.releases.show`; fixed routes first   | Public profile/repository/release pages                                                                | Binding, route ordering, handle/slug-change stable-link tests                                                              | 2, 3, 6, 9    |
| Required authenticated route family and verified middleware                                                                                            | —                                                                                                                           | —                                                                             | Actions above                                                                                        | Requests above                                 | Policies above                                                     | Dashboard, repositories, nested composer, release detail/edit/publish/delete named routes | Dashboard, repositories, composer, release pages                                                       | Middleware, route and outcome feature tests                                                                                | 4-10          |
| Dashboard journal overview: create-release, active repositories, drafts, recent releases, private empty state                                          | —                                                                                                                           | Existing scopes                                                               | —                                                                                                    | —                                              | Owner policies/scoped queries                                      | `dashboard`                                                                               | Dashboard                                                                                              | Props, private data, empty-state/no-social-placeholder tests                                                               | 10            |
| Repository pages: identity, owner draft/timeline, public-safe timeline, 20-item pagination                                                             | —                                                                                                                           | Repository/release chronological scopes                                       | —                                                                                                    | —                                              | View policies                                                      | Repository routes                                                                         | Index/create/edit/show and public show; repository components                                          | Inertia component/props/pagination/privacy tests                                                                           | 5, 6          |
| Composer: required fields, initial empty `added` row, future visibility retained, keyboard/pointer ordering, no false autosave                         | —                                                                                                                           | Controlled TypeScript domain types                                            | —                                                                                                    | Release form request                           | Release policy                                                     | Composer create/store/edit/update routes                                                  | `releases/create`, `releases/edit`, typed change-entry editor                                          | Validation rendering, row identity/order, keyboard smoke tests                                                             | 7, 8          |
| Detail: context, version/type, body, dates, edited indicator, ordered entries, owner visibility/actions, public copy link                              | —                                                                                                                           | Release/change-entry presentation types                                       | —                                                                                                    | —                                              | Release view/update/delete                                         | Authenticated and public release routes                                                   | Owner/public release detail and copy-link component                                                    | Inertia public-prop/owner-action/edited/order tests                                                                        | 9             |
| Accessibility, responsive behavior, confirmations, escaped plain text                                                                                  | —                                                                                                                           | —                                                                             | —                                                                                                    | Requests return field errors                   | —                                                                  | All modifying routes                                                                      | Persistent labels, non-color labels, focus behavior, announcements, 320px layout, confirmation dialogs | Accessibility review and browser smoke path                                                                                | 5, 6, 8-11    |
| Factories, fictional seed data, observability without journal content, future event seam                                                               | Seed primary/second demo users, 3 repositories, draft, 6 releases, all change types                                         | Factory states; event-friendly actions                                        | Existing actions expose seam only; no required event dispatch                                        | —                                              | —                                                                  | —                                                                                         | —                                                                                                      | Factory/seed tests; log-safety review                                                                                      | 2, 11         |
| Definition of done: private-first complete authoring loop, no scope creep, clean generated/secret state, full checks                                   | —                                                                                                                           | —                                                                             | —                                                                                                    | —                                              | —                                                                  | —                                                                                         | All completed pages                                                                                    | Full contract matrix; existing auth suite; browser path; final build                                                       | 11            |

## Deferred and prohibited checklist

Before accepting any prompt, confirm all remain absent unless Phase 1 contract
scope is explicitly amended:

- [ ] Watches, follows, maintainers, invitations, friends, connections, or
      mutual-follow relationships
- [ ] LGTM/reactions, reviews/comments/open issues, cherry-picks, deployments,
      forks, source attribution, adoption backlogs, notifications, or digests
- [ ] Watched/discovery/deployment/incident/release-train feeds; topics, tags,
      search, recommendations, trending, public counters, or analytics
- [ ] Scheduled publishing, recurring prompts, maintenance streaks, annual
      changelogs, release comparisons, or archive search
- [ ] Moderation queues, reports, blocks, mutes, direct messages, groups,
      teams, organizations, or communities
- [ ] AI release generation, inferred wellness/productivity scoring, avatar
      uploads, rich embeds, external publishing, newsletters, custom domains,
      mobile apps, separate API frontend, microservices, or generalized platform
      abstractions
- [ ] Speculative tables, polymorphic relations, endpoints, controllers,
      generated API contracts, feature flags, empty UI controls, disabled social
      buttons, or placeholder engagement modules for any deferred feature

## Unresolved decisions to settle before migrations

1. Canonical contract location: retain the current private storage path, or add
   the requested `docs/contracts/lifediff-phase-1.md` as the single source of
   truth without creating divergent copies.
2. `name` and `display_name`: decide whether registration stores the same value
   in both fields and whether later profile edits keep them synchronized while
   retaining `name` for authentication compatibility.
3. Repository name normalization: resolved by the `normalized_name` column and
   owner-scoped unique index. Later writes must populate the lowercased,
   normalized value.
4. Soft-deleted release-version reuse: resolved by the portable simple unique
   `(repository_id, version)` index. Versions remain permanently reserved after
   soft deletion; reuse requires an explicit future schema decision.
5. Deployment database: local development uses MySQL 8.4, but confirm the
   deployed MySQL version, collation, strict-mode expectation, and connection
   configuration before relying on environment-specific behavior.

## Verification gate

The repository defines these non-mutating checks for each implementation
prompt:

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse --no-progress --memory-limit=512M
php artisan test
npm run lint:check
npm run format:check
npm run types:check
```

Run the repository-defined production build when frontend bundling changes or
when a milestone completes:

```bash
npm run build
```

`composer test` and `composer ci:check` are also defined aggregate commands.
The baseline reports that this managed sandbox blocks their parallel Pint socket;
use the direct checks here and run the aggregate commands where that socket is
permitted. Do not use mutating `composer lint`, `npm run lint`, or `npm run
format` as verification commands.
