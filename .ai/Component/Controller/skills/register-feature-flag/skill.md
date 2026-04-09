---
name: register-feature-flag
brick: H3
component: Controller
step: 10
needs: [H2]
produces: "feature_flag.xml entry with stability=beta and state=0"
conditional: false
---

# register-feature-flag

## Description
Register the domain's feature flag in `feature_flag.xml`. This entry populates the `ps_feature_flag` table at install/upgrade and enables the flag-based routing between legacy and Symfony controllers.

## Context
- **Brick:** H3 — Step 10
- **Reads from:** H2 (flag name used in `_legacy_feature_flag` — must match the `id` attribute here)
- **Writes to:** H2 (routing depends on this registration), step 12 (GA promotes this to stable)
- **Artifact:** `install-dev/data/xml/feature_flag.xml` (edit — add entry)
- **PS example:** Check existing entries in `install-dev/data/xml/feature_flag.xml`

## Instructions

1. Open `install-dev/data/xml/feature_flag.xml`.
2. Add inside `<entities>`:
   ```xml
   <feature_flag id="{domain}">
     <stability>beta</stability>
     <state>0</state>
     <label_wording>{Domain} page</label_wording>
     <label_domain>Admin.{Section}.Feature</label_domain>
     <description_wording>Enable the new Symfony-based {domain} management page (beta).</description_wording>
     <description_domain>Admin.{Section}.Feature</description_domain>
   </feature_flag>
   ```
3. The `id` attribute must exactly match `_legacy_feature_flag` values in H2.
4. Insert a row manually for local dev: `INSERT INTO ps_feature_flag (name, state, stability) VALUES ('{domain}', 0, 'beta');`
5. Enable for development: `UPDATE ps_feature_flag SET state=1 WHERE name='{domain}';`
6. Verify routing: visit legacy URL with flag OFF → legacy page. Enable flag → redirect to Symfony route.

## Rules

- H1 + H2 + H3 commit together — never ship routes referencing an unregistered flag
- stability starts as 'beta' always — promoted to 'stable' in step 12 only
- state starts as 0 — contributors enable manually; merchants never see it until GA
