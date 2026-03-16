<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class ModelsUserTest extends TestCase
{
    private User $user;

    /** @var int[] IDs to clean up after each test */
    private array $createdIds = [];

    protected function setUp(): void
    {
        $this->user = new User();
    }

    protected function tearDown(): void
    {
        // Clean up all records created during this test
        foreach ($this->createdIds as $id) {
            $this->user->delete($id);
        }
    }

    private function createTestUser(array $overrides = []): int
    {
        $data = array_merge([
            'name' => 'Test User',
            'email' => 'test_' . uniqid('', true) . '@example.com',
            'phone' => '+1-234-567-8900',
        ], $overrides);

        $id = $this->user->create($data);
        $this->assertNotNull($id);
        $this->createdIds[] = $id;
        return $id;
    }

    // ---------------------------------------------------------------
    // Create
    // ---------------------------------------------------------------

    public function testCreateReturnsPositiveIntId(): void
    {
        $id = $this->createTestUser();
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }

    public function testCreatePersistsAllFields(): void
    {
        $data = [
            'name' => 'Alice Smith',
            'email' => 'alice_' . uniqid('', true) . '@example.com',
            'phone' => '+1-555-000-1234',
        ];

        $id = $this->createTestUser($data);
        $fetched = $this->user->getById($id);

        $this->assertEquals($data['name'], $fetched['name']);
        $this->assertEquals($data['email'], $fetched['email']);
        $this->assertEquals($data['phone'], $fetched['phone']);
    }

    public function testCreateWithMinimalData(): void
    {
        $id = $this->createTestUser(['phone' => '']);
        $fetched = $this->user->getById($id);

        $this->assertIsArray($fetched);
        $this->assertEmpty($fetched['phone']);
    }

    public function testCreateSetsTimestamps(): void
    {
        $id = $this->createTestUser();
        $fetched = $this->user->getById($id);

        $this->assertNotEmpty($fetched['created_at']);
        $this->assertNotEmpty($fetched['updated_at']);
    }

    // ---------------------------------------------------------------
    // Read
    // ---------------------------------------------------------------

    public function testGetByIdReturnsCorrectUser(): void
    {
        $id = $this->createTestUser(['name' => 'Fetchable User']);
        $fetched = $this->user->getById($id);

        $this->assertIsArray($fetched);
        $this->assertEquals($id, $fetched['id']);
        $this->assertEquals('Fetchable User', $fetched['name']);
    }

    public function testGetByIdReturnsNullForNonexistentUser(): void
    {
        $this->assertNull($this->user->getById(99999));
    }

    public function testGetAllReturnsArray(): void
    {
        $users = $this->user->getAll(10, 0);
        $this->assertIsArray($users);
    }

    public function testGetAllRespectsLimit(): void
    {
        // Create 3 users, request limit of 2
        $this->createTestUser();
        $this->createTestUser();
        $this->createTestUser();

        $users = $this->user->getAll(2, 0);
        $this->assertLessThanOrEqual(2, count($users));
    }

    public function testGetAllRespectsOffset(): void
    {
        $id1 = $this->createTestUser();
        $id2 = $this->createTestUser();
        $id3 = $this->createTestUser();

        $all = $this->user->getAll(100, 0);
        $offset = $this->user->getAll(100, count($all) - 1);

        $this->assertCount(1, $offset);
    }

    public function testGetCountReturnsInt(): void
    {
        $count = $this->user->getCount();
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testGetCountIncrementsAfterCreate(): void
    {
        $before = $this->user->getCount();
        $this->createTestUser();
        $after = $this->user->getCount();

        $this->assertEquals($before + 1, $after);
    }

    // ---------------------------------------------------------------
    // Update
    // ---------------------------------------------------------------

    public function testUpdateReturnsTrueOnSuccess(): void
    {
        $id = $this->createTestUser();
        $result = $this->user->update($id, [
            'name' => 'Updated Name',
            'email' => 'updated_' . uniqid('', true) . '@example.com',
            'phone' => '+1-000-000-0000',
        ]);

        $this->assertTrue($result);
    }

    public function testUpdatePersistsChanges(): void
    {
        $id = $this->createTestUser();
        $newEmail = 'changed_' . uniqid('', true) . '@example.com';

        $this->user->update($id, [
            'name' => 'Changed Name',
            'email' => $newEmail,
            'phone' => '+1-111-222-3333',
        ]);

        $fetched = $this->user->getById($id);
        $this->assertEquals('Changed Name', $fetched['name']);
        $this->assertEquals($newEmail, $fetched['email']);
        $this->assertEquals('+1-111-222-3333', $fetched['phone']);
    }

    // ---------------------------------------------------------------
    // Delete
    // ---------------------------------------------------------------

    public function testDeleteReturnsTrueOnSuccess(): void
    {
        $id = $this->createTestUser();
        // Remove from cleanup list since we're deleting manually
        $this->createdIds = array_diff($this->createdIds, [$id]);

        $this->assertTrue($this->user->delete($id));
    }

    public function testDeleteRemovesUser(): void
    {
        $id = $this->createTestUser();
        $this->createdIds = array_diff($this->createdIds, [$id]);

        $this->user->delete($id);
        $this->assertNull($this->user->getById($id));
    }

    public function testDeleteDecrementsCount(): void
    {
        $id = $this->createTestUser();
        $before = $this->user->getCount();

        $this->createdIds = array_diff($this->createdIds, [$id]);
        $this->user->delete($id);

        $this->assertEquals($before - 1, $this->user->getCount());
    }

    // ---------------------------------------------------------------
    // Email Exists
    // ---------------------------------------------------------------

    public function testEmailExistsReturnsTrueForExistingEmail(): void
    {
        $email = 'exists_' . uniqid('', true) . '@example.com';
        $this->createTestUser(['email' => $email]);

        $this->assertTrue($this->user->emailExists($email));
    }

    public function testEmailExistsReturnsFalseForUnknownEmail(): void
    {
        $this->assertFalse($this->user->emailExists('nope_' . uniqid('', true) . '@example.com'));
    }

    public function testEmailExistsExcludesGivenId(): void
    {
        $email = 'exclude_' . uniqid('', true) . '@example.com';
        $id = $this->createTestUser(['email' => $email]);

        // Same email but excluded ID — should return false (used during update)
        $this->assertFalse($this->user->emailExists($email, $id));

        // Without exclusion — should return true
        $this->assertTrue($this->user->emailExists($email));
    }

    // ---------------------------------------------------------------
    // Search
    // ---------------------------------------------------------------

    public function testSearchByNameFindsUser(): void
    {
        $uniqueName = 'UniqueSearchName_' . uniqid();
        $id = $this->createTestUser(['name' => $uniqueName]);

        $results = $this->user->search($uniqueName);
        $this->assertCount(1, $results);
        $this->assertEquals($id, $results[0]['id']);
    }

    public function testSearchByEmailFindsUser(): void
    {
        $email = 'searchable_' . uniqid('', true) . '@example.com';
        $this->createTestUser(['email' => $email]);

        $results = $this->user->search($email);
        $this->assertCount(1, $results);
        $this->assertEquals($email, $results[0]['email']);
    }

    public function testSearchWithPartialMatch(): void
    {
        $prefix = 'Partial_' . uniqid();
        $id = $this->createTestUser(['name' => $prefix . ' FullName']);

        $results = $this->user->search($prefix);
        $this->assertGreaterThanOrEqual(1, count($results));

        $ids = array_column($results, 'id');
        $this->assertContains($id, $ids);
    }

    public function testSearchReturnsEmptyForNoMatch(): void
    {
        $results = $this->user->search('zzz_no_match_' . uniqid());
        $this->assertIsArray($results);
        $this->assertCount(0, $results);
    }

    public function testSearchRespectsLimit(): void
    {
        $tag = 'LimitSearch_' . uniqid();
        $this->createTestUser(['name' => $tag . ' One']);
        $this->createTestUser(['name' => $tag . ' Two']);
        $this->createTestUser(['name' => $tag . ' Three']);

        $results = $this->user->search($tag, 2, 0);
        $this->assertLessThanOrEqual(2, count($results));
    }
}
