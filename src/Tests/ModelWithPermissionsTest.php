<?php

declare(strict_types=1);

namespace Lucite\Model\Tests;

use Lucite\Model\Model;
use Lucite\Model\Exception\NotFoundException;

global $user;
$user = ['companyId' => 1];

class UserModel extends Model
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

final class ModelWithPermissionsTest extends TestWithMocks
{
    public function setCompanyId(int $companyId): void
    {
        global $user;
        $user['companyId'] = $companyId;
    }

    public function testModelFetchOneWithPermissionsApplied(): void
    {
        [$db, $logger] = $this->setupDeps();
        $model = new UserModel($db, $logger);
        $this->setCompanyId(1);
        $user1 = $model->fetchOne(1);
        $this->assertEquals('company1user1', $user1['name']);
        $user2 = $model->fetchOne(2);
        $this->assertEquals('company1user2', $user2['name']);
    }

    public function testModelFetchOneCannotFetchOtherCompanyUsers(): void
    {
        $this->expectException(NotFoundException::class);
        [$db, $logger] = $this->setupDeps();
        $model = new UserModel($db, $logger);
        $this->setCompanyId(1);
        $model->fetchOne(3);
    }

    public function testModelFetchManyOnlyFetchesSameCompanyUsers(): void
    {
        [$db, $logger] = $this->setupDeps();
        $model = new UserModel($db, $logger);
        $this->setCompanyId(2);
        $results = $model->fetchMany();
        $this->assertEquals(2, count($results));
        $this->assertEquals('company2user1', $results[0]['name']);
        $this->assertEquals('company2user2', $results[1]['name']);
    }

    public function testUpdateForcesCompanyId(): void
    {
        [$db, $logger] = $this->setupDeps();
        $model = new UserModel($db, $logger);
        $this->setCompanyId(1);

        $beforeUpdate = $model->fetchOne(1);
        $this->assertEquals('company1user1', $beforeUpdate['name']);
        $this->assertEquals(1, $beforeUpdate['companyId']);

        $returned = $model->update(1, ['name' => 'company1user1-updated', 'companyId' => 3]);
        $this->assertEquals('company1user1-updated', $returned['name']);
        $this->assertEquals(1, $returned['companyId']);

        $fresh = $model->fetchOne(1);
        $this->assertEquals('company1user1-updated', $fresh['name']);
        $this->assertEquals(1, $fresh['companyId']);
    }

    public function testInsertForcesCompanyId(): void
    {
        [$db, $logger] = $this->setupDeps();
        $model = new UserModel($db, $logger);
        $this->setCompanyId(2);

        $returned = $model->create(['name' => 'company1user3', 'companyId' => 8]);
        $this->assertEquals('company1user3', $returned['name']);
        $this->assertEquals(5, $returned['userId']);
        $this->assertEquals(2, $returned['companyId']);
    }
}
