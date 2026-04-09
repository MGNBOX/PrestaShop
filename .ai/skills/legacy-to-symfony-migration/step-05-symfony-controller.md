---
step: 5
title: "Symfony Controller"
skill: legacy-to-symfony-migration
previous: step-04-grid.md
next: step-06-routing.md
deliverable: "src/PrestaShopBundle/Controller/Admin/.../XxxController.php with all actions wired to the command/query bus"
---

# Step 5 — Symfony Controller

The controller is the HTTP entry point. It delegates entirely to the command bus (writes) and query bus (reads) — **no business logic lives here**. It is the thinnest layer in the architecture.

## Micro-Skills

> Full definitions: `.claude/migration-micro-skill-architecture.md`

| ID | Brick | Artifact | ⚠ |
|---|---|---|---|
| **H1** | `create-admin-controller` | `Controller/Admin/{Section}/{Domain}Controller.php` | — |

> **H1 must be committed together with H2 (`create-admin-routing`) and H3 (`register-feature-flag`)** — routes referencing an unregistered feature flag cause a 500.

## 5.1 — Class skeleton

```php
// src/PrestaShopBundle/Controller/Admin/{Section}/{Subsection}/{Domain}Controller.php
// Section: Sell, Improve, Configure, Catalog, etc.
// Subsection: Shipping, Catalog, etc.

#[AdminSecurity("is_granted('read', request.get('_legacy_controller'))")]
class {Domain}Controller extends PrestaShopAdminController
{
    // All dependencies injected via constructor or action-level injection
}
```

Extend `PrestaShopAdminController`, not the generic Symfony `AbstractController`. It provides:
- `$this->getCommandBus()` and `$this->getQueryBus()`
- `$this->addFlash()` with PS-specific flash types
- `$this->redirectToDefaultPage()` for fallback redirects
- Multi-store context helpers

## 5.2 — Listing action

```php
public function indexAction(
    Request $request,
    {Domain}Filters $filters
): Response {
    $grid = $this->get('prestashop.core.grid.factory.{domain}')->getGrid($filters);

    return $this->render('@PrestaShop/Admin/{Section}/{Subsection}/{Domain}s/index.html.twig', [
        'enableSidebar' => true,
        'layoutTitle' => $this->trans('{Domain}s', [], 'Admin.{Section}.Feature'),
        '{domain}Grid' => $this->presentGrid($grid),
        'showcaseCardName' => '{domain}',
    ]);
}
```

`{Domain}Filters` is auto-resolved by Symfony's argument resolver — it reads the session-persisted filters automatically.

## 5.3 — Search (filter) action

```php
#[AdminSecurity("is_granted('read', request.get('_legacy_controller'))")]
public function searchAction(Request $request): RedirectResponse
{
    $definitionFactory = $this->get('prestashop.core.grid.definition.factory.{domain}');
    $filteredRequest = $this->getFilteredRequest($request, $definitionFactory->getDefinition());

    return $this->redirectToRoute('admin_{domain}s_index', ['filters' => $filteredRequest]);
}
```

## 5.4 — Create action

```php
#[AdminSecurity("is_granted('create', request.get('_legacy_controller'))")]
public function createAction(Request $request): Response
{
    $form = $this->get('prestashop.core.form.identifiable_object.builder.{domain}_form_builder')
        ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        try {
            $domainId = $this->get('prestashop.core.form.identifiable_object.handler.{domain}_form_handler')
                ->handle($form);

            $this->addFlash('success', $this->trans(
                'Successful creation',
                [],
                'Admin.Notifications.Success'
            ));

            return $this->redirectToRoute('admin_{domain}s_edit', [
                '{domain}Id' => $domainId->getValue(),
            ]);
        } catch (Exception $e) {
            $this->addFlash('error', $this->getErrorMessageForException($e, $this->getErrorMessages()));
        }
    }

    return $this->render('@PrestaShop/Admin/{Section}/{Subsection}/{Domain}s/form.html.twig', [
        '{domain}Form' => $form->createView(),
        'layoutTitle' => $this->trans('Add new {domain}', [], 'Admin.{Section}.Feature'),
        'isEdit' => false,
    ]);
}
```

## 5.5 — Edit action

```php
#[AdminSecurity("is_granted('update', request.get('_legacy_controller'))")]
public function editAction(
    int {domain}Id,
    Request $request
): Response {
    $form = $this->get('prestashop.core.form.identifiable_object.builder.{domain}_form_builder')
        ->getFormFor({domain}Id);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        try {
            $this->get('prestashop.core.form.identifiable_object.handler.{domain}_form_handler')
                ->handleFor({domain}Id, $form);

            $this->addFlash('success', $this->trans(
                'Successful update',
                [],
                'Admin.Notifications.Success'
            ));

            return $this->redirectToRoute('admin_{domain}s_edit', [
                '{domain}Id' => {domain}Id,
            ]);
        } catch ({Domain}NotFoundException $e) {
            return $this->redirectToRoute('admin_{domain}s_index');
        } catch (Exception $e) {
            $this->addFlash('error', $this->getErrorMessageForException($e, $this->getErrorMessages()));
        }
    }

    try {
        $editable = $this->getQueryBus()->handle(new Get{Domain}ForEditing(
            new {Domain}Id({domain}Id)
        ));
    } catch ({Domain}NotFoundException $e) {
        return $this->redirectToRoute('admin_{domain}s_index');
    }

    return $this->render('@PrestaShop/Admin/{Section}/{Subsection}/{Domain}s/form.html.twig', [
        '{domain}Form' => $form->createView(),
        'layoutTitle' => $this->trans('Edit %name%', ['%name%' => $editable->getName()], 'Admin.{Section}.Feature'),
        'isEdit' => true,
        '{domain}Id' => {domain}Id,
    ]);
}
```

## 5.6 — Delete action

```php
#[AdminSecurity("is_granted('delete', request.get('_legacy_controller'))")]
public function deleteAction(int {domain}Id): RedirectResponse
{
    try {
        $this->getCommandBus()->handle(new Delete{Domain}Command(
            new {Domain}Id({domain}Id)
        ));
        $this->addFlash('success', $this->trans(
            'Successful deletion',
            [],
            'Admin.Notifications.Success'
        ));
    } catch ({Domain}NotFoundException $e) {
        // already gone — silently redirect
    } catch (CannotDelete{Domain}Exception $e) {
        $this->addFlash('error', $this->getErrorMessageForException($e, $this->getErrorMessages()));
    }

    return $this->redirectToRoute('admin_{domain}s_index');
}
```

## 5.7 — Bulk actions

```php
#[AdminSecurity("is_granted('delete', request.get('_legacy_controller'))")]
public function bulkDeleteAction(Request $request): RedirectResponse
{
    $ids = $this->getBulkActionIds($request, '{domain}s_bulk', 'id_{domain}');

    try {
        $this->getCommandBus()->handle(new BulkDelete{Domain}Command(
            array_map(static fn(string $id): {Domain}Id => new {Domain}Id((int) $id), $ids)
        ));
        $this->addFlash('success', $this->trans(
            'The selection has been successfully deleted.',
            [],
            'Admin.Notifications.Success'
        ));
    } catch (Exception $e) {
        $this->addFlash('error', $this->getErrorMessageForException($e, $this->getErrorMessages()));
    }

    return $this->redirectToRoute('admin_{domain}s_index');
}
```

Use `$this->getBulkActionIds()` from `PrestaShopAdminController` to extract the submitted checkbox IDs.

## 5.8 — Toggle status action

```php
#[AdminSecurity("is_granted('update', request.get('_legacy_controller'))")]
public function toggleStatusAction(int {domain}Id): JsonResponse
{
    try {
        $this->getCommandBus()->handle(new Toggle{Domain}StatusCommand(
            new {Domain}Id({domain}Id)
        ));
    } catch (Exception $e) {
        return $this->json(['status' => false, 'message' => $e->getMessage()]);
    }

    return $this->json(['status' => true]);
}
```

Toggle actions return JSON — the grid JS handles the visual update without a page reload.

## 5.9 — Position update action (if reorderable)

```php
#[AdminSecurity("is_granted('update', request.get('_legacy_controller'))")]
public function updatePositionAction(Request $request): JsonResponse
{
    try {
        $positionUpdateFactory = $this->get('prestashop.core.grid.position_update_factory');
        $positionUpdate = $positionUpdateFactory->buildPositionUpdate(
            $request->request->all('{domain}s'),
            $this->get('prestashop.core.grid.{domain}.position_definition')
        );
        $this->getCommandBus()->handle(new UpdateXxxPositionCommand($positionUpdate));
    } catch (Exception $e) {
        return $this->json(['status' => false]);
    }
    return $this->json(['status' => true]);
}
```

## 5.10 — Error message map

Define a private method mapping domain exceptions to user-readable strings:

```php
private function getErrorMessages(): array
{
    return [
        {Domain}NotFoundException::class => $this->trans(
            '{Domain} not found.',
            [],
            'Admin.Notifications.Error'
        ),
        CannotDelete{Domain}Exception::class => $this->trans(
            'Cannot delete this {domain}.',
            [],
            'Admin.Notifications.Error'
        ),
        {Domain}ConstraintException::class => [
            {Domain}ConstraintException::INVALID_NAME => $this->trans(
                'Invalid name.',
                [],
                'Admin.Notifications.Error'
            ),
        ],
    ];
}
```

## Checklist

- [ ] Controller extends `PrestaShopAdminController`
- [ ] `indexAction()` renders grid via grid factory
- [ ] `searchAction()` persists filters and redirects
- [ ] `createAction()` renders empty form on GET, handles submission on POST, redirects to edit on success
- [ ] `editAction()` renders populated form on GET (via form builder's `getFormFor()`), handles submission on POST
- [ ] `deleteAction()` dispatches `DeleteXxxCommand`, handles not-found silently
- [ ] `bulkDeleteAction()` uses `getBulkActionIds()`, dispatches `BulkDeleteXxxCommand`
- [ ] `bulkEnableStatusAction()` and `bulkDisableStatusAction()` implemented
- [ ] `toggleStatusAction()` returns JSON response
- [ ] `updatePositionAction()` implemented if entity is reorderable
- [ ] `getErrorMessages()` maps all domain exceptions to translated strings
- [ ] All actions have correct `#[AdminSecurity]` attributes
- [ ] Controller registered as a service (or auto-configured) in DI
