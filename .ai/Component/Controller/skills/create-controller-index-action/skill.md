---
name: create-controller-index-action
brick: "—"
component: Controller
step: 5
needs: [H1, G1, G2, G3]
produces: "indexAction() method — renders the grid listing page"
conditional: false
---

# create-controller-index-action

## Description
Documents the full implementation of the grid index action. Builds the SearchCriteria from the request, presents the grid, and renders the listing template.

## Context
- **Brick:** — — Step 5
- **Reads from:** H1 (controller skeleton), G1/G2/G3 (grid factory, filters, column definitions)
- **Writes to:** T1 (index.html.twig — receives the presented grid)
- **Artifact:** `{Domain}Controller.php` (edit H1 output)
- **PS example:** `src/PrestaShopBundle/Controller/Admin/Shipping/CarrierController.php`

## Instructions

1. Inject `ResponseBuilder $responseBuilder` and `GridPresenter $gridPresenter` (or use `$this->get()`).
2. Build `SearchCriteria` from `Request $request` using the grid filters form.
3. Build grid: `$grid = $this->get('{domain}.grid_factory')->getGrid($searchCriteria)`.
4. Present grid: `$presentedGrid = $gridPresenter->present($grid)`.
5. Return `$this->render('@PrestaShop/Admin/{Section}/{Domain}/index.html.twig', ['grid' => $presentedGrid])`.
6. Handle the filter reset action: if `$request->request->has('grid[{domain}][action]')` is reset, redirect without filters.

## Rules

- Never build raw SQL in the controller — delegate to the grid factory
- SearchCriteria must be built from the request, not hardcoded
