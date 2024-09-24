<?php

declare(strict_types=1);

namespace Lucite\Model\Tests;

final class WarningTest extends TestWithMocks
{
    public function testSetReadOnlyColumnAddsWarning(): void
    {
        [$db, $logger] = $this->setupDeps();
        $model = new CompanyModel($db, $logger);
        $unsettableValue = '1970-01-01T00:00:00Z';

        # We should have 0 warnings at the start of the test.
        $warnings = $model->getWarnings();
        $this->assertEquals(0, count($warnings));

        # Make sure that the value already in the db is not $unsettableValue.
        # Should not happen unless you manage to run the unit test exactly on
        # 1970-01-01T00:00:00Z, which means either you've set your clock back
        # or you have a time machine (go you!)
        $beforeUpdate = $model->fetchOne(1);
        $this->assertNotEquals($beforeUpdate['createdOn'], $unsettableValue);

        # Make sure createdOn didn't actually change
        $returned = $model->update(1, ['name' => 'Company1-updated', 'createdOn' => $unsettableValue]);
        $this->assertEquals($returned['createdOn'], $beforeUpdate['createdOn']);

        # Do one more fetch from the db to ensure it hasn't changed there either
        $afterUpdate = $model->fetchOne(1);
        $this->assertNotEquals($afterUpdate['createdOn'], $unsettableValue);

        # We should have one warning
        $warnings = $model->getWarnings();
        $this->assertEquals(1, count($warnings));

        # Reset and check warnings. Should have 0 again.
        $model->resetWarnings();
        $warnings = $model->getWarnings();
        $this->assertEquals(0, count($warnings));
    }
}
