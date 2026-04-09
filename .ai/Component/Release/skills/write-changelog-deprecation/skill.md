---
name: write-changelog-deprecation
brick: R5
component: Release
step: 13
needs: [R4]
produces: "CHANGELOG.md entry announcing Admin{Domain}sController deprecation"
conditional: false
---

# write-changelog-deprecation

## Description
Add a deprecation entry to CHANGELOG.md (or the equivalent PS release notes document) announcing that the legacy controller is deprecated and will be removed in the next major version.

## Context
- **Brick:** R5 — Step 13
- **Reads from:** R4 (confirms deprecation notice was added)
- **Writes to:** (no further bricks — public announcement)
- **Artifact:** `CHANGELOG.md` (edit)
- **PS example:** Search `CHANGELOG.md` for existing `### Deprecated` sections to see the format

## Instructions

1. Open `CHANGELOG.md` (or the equivalent PS version-specific changelog).
2. Find the `### Deprecated` section for the current version.
3. Add:
   ```markdown
   ### Deprecated
   - `Admin{Domain}sController` is deprecated and will be removed in PrestaShop X.0.
     Use `{Domain}Controller` (Symfony) and the new admin routes instead.
     Migration guide: [link if available]
   ```
4. If there is no `### Deprecated` section, create one.
5. Also add a note to any developer-facing upgrade documentation.

## Rules

- Specify the target removal version (next major, e.g., PS 10.0)
- Link to migration guide or new route names so developers can self-serve
- Deprecation notice in changelog is for MODULE DEVELOPERS, not end users
