<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Lucite\MockLogger\MockLogger;

class TestWithMocks extends TestCase
{
    public function setupDeps(): array
    {
        $db = new PDO('sqlite::memory:');
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->exec(file_get_contents(__DIR__.'/db.sql'));
        return [$db, new MockLogger()];
    }
}
