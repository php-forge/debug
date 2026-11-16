---
name: php-forge-debug
description: Implement portable diagnostic collectors and declarative panels with the php-forge/debug contracts (CollectorInterface, Panel, PanelView and the Presenter value objects). Use when writing or reviewing a collector or panel that a host such as php-forge/debug-core renders. Not for the debugger engine, host wiring, storage, toolbar rendering or frontend code, which live in the host packages.
license: BSD-3-Clause
---

# Use php-forge/debug

## Resolve the package

The package requires PHP 8.3 or later. Choose APIs from the installed source: the Composer archive excludes `docs/`, so
in `vendor/php-forge/debug` the PHPDoc in `src/` is the reference for the installed version. A lockfile or branch alias
alone does not prove which files are installed. Installing this skill does not install dependencies or authorize
mutations.

## Package contracts

This package supplies contracts and presentation models, not a debugger engine or automatic activation.
`CollectorInterface` coordinates `startup()`, `capture()`, `shutdown()` and a stable `id()`. A null capture means absence;
an empty array remains a capture. The host validates JSON serializability before persistence, and nested values must
survive strict JSON encoding within 500 levels. Providers must omit secrets before returning data.

Read `Panel`, `PanelView` and the selected `Presenter` value objects before constructing a panel. Keep the collector
and panel identifiers aligned. Test repeated request lifecycles and released state. Debugger integrations are for local
development, not production; installing these contracts does not make an application safe to expose diagnostics.

Intended behavior that can look like a defect:

- `Panel::id()`, `name()` and `icon()` are final and throw `InvalidArgumentException` when the `ID`, `TITLE` or `ICON`
  constant is empty. Declare the constants; do not override the accessors. The host may override the title and icon.
- `Panel::present()` builds the view from the stored capture only. Do not query live services or the container there.
- `PanelView` is a `final readonly` class created with `PanelView::create()`. Every content method returns a new view,
  so discarding the return value loses the block.
- Inline content accepts scalars, `null` and `Presenter\Inline` values. `null`, `true` and `false` render as the text
  `null`, `true` and `false`; arrays and objects are rejected with `InvalidArgumentException`. Use `PanelView::value()`
  to display structured data.
- Table rows must be lists whose width matches the headers; a keyed or short row is rejected.
- `PanelView::link()` accepts only relative targets and the `http`, `https` and `mailto` schemes, and rejects targets
  with control characters or surrounding spaces. This blocks executable targets built from captured values; do not
  relax it.

## Verify the task

Run the relevant existing package checks and independent assertions for the selected operation. In this package's
checkout, `composer tests`, `composer static` and `composer check-dependencies` only read; `composer ecs` and
`composer rector` rewrite files. Use isolated synthetic inputs for side-effecting behavior. Report the PHP version and
missing dependencies.
