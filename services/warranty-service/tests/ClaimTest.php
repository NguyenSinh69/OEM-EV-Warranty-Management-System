<?php
use PHPUnit\Framework\TestCase;
use App\Models\Claim;
use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

class ClaimTest extends TestCase
{
    private static $testId;

    public function testCreateFindUpdateDelete()
    {
        // Tạo claim mới
        $data = [
            'vin' => 'VINTEST' . rand(1000,9999),
            'customer_id' => 123,
            'status' => 'PENDING',
            'description' => 'Unit test claim'
        ];
        $created = Claim::create($data);
        $this->assertIsArray($created);
        $this->assertArrayHasKey('id', $created);
        self::$testId = $created['id'];

        // Tìm claim vừa tạo
        $found = Claim::find(self::$testId);
        $this->assertEquals($created['id'], $found['id']);
        $this->assertEquals($data['vin'], $found['vin']);

        // Cập nhật claim
        $updated = Claim::update(self::$testId, ['status' => 'IN_PROGRESS', 'description' => 'Updated']);
        $this->assertEquals('IN_PROGRESS', $updated['status']);
        $this->assertEquals('Updated', $updated['description']);

        // Xóa claim
        $deleted = Claim::delete(self::$testId);
        $this->assertTrue($deleted);

        // Kiểm tra claim đã bị xóa
        $after = Claim::find(self::$testId);
        $this->assertNull($after);
    }

    public function testCreateInvalidVin()
    {
        $this->expectException(InvalidArgumentException::class);
        Claim::create([
            'vin' => '',
            'customer_id' => 1,
            'status' => 'PENDING',
        ]);
    }

    public function testCreateInvalidCustomerId()
    {
        $this->expectException(InvalidArgumentException::class);
        Claim::create([
            'vin' => 'VIN123',
            'customer_id' => 'abc',
            'status' => 'PENDING',
        ]);
    }

    public function testCreateInvalidStatus()
    {
        $this->expectException(InvalidArgumentException::class);
        Claim::create([
            'vin' => 'VIN123',
            'customer_id' => 1,
            'status' => 'INVALID',
        ]);
    }

    public function testUpdateInvalidVin()
    {
        $this->expectException(InvalidArgumentException::class);
        Claim::update('someid', ['vin' => '']);
    }

    public function testUpdateInvalidCustomerId()
    {
        $this->expectException(InvalidArgumentException::class);
        Claim::update('someid', ['customer_id' => 'abc']);
    }

    public function testUpdateInvalidStatus()
    {
        $this->expectException(InvalidArgumentException::class);
        Claim::update('someid', ['status' => 'INVALID']);
    }
}
