<?php

namespace Tests\Unit;

use App\Config\Database;
use mysqli;
use PHPUnit\Framework\TestCase;

class ConfigDatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        // Properly disconnect (closes the connection) then reset singleton
        Database::disconnect();
    }

    protected function tearDown(): void
    {
        Database::disconnect();
    }

    public function testConnectReturnsMysqliInstance(): void
    {
        $connection = Database::connect();
        $this->assertInstanceOf(mysqli::class, $connection);
    }

    public function testConnectReturnsSingletonInstance(): void
    {
        $connection1 = Database::connect();
        $connection2 = Database::connect();
        $this->assertSame($connection1, $connection2);
    }

    public function testGetInstanceReturnsSameAsConnect(): void
    {
        $fromConnect = Database::connect();
        $fromGetInstance = Database::getInstance();
        $this->assertSame($fromConnect, $fromGetInstance);
    }

    public function testConnectionCanExecuteQuery(): void
    {
        $connection = Database::connect();
        $result = $connection->query('SELECT 1 AS val');
        $this->assertNotFalse($result);

        $row = $result->fetch_assoc();
        $this->assertEquals(1, $row['val']);
    }

    public function testConnectionUsesEnvironmentVariables(): void
    {
        $_ENV['DB_HOST'] = getenv('DB_HOST') ?: 'db';
        $_ENV['DB_NAME'] = getenv('DB_NAME') ?: 'crud_app';
        $_ENV['DB_USER'] = getenv('DB_USER') ?: 'crud_user';
        $_ENV['DB_PASSWORD'] = getenv('DB_PASSWORD') ?: 'crud_password';

        $connection = Database::connect();
        $this->assertInstanceOf(mysqli::class, $connection);
    }

    public function testDisconnectClearsConnection(): void
    {
        Database::connect();
        Database::disconnect();

        $reflection = new \ReflectionClass(Database::class);
        $property = $reflection->getProperty('connection');
        $property->setAccessible(true);
        $this->assertNull($property->getValue());
    }

    public function testReconnectsAfterDisconnect(): void
    {
        $first = Database::connect();
        Database::disconnect();
        $second = Database::connect();

        $this->assertInstanceOf(mysqli::class, $second);
        $this->assertNotSame($first, $second);
    }

    public function testCharsetIsUtf8mb4(): void
    {
        $connection = Database::connect();
        $this->assertEquals('utf8mb4', $connection->character_set_name());
    }

    public function testErrorReportingIsEnabled(): void
    {
        $connection = Database::connect();

        // MySQLi should throw exceptions on errors (MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)
        $this->expectException(\mysqli_sql_exception::class);
        $connection->query('SELECT * FROM nonexistent_table_xyz');
    }
}
