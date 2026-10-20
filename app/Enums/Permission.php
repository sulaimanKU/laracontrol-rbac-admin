<?php

namespace App\Enums;

enum Permission: string
{
    case Dashboard = 'access dashboard';
    case ViewUsers = 'view users';
    case ManageUsers = 'manage users';
    case ViewRoles = 'view roles';
    case ManageRoles = 'manage roles';
    case ViewAudit = 'view audit logs';
}
