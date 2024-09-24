<?php

declare(strict_types=1);

namespace Lucite\Model\Tests;

use Lucite\Model\Exception\NotFoundException;

final class SimpleModelTest extends TestWithMocks
{
    public function testModelFetchOne(): void
    {
        [$db, $logger] = $this->setupDeps();
        $model = new CompanyModel($db, $logger);
        $company1 = $model->fetchOne(1);

        $this->assertEquals('Company1', $company1['name']);
    }

    public function testModelFetchOneNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        [$db, $logger] = $this->setupDeps();
        $model = new CompanyModel($db, $logger);
        $model->fetchOne(3);
    }

    public function testModelFetchMany(): void
    {
        [$db, $logger] = $this->setupDeps();
        $model = new CompanyModel($db, $logger);
        $companies = $model->fetchMany();

        $this->assertEquals(2, count($companies));
        $this->assertEquals('Company1', $companies[0]['name']);
        $this->assertEquals('Company2', $companies[1]['name']);
    }

    public function testModelCanUpdateRow(): void
    {
        [$db, $logger] = $this->setupDeps();
        $model = new CompanyModel($db, $logger);
        $returned = $model->update(1, ['name' => 'Company1-updated']);
        $this->assertEquals('Company1-updated', $returned['name']);
        $fresh_result = $model->fetchOne(1);
        $this->assertEquals('Company1-updated', $fresh_result['name']);
    }

    public function testModelUpdateNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        [$db, $logger] = $this->setupDeps();
        $model = new CompanyModel($db, $logger);
        $model->update(3, ['name' => 'Company3']);
    }

    public function testModelCanInsertRow(): void
    {
        [$db, $logger] = $this->setupDeps();
        $model = new CompanyModel($db, $logger);
        $returned = $model->create(['name' => 'Company3']);
        $this->assertEquals('Company3', $returned['name']);
        $companies = $model->fetchMany();
        $this->assertEquals(3, count($companies));
        $fresh_result = $model->fetchOne(3);
        $this->assertEquals('Company3', $fresh_result['name']);
    }

    public function testModelCanDeleteRow(): void
    {
        [$db, $logger] = $this->setupDeps();
        $model = new CompanyModel($db, $logger);
        $before_count = $model->fetchMany();
        $this->assertEquals(2, count($before_count));
        $model->delete(2);
        $after_count = $model->fetchMany();
        $this->assertEquals(1, count($after_count));
    }
}
