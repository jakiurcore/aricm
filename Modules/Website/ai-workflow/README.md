# Website Module

## Purpose
The public site: home, about, academics, contact, public exam routine, `sitemap.xml`, `robots.txt`. A **consumer**: it may depend on any module, but composes content from others only through contracts.

## How the home page is built
`Services\HomepageComposer` collects every module's `ProvidesHomepageSections` and sorts by `sortOrder`. Current order: Admission banner 10, Website program 20, divisions 30, principal 40, Notices latest 50, Results search widget 60, Gallery preview 70, stats 80, contact 90, hero 0. Add a block by implementing the contract in your module; no change to Website is needed.

## Shared layout
`core::layouts.public` (ticker from `ProvidesTicker`, nav filtered by `Route::has`, language switcher, footer, SEO/OG meta via `@section('title'|'description'|'og_image')`). New public pages should extend it. The nav list is `Core\Services\PublicSite::NAV` (route name → label key).

## Rules
- Draft exams never show on the public routine; the routine page shows a table from tablet up and cards on phones.
- Included section partials must not use `@props` (they are `@include`d, not components).
- Map coordinates come from `config('modules.website.map')` / `WEBSITE_MAP_LAT|LNG`; confirm the exact spot.
