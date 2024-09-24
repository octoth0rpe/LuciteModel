<?php

declare(strict_types=1);

namespace Lucite\Model\Tests;

use Lucite\Model\Model;

global $user;
$user = ['companyId' => 1];

class TestUserModel extends Model
{
    public static string $tableName = 'users';
    public static string $primaryKey = 'userId';
    public static array $columns = ['companyId', 'name', 'createdOn'];

    public function applyPermissionValues(array &$data): void
    {
        global $user;
        $data['companyId'] = $user['companyId'];
    }

    public function getPermissionFilter(array &$args): string
    {
        global $user;
        $placeholderName = '__permissionCompanyId';
        $args[$placeholderName] = $user['companyId'];
        return ' AND "companyId"=:'.$placeholderName;
    }
}
