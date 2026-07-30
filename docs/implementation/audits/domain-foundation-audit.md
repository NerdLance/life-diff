# Domain foundation audit

Date: 2026-07-30  
Scope: Phase 1 domain foundation only  
Authority: `storage/app/private/docs/lifediff-phase-1.md`

## Result

**Pass with follow-up work required before publication behavior.** The domain
foundation is safe to proceed to authorization. It contains no HTTP, Inertia,
controller, action, or social-feature implementation.

## Verification matrix

| Contract check                   | Evidence                                                                                                                                                                                                   | Result                     |
| -------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------- |
| PHP enums and TypeScript values  | Five PHP string-backed enums and `resources/js/types/lifediff.ts` contain the same status, visibility, release-state, release-type, and change-type values. Repository status aliases `ProfileStatus`.     | Pass                       |
| Semantic versions                | `SemanticVersion` permits only three numeric segments from 0-9999, normalizes padding, rejects `v`, prerelease, and build metadata; `ReleaseVersionSuggester` covers every release type and first release. | Pass                       |
| Visibility ceiling               | `VisibilityCeiling::allows()` implements all nine repository/release combinations and has exhaustive unit coverage.                                                                                        | Pass                       |
| Handle rule                      | `ReservedHandles` contains every contract-required handle and is case-insensitive. Full syntax/normalization validation remains a form-request concern.                                                    | Pass for foundation        |
| Schema and indexes               | Users, repositories, releases, and change entries contain required columns, FKs, unique/index constraints, soft deletes, and string-backed domain columns. MySQL fresh/rollback/re-migration passed.       | Pass                       |
| No deferred schema               | Migration review found no social, feed, tag, notification, moderation, polymorphic, or Phase 2 tables/relationships.                                                                                       | Pass                       |
| Public identifiers               | Repository/release `public_id` columns are unique `varchar(26)` ULIDs. `HasUlids` generates them; model update guards reject changes; route keys use `public_id`.                                          | Pass                       |
| Public URL safety                | No public route or URL has been added. The route-key contract reserves opaque `public_id` for release links, so no sequential release ID is planned for public URLs.                                       | Pass                       |
| Visibility values and defaults   | Repository/release columns are strings cast to `RepositoryVisibility`; migrations and factories default to private.                                                                                        | Pass                       |
| Draft/publication fields         | `state`, `published_at`, and `edited_at` exist; factories create drafts with no `published_at` and published releases with it.                                                                             | Pass for storage/factories |
| Soft deletes                     | Repository/release models use `SoftDeletes`; default queries omit trashed rows and model tests cover `withTrashed`.                                                                                        | Pass                       |
| Version uniqueness               | Unique `(repository_id, version)` index intentionally reserves versions after soft delete. This stricter portable strategy is documented in the contract and plan.                                         | Pass                       |
| Relationships, casts, and scopes | Required ownership, nesting, enum/date casts, ordered entries, and query scopes exist and are covered by tests. Scopes state that they do not replace policies.                                            | Pass                       |
| Factories                        | Repository and release visibility states now create compatible default parents. Draft/published factory invariants are tested.                                                                             | Pass after fix             |
| Journal logging                  | Domain classes, migrations, factories, and tests contain no application logging of release bodies or change-entry content.                                                                                 | Pass                       |
| Git diff                         | Diff contains only domain primitives, schema, models, factories, tests, and implementation/audit documentation; no controllers, routes, pages, or deferred-feature artifacts.                              | Pass                       |

## Findings fixed in this audit

- **Must fix before continuing - fixed:** `ReleaseFactory::public()` and
  `ReleaseFactory::unlisted()` originally inherited the default private
  repository factory, allowing an invalid published visibility combination.
  They now create public and unlisted repositories respectively, and tests
  assert compatible parent visibility.

## Should fix before publication work

- Publish/update actions must atomically enforce `state = published` with a
  non-null `published_at`, entry-count rules, archived-repository rejection,
  visibility ceiling, and version uniqueness. The model layer deliberately does
  not implement those workflows.
- Policies and route/query integration must turn the existing visibility scopes
  into owner/other-user/guest 404 behavior. Scopes alone are not authorization.
- Registration/profile requests must require, normalize, and validate handles;
  profile writes must populate `normalized_name` for repositories.

## Safe to defer beyond Phase 1

- A MySQL-backed automated test configuration remains a separate infrastructure
  decision. The local runtime uses MySQL 8.4 while the current suite retains an
  intentional in-memory SQLite compatibility lane.
- Version reuse after soft deletion can be reconsidered only through an
  explicit migration; it is not required for the chosen Phase 1 strategy.

## Prohibited scope creep

None found. No watches, follows, maintainers, invitations, reactions, reviews,
comments, cherry-picks, deployments, forks, notifications, feeds, tags,
search, analytics, moderation, messages, groups, organizations, API surface,
or speculative polymorphic relationships exist in the audited diff.
