<?php

declare(strict_types=1);

namespace Lucite\Model\Tests;

use Lucite\Model\Model;
use Lucite\Model\NoPermissionCheckTrait;

class CompanyModel extends Model
{
    use NoPermissionCheckTrait;
    public static string $tableName = 'companies';
    public static string $primaryKey = 'companyId';
    public static array $columns = ['name', 'createdOn'];
    public static array $readonlyColumns = ['createdOn'];
}
