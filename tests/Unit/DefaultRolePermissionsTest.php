<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Role;
use PHPUnit\Framework\TestCase;

class DefaultRolePermissionsTest extends TestCase
{
    public function test_accountant_role_cannot_manage_billing_by_default(): void
    {
        $accountantPermissions = Role::defaultRolePermissions()['Accountant'];

        $this->assertContains(
            Permission::PERMISSION_EXPORT_TRANSACTIONS,
            $accountantPermissions,
        );
        $this->assertNotContains(
            Permission::PERMISSION_MANAGE_BILLING,
            $accountantPermissions,
        );
    }
}
