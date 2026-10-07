<?php
namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PDO;
use DomainSystem\SystemApps\auth\Repositories\RoleRepository;
use DomainSystem\SystemApps\auth\Repositories\CapabilityRepository;

class AclRepositoriesTest extends TestCase
{
    private PDO $db;
    private RoleRepository $roles;
    private CapabilityRepository $caps;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $this->db->exec("CREATE TABLE roles (id INTEGER PRIMARY KEY, slug VARCHAR(50) UNIQUE, name VARCHAR(100), description VARCHAR(255), is_system_locked BOOLEAN DEFAULT 1)");
        $this->db->exec("CREATE TABLE capabilities (id INTEGER PRIMARY KEY, slug VARCHAR(100) UNIQUE, context VARCHAR(100))");
        $this->db->exec("CREATE TABLE role_capabilities (role_id INTEGER, capability_id INTEGER)");
        $this->db->exec("CREATE TABLE user_roles (user_id INTEGER, role_id INTEGER)");
        
        $connMock = $this->createMock(\DomainSystem\SystemApps\Database\Connection::class);
        $connMock->method('getPdo')->willReturn($this->db);

        $this->roles = new RoleRepository($connMock);
        $this->caps = new CapabilityRepository($connMock);
    }

    public function testRoleCrud()
    {
        $id = $this->roles->create([
            'slug' => 'editor',
            'name' => 'Editor Chefe',
            'description' => 'Edita os artigos',
            'is_system_locked' => 0
        ]);
        
        $this->assertGreaterThan(0, $id);
        
        $role = $this->roles->findById($id);
        $this->assertEquals('editor', $role['slug']);
        
        $this->roles->update($id, ['name' => 'Editor Supremo']);
        $role = $this->roles->findBySlug('editor');
        $this->assertEquals('Editor Supremo', $role['name']);
        
        $this->roles->delete($id);
        $this->assertNull($this->roles->findById($id));
    }

    public function testCapabilityCrud()
    {
        $id = $this->caps->create([
            'slug' => 'app.blog.write',
            'context' => 'blog'
        ]);
        
        $cap = $this->caps->findBySlug('app.blog.write');
        $this->assertEquals('blog', $cap['context']);
    }

    public function testRegisterCapabilityUpsert()
    {
        // 1. Should create a new capability
        $id1 = $this->caps->registerCapability('forum.post', 'Forum');
        $this->assertGreaterThan(0, $id1);
        
        // 2. Should NOT throw an error for unique constraint, should return existing ID
        $id2 = $this->caps->registerCapability('forum.post', 'Forum Novo Contexto');
        $this->assertEquals($id1, $id2);
        
        // Check DB has only 1
        $all = $this->caps->findAll();
        $this->assertCount(1, $all);
    }

    public function testUserRoleAndCapabilityResolution()
    {
        $roleId = $this->roles->create(['slug' => 'admin', 'name' => 'Admin', 'is_system_locked' => 1]);
        $capId = $this->caps->create(['slug' => '*', 'context' => 'system']);
        
        $this->roles->grantCapability($roleId, $capId);
        $this->roles->assignToUser(999, $roleId);
        
        $this->assertTrue($this->caps->userHasCapability(999, 'core.plugin.install')); // should be true because of '*'
        $this->assertTrue($this->caps->userHasCapability(999, 'anything'));
        $this->assertFalse($this->caps->userHasCapability(888, 'core.plugin.install'));
        
        $role2Id = $this->roles->create(['slug' => 'editor', 'name' => 'Editor']);
        $cap2Id = $this->caps->create(['slug' => 'write.post', 'context' => 'blog']);
        $this->roles->grantCapability($role2Id, $cap2Id);
        $this->roles->assignToUser(888, $role2Id);
        
        $this->assertTrue($this->caps->userHasCapability(888, 'write.post'));
        $this->assertFalse($this->caps->userHasCapability(888, 'core.plugin.install'));
    }
}
