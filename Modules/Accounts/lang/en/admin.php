<?php

return [
    'menu_group' => 'Users',
    'users' => [
        'menu' => 'Users',
        'title' => 'Users',
        'new' => 'New user',
        'edit' => 'Edit user',
        'name' => 'Name',
        'email' => 'Email',
        'phone' => 'Mobile',
        'password' => 'Password',
        'password_hint' => 'Leave blank to keep the current password',
        'roles' => 'Roles',
        'status' => 'Status',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'last_login' => 'Last sign in',
        'never' => 'Never',
        'cannot_assign_super_admin' => 'Only a super admin can assign this role.',
    ],
    'roles' => [
        'menu' => 'Roles & permissions',
        'title' => 'Roles & permissions',
        'new' => 'New role',
        'name' => 'Role name (English, lowercase)',
        'permissions' => 'Permissions',
        'users_count' => 'Users',
        'system' => 'System',
        'names' => [
            'super_admin' => 'Super admin',
            'admin' => 'Admin',
            'teacher' => 'Teacher',
            'accountant' => 'Accountant',
        ],
        'groups' => [
            'admin' => 'Admin panel',
            'users' => 'Users',
            'roles' => 'Roles',
            'settings' => 'Settings',
        ],
    ],
];
