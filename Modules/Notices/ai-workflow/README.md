# Notices Module

## Purpose
Notices with category, attachment (PDF/image ≤ 5 MB), pinning, ticker flag and scheduled publishing; public list/detail pages; the ticker items and the "latest notices" homepage block. **Depends on Core only** (the architecture test enforces it): API routes are gated with `can:` permission strings, never Accounts classes.

## Public surface
- `Services\NoticeDirectory`: `latest()`, `ticker()`, `paginate()`, `find()` — always visible notices only (`Notice::visible()`: published and `publish_at` not in the future).
- Implements `ProvidesHomepageSections` (`notices::home.latest`, order 50), `ProvidesTicker` (Core contract used by the shared public layout), `ProvidesPortalMenu` (route `portal.notices`, defined by StudentPortal), `ProvidesPermissions` (`notices.view|manage`).
- Public routes `notices.public.index|show`; admin API `notices.api.*` (multipart updates are spoofed `PUT`).

## Rules
- Never expose a notice outside `visible()`.
- Attachments are limited to pdf/jpg/png; replacing or removing one deletes the old file.
