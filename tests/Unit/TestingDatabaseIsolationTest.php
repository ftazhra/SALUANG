<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TestingDatabaseIsolationTest extends TestCase
{
    public function test_phpunit_forces_a_test_only_sqlite_database_and_cleanup_confirmation(): void
    {
        $this->assertSame('testing', $_SERVER['APP_ENV'] ?? null);
        $this->assertSame('sqlite', $_SERVER['DB_CONNECTION'] ?? null);
        $this->assertSame(':memory:', $_SERVER['DB_DATABASE'] ?? null);
        $this->assertSame('', $_SERVER['DB_URL'] ?? null);
        $this->assertTrue($_SERVER['SALUANG_CONFIRM_WHATSAPP_SCHEMA_CLEANUP'] ?? false);

        $this->assertSame('sqlite', getenv('DB_CONNECTION'));
        $this->assertSame(':memory:', getenv('DB_DATABASE'));
        $this->assertSame('', getenv('DB_URL'));
        $this->assertSame('1', getenv('SALUANG_CONFIRM_WHATSAPP_SCHEMA_CLEANUP'));
    }
}
