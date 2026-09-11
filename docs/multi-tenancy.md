# Multi‑tenancy Documentation

## Architecture
```
Organization
├── Users
├── Groups
├── Roles
├── Permissions
└── Resources
```

## Isolation Model
A standard user can only access resources that belong to **their own organisation**. The check flow is:
```
Authentication
    ↓
Organization / Tenant
    ↓
Policy
    ↓
Spatie Permission
    ↓
Resource
```
The policy first confirms the **tenant ownership** (`$user->organization_id === $resource->organization_id`) and then verifies the required **Spatie permission** (`$user->can('permission.name')`).

> **Why Spatie alone is insufficient** – Spatie permissions are global (or team‑scoped) and do not automatically enforce that the underlying model belongs to the same organisation. Without the tenant check a user could be granted a permission that applies to another organisation's data.

## Policies
All policies follow the same pattern:
```php
if ($user->can('permission.name') && $user->organization_id === $model->organization_id) {
    return true;
}
return false;
```
- `OrganizationPolicy` protects CRUD on organisations.
- `UserPolicy` protects CRUD on users.
- `GroupPolicy` protects CRUD on groups.
- (Future policies for `Document`, `Folder`, `Workflow` will adopt the same logic.)

### Super‑admin Role
The `super‑admin` role is **global** and bypasses tenant isolation via a global gate defined in `AuthServiceProvider`:
```php
Gate::before(function (User $user, $ability) {
    if ($user->hasRole('super-admin')) {
        return true; // grant all abilities
    }
});
```
This means a super‑admin can access **any** organisation and any resource, regardless of `organization_id`.

## Sanctum & Authentication
- **Web authentication** – sessions are used for the web UI.
- **Personal Access Tokens** – enabled via Laravel Sanctum (`personal_access_tokens` table) for API access.
- **Future mobile app** – the same token system will be consumed by a React Native client.
- The API is designed to serve the GED platform's future mobile and third‑party integrations.

## Commands
```bash
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
php artisan test --compact
vendor/bin/pint --format agent
git diff --check
```
These commands set up the database, seed roles/permissions, run the test suite, format the code, and ensure no style violations.
