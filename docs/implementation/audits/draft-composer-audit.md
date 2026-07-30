# Draft composer audit

Date: 2026-07-30  
Scope: Phase 1 release drafts and the structured release composer  
Authority: `storage/app/private/docs/lifediff-phase-1.md`

## Result

**Pass after one privacy fix.** The draft-authoring loop is safe to proceed to
publication work. Publication behavior itself remains absent from this scope.

## Contract verification

| Check | Result | Evidence |
| --- | --- | --- |
| 1. An author can create an incomplete private draft | Pass | The create page defaults to private with one removable empty row. Store accepts an empty entry array, creates `state = draft`, and clears `published_at`. |
| 2. Zero entries are allowed | Pass | Both draft requests permit a present array with zero entries; feature coverage creates a draft with no entries. |
| 3. Empty rows are not persisted | Pass | Both requests remove whitespace-only rows before validation and action input; feature coverage confirms only non-empty entries persist. |
| 4. Entry order is contiguous and server-controlled | Pass | Requests submit ordered arrays; actions assign zero-based `sort_order` values inside their transactions. The `(release_id, sort_order)` unique index protects the persisted order. |
| 5. Cross-release IDs cannot be injected | Pass | Update validation verifies each accepted entry ID belongs to the routed release before any action runs; a feature test confirms no alteration occurs. |
| 6. Suggestions use publications, not drafts | Pass | `SuggestReleaseVersion` queries only `published()->chronological()`. Its test places a newer draft beside a published release and confirms the published version is used. |
| 7. Manual valid versions are accepted | Pass | The semantic-version request normalization accepts manual `major.minor.patch` input and persists its canonical form. |
| 8. Invalid and duplicate versions are rejected | Pass | The semantic parser rule rejects malformed values; the owner-scoped unique rule rejects collisions while permitting the same version in another repository. |
| 9. Draft visibility never grants public access | Pass | Drafts are never `published()`. `ReleasePolicy::view` denies all non-owners before selected future visibility is considered, and public queries require published state. |
| 10. Archived repositories reject draft writes | Pass | `ReleasePolicy::create`, `update`, and `delete` require an active repository; feature coverage exercises create and update rejection. |
| 11. Composer errors remain with the correct row | Pass | Stable client row IDs are React keys; indexed Laravel errors are displayed beside each current row. Validation redirects now explicitly return to the matching create or edit composer. Live verification covered an oversized entry error. |
| 12. Reordering works without a pointer | Pass | Native buttons provide Move up/Move down operations and focus the moved entry's content field. No drag-and-drop is present. |
| 13. Unsaved-change warning is truthful | Pass | The form uses Inertia dirty state for browser unload and Inertia navigation warnings. It makes no claim that changes have been saved. |
| 14. No autosave implication exists | Pass | The composer explicitly says changes are saved only through Save draft; no autosave, polling, background write, or draft-save endpoint exists. |
| 15. No social controls or placeholder counts exist | Pass | Targeted source and diff review found no follows, reactions, comments, feeds, notifications, analytics, counts, or deferred-feature placeholders. |
| 16. Public Inertia responses contain no draft data | Pass | Public profile and public repository controllers query published public releases only and explicitly serialize no draft, body, change-entry, owner-action, or count props. Existing public-prop assertions remain green. |

## Privacy findings

- **Fixed — private authoring routes returned 403 to a non-owner.** The create,
  store, update, and delete draft paths were policy-protected but form-request
  authorization converted a private-resource denial into 403. The release
  requests now return `AuthorizationException::asNotFound()` for non-owners of
  private repositories and drafts. Feature coverage asserts 404 for create,
  store, update, and delete attempts.
- Drafts may retain a selected future visibility, but this is only stored
  publication intent. No non-owner or guest can read a draft through a policy,
  scope, controller, or public Inertia response.
- No journal body or change-entry content is logged. The only author-facing
  Inertia props containing draft content are the owner-only edit page props.

## Transaction and failure review

`CreateReleaseDraft` wraps release creation and change-entry creation in one
`DB::transaction()`. `UpdateRelease` wraps release attribute updates, stale-row
deletion, temporary reordering, and entry saves in one `DB::transaction()`.
Any exception from an entry write, relationship save, or uniqueness constraint
escapes the closure and rolls back the release and all entry mutations together.

Update temporarily moves existing sort orders by 100 before assigning their
new contiguous zero-based values. The request limit of 50 entries and the
server-controlled invariant keep those temporary values outside the final
`0..49` range, avoiding collisions with the unique release/order constraint.
The action never accepts a repository ID, so a release cannot move repositories
as part of an update.

There is no swallowed database exception or compensating partial write path.
Validation, including foreign-entry ID ownership, completes before either
transaction begins.

## Deferred and prohibited scope

Publication, published-release editing, release detail, public release URLs,
and publication validation are intentionally not implemented by this audit.
No deferred social, discovery, analytics, moderation, scheduling, AI, API, or
mobile concepts were added.

## Verification

- `composer test` — passed: Pint, PHPStan, and 181 tests / 685 assertions.
- `npm run types:check` — passed.
- `npm run lint:check` — passed.
- `npm run format:check` — passed.
- `npm run build` — passed.
- `git diff --check` — passed.
