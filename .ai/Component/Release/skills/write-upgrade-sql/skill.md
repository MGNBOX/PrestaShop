---
name: write-upgrade-sql
brick: R2
component: Release
step: 12
needs: [R1, H3]
produces: "upgrade/sql/{version}.sql — SQL to update flag for existing installations upgrading to this version"
conditional: "only if PS upgrade scripts do not auto-sync feature_flag.xml with ps_feature_flag table"
---

# write-upgrade-sql

## Description
Create the SQL upgrade file that updates the `ps_feature_flag` table for shops upgrading to the version containing the GA change. New installs get the flag from the XML; upgrading shops need this SQL.

## Context
- **Brick:** R2 — Step 12
- **Reads from:** R1 (the flag name that needs updating)
- **Writes to:** Applied automatically during PS upgrade process
- **Artifact:** `upgrade/sql/{PS_version}.sql`
- **PS example:** Check `upgrade/sql/` for existing SQL upgrade files

## Instructions

1. Determine the target PS version (e.g., 9.1.0) — check with the team.
2. Check if PS upgrade process already auto-syncs `feature_flag.xml` → `ps_feature_flag`. If yes, skip this skill.
3. If not auto-synced: create `upgrade/sql/{version}.sql` if it doesn't exist.
4. Add: `UPDATE ps_feature_flag SET state = 1, stability = 'stable' WHERE name = '{domain}';`
5. If the flag might not exist in older installations: use INSERT ... ON DUPLICATE KEY UPDATE.

## Rules

- Only create this file if the upgrade process does NOT auto-sync XML with DB
- SQL must be idempotent — safe to run multiple times
- Use the exact flag name from H3
