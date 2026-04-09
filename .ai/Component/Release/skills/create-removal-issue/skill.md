---
name: create-removal-issue
brick: R6
component: Release
step: 13
needs: [R4, R5]
produces: "GitHub issue tracking the actual removal of Admin{Domain}sController in the next major version"
conditional: false
---

# create-removal-issue

## Description
Create a GitHub issue that targets the actual removal of the legacy controller in the next major version. This issue tracks prerequisites (module developer migration, deprecation period) and assigns a milestone.

## Context
- **Brick:** R6 — Step 13
- **Reads from:** R4 (deprecation is in place), R5 (changelog entry confirms public announcement)
- **Writes to:** (no further bricks — tracks future work)
- **Artifact:** GitHub Issue (created via gh CLI or GitHub web UI)
- **PS example:** Search GitHub issues for "Remove deprecated Admin" to see existing removal issue format

## Instructions

1. Create a GitHub issue with title: `Remove deprecated Admin{Domain}sController`.
2. Body:
   ```markdown
   **Target:** PS X.0
   
   **Context:** `Admin{Domain}sController` was deprecated in PS Y.Z (PR #XXXXX).
   The new `{Domain}Controller` (Symfony) is GA since PS W.V (PR #YYYYY).
   
   **Prerequisites:**
   - [ ] Deprecation in place for at least 2 minor releases
   - [ ] Migration guide published
   - [ ] All known modules updated (verify with ecosystem team)
   
   **Files to delete:**
   - `controllers/admin/Admin{Domain}sController.php`
   - Legacy-only service registrations referencing this controller
   
   **Files to update:**
   - Remove `_legacy_controller: Admin{Domain}s` from routing YAML
   - Remove `_legacy_feature_flag: {domain}` from routing YAML
   - Clean up `feature_flag.xml` entry if no longer needed
   ```
3. Assign to the team's major release milestone.
4. Tag with: `deprecation`, `major-release`, `legacy-cleanup`.

## Rules

- Issue must be opened BEFORE the deprecation period ends — it tracks readiness
- Never create a PR for removal until all prerequisites in the issue are checked
- The issue must reference the deprecation PR and the GA PR by number
