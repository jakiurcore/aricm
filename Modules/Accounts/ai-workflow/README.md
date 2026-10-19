# Accounts Module

Read this before changing the module or using its classes from elsewhere.

## Purpose
Admin users, roles/permissions (spatie, custom `Role` model with system roles), the `login`/`logout` routes laramod-admin needs, the `EnsureAdminAccess` gate for `/admin` (permission `admin.access`), admin Users/Roles API + React pages. Student guard and student login are added with the Students module.

## Public surface
- Routes: `login`, `login.store`, `logout`; API `accounts.api.*`.
- `RolesAndPermissionsSeeder` collects permissions from every `ProvidesPermissions` module; admin and super_admin get all, other roles get each module's `defaultRolePermissions()`. Re-run after adding permissions.
- `User::isSuperAdmin()`; super_admin passes every permission check (`hasPermissionTo` override).
- Roles: super_admin, admin, teacher, accountant (`Role::SYSTEM`, protected).

## Rules
- Never trust request role names: `UserRequest::roleNames()` re-queries; only a super admin may assign super_admin.
- Depends on Core only.
- `config/modules/admin.php` sets the admin middleware to `auth` + `EnsureAdminAccess`.
