# Copilot Instructions

FlexTFT is a Laravel 12 + Vue 3 + Inertia.js application for managing and simulating Teamfight Tactics compositions. All implementation must preserve the current architecture and raise the quality bar without introducing unrelated rewrites.

## Non-negotiable repository rule

This repository keeps source code free of comments.

Do not add code comments unless the user explicitly asks for them. This includes line comments, block comments, docblocks, PHPDoc, JSDoc, HTML comments, Vue template comments, Blade comments, CSS comments, and commented-out code.

Prefer clear names, smaller functions, typed signatures, expressive variables, and direct structure over explanatory comments. When code would need a comment to be understood, refactor the code first.

## Project shape

- The Laravel application lives in `app/`; the repository root contains the Makefile and Laradock setup.
- Backend source lives under `app/app`, routes under `app/routes`, migrations/factories/seeders under `app/database`, and tests under `app/tests`.
- Frontend source lives under `app/resources/js`, with Vue pages in `Pages`, layouts in `Layouts`, shared UI in `Components/UI`, domain components in `Components`, and reusable state logic in `composables`.
- Keep infrastructure changes scoped. Do not modify Laradock, generated assets, vendor files, build outputs, or environment files unless the task explicitly requires it.

## Architectural principles

- Follow the existing Laravel + Inertia + Vue architecture before inventing new patterns.
- Keep changes minimal, cohesive, and aligned with the module being touched.
- Prefer explicit data contracts between Laravel and Vue over implicit coupling.
- Keep business rules close to the backend domain and interactive UI state close to Vue composables/components.
- Avoid duplicated logic across backend and frontend. When duplication is unavoidable, keep the contract small and tested.
- Use framework features idiomatically: dependency injection, route names, middleware, Eloquent relationships/scopes/casts, Inertia responses, Vue `script setup`, and Tailwind utilities.
- Do not introduce new frameworks, state managers, UI kits, validation libraries, or architecture layers without a clear need and user approval.

## Backend architecture

- Use controllers in `app/app/Http/Controllers` to orchestrate requests, authorization, validation, persistence, and Inertia/JSON responses.
- Keep controllers readable. Small request-specific mapping helpers are acceptable, but move reusable or complex domain logic into services under `app/app/Services`.
- Inject services through constructors, as done with `TftDataService`.
- Use Eloquent relationships, query scopes, casts, factories, and model methods for model-owned behavior.
- Use named routes in `app/routes/web.php`; keep public routes, authenticated routes, and admin routes grouped consistently with the existing middleware structure.
- Keep admin behavior under the `App\Http\Controllers\Admin` namespace and protected by `auth` plus `admin` middleware.
- Use `Inertia::render()` for web screens and explicit `JsonResponse` endpoints only for API-style data such as `/api/tft-data` and health checks.
- Return only the data the frontend needs. Shape Inertia props deliberately and avoid leaking hidden or unrelated model attributes.
- Eager load relationships before mapping data that uses them to avoid N+1 queries.
- Use database transactions for operations that create, update, duplicate, import, or delete multiple related records when partial writes would leave invalid state.
- Keep flash messages user-facing and in Brazilian Portuguese, matching the existing tone.

## Authorization and validation

- Preserve the current authorization model: public global compositions, private user compositions, admin overrides, and `abort(403)` for forbidden access.
- Reuse existing ownership/admin checks when editing composition behavior. If checks grow, extract them cleanly instead of scattering conditionals.
- Validate all request input at the controller boundary or in a Form Request when the validation becomes shared or large.
- Never trust client-provided ownership, role, visibility, or computed score data.
- Keep validation rules in sync with Vue payloads for levels, board state, dispositions, champions, traits, items, and priorities.

## Data contracts

- `tftData` must keep the shape `{ champions, items, traits }` unless every consumer and test is updated.
- Composition levels are the fixed TFT levels `[3, 4, 5, 6, 7, 8, 9, 10]` throughout backend and frontend.
- `board_state` is JSON-backed and should behave as an object keyed by board cell. Empty board state should remain compatible with empty objects expected by Vue.
- Dispositions use the existing fields: `type`, `champion_ids`, `star_level`, `trait_id`, `trait_count`, `item_ids`, and `priority`.
- Normalize champion, item, trait, and board IDs to strings before comparing them when values can cross JSON, Vue state, or DOM boundaries.
- Keep summon, trait, item, and crafting behavior consistent between saved compositions, the editor, cards, and simulator results.

## Frontend architecture

- Use Vue 3 `<script setup>` and Inertia pages under `app/resources/js/Pages`.
- Use `AppLayout` or `GuestLayout` instead of creating page-local layout shells.
- Use existing UI primitives from `Components/UI` for buttons, inputs, textareas, modals, and empty states.
- Use domain components such as `HexBoard`, `HexCell`, `ChampionPanel`, `ItemPanel`, `SynergyPanel`, `LevelTabs`, and `DispositionEditor` instead of duplicating board or TFT UI behavior inside pages.
- Put reusable reactive behavior in composables under `composables`, following `useBoardState` for board state, derived traits, champion placement, item placement, and summon reconciliation.
- Keep pages responsible for page composition, routing actions, and coordinating child components. Keep reusable UI and domain behavior inside components/composables.
- Use Inertia `Link`, `router`, `usePage`, and the Ziggy `route()` helper for navigation and mutations.
- Preserve existing prop names and payload shapes unless the backend, frontend, and tests are updated together.
- Prefer computed values for derived UI state. Avoid storing duplicated derived state unless it solves a real interaction problem.
- Keep drag-and-drop, selection, modal, and board interactions deterministic and resilient to empty/null data.

## Frontend design system

- Preserve the current dark, dense, game-tool interface: gray backgrounds, subtle borders, compact spacing, yellow accents for brand/navigation, and blue accents for primary interaction.
- Use Tailwind utilities and existing CSS classes in `app/resources/css/app.css` before adding new global CSS.
- Keep reusable visual variants inside UI components when the pattern is shared.
- Prefer Heroicons for icon buttons and visual actions when they semantically fit the action.
- When Heroicons do not provide a good semantic match, a second Vue-friendly icon library may be added if it integrates cleanly and is used sparingly.
- Do not add inline SVGs or handcrafted icon markup; use installed icon libraries and pick the closest available icon when an exact match does not exist.
- Design for the actual tool workflow first. Do not add marketing-style landing sections, decorative cards, oversized hero content, or purely ornamental UI.
- Keep controls accessible: semantic buttons/links, disabled/loading states, labels or placeholders where appropriate, keyboard-friendly modals and forms, and no text overflow on common viewport sizes.
- Avoid nested cards and page sections that look like floating card stacks. Use cards for repeated items, modals, and genuinely framed tool panels.

## Tests

- When creating a new endpoint, always create corresponding feature tests covering status code, response structure, authorization, validation, and relevant business rules.
- When modifying an existing endpoint, review and update its feature tests if behavior, structure, authorization, validation, or response contracts changed.
- Use feature tests under `app/tests/Feature` and admin tests under `app/tests/Feature/Admin` following the current naming style.
- Use `RefreshDatabase` for database-backed feature tests.
- Mock `TftDataService` in tests that do not need real TFT JSON files.
- Use Inertia assertions for page endpoints, JSON structure assertions for API endpoints, and database assertions for persistence behavior.
- Cover guest, authenticated user, owner, non-owner, and admin cases whenever authorization rules are involved.
- Do not broaden test scope to unrelated behavior while fixing a focused issue.

## Commands and quality checks

- Run commands from the repository root unless a command explicitly requires `app/`.
- Use `make test` for the PHPUnit suite in the Docker/Laradock environment.
- Use `make format` to run comment stripping, Prettier, and Pint when formatting changes are needed.
- Use `make format-check` when checking formatting without applying changes.
- Use `make sync` only when TFT source data needs to be refreshed.
- Do not commit changes, create branches, or alter generated build artifacts unless the user explicitly asks.

## Implementation discipline

- Read surrounding files before editing and match local style.
- Preserve Portuguese UI copy and existing route/page naming conventions.
- Prefer small, direct functions over broad abstractions.
- Add an abstraction only when it removes real duplication or protects a clear domain boundary.
- Do not perform unrelated refactors while implementing requested behavior.
- Keep security and privacy in mind: no trusting client-side role checks, no exposing sensitive user fields, no mass assignment beyond intended fillable fields, and no unsafe file or path handling.
- Update README or project documentation only when setup, commands, behavior, or user-facing workflows change.
