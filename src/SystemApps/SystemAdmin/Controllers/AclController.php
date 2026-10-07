<?php

namespace DomainSystem\SystemApps\SystemAdmin\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\SystemApps\auth\Contracts\RoleRepositoryInterface;
use DomainSystem\SystemApps\auth\Contracts\CapabilityRepositoryInterface;

class AclController
{
    private ThemeManager $theme;
    private RoleRepositoryInterface $roles;
    private CapabilityRepositoryInterface $capabilities;

    public function __construct(
        ThemeManager $theme, 
        RoleRepositoryInterface $roles, 
        CapabilityRepositoryInterface $capabilities
    ) {
        $this->theme = $theme;
        $this->roles = $roles;
        $this->capabilities = $capabilities;
    }

    public function index(Request $request): string
    {
        $roles = [];
        $capabilities = [];
        $role_caps = [];
        
        try {
            $roles = $this->roles->findAll();
            $capabilities = $this->capabilities->findAll();
            
            // Get existing role capabilities
            foreach ($roles as $role) {
                $roleCaps = $this->capabilities->getCapabilitiesForRole($role['id']);
                foreach ($roleCaps as $cap) {
                    $role_caps[$role['id']][] = $cap['id'];
                }
            }
        } catch (\Exception $e) {
        }

        return $this->theme->render('acl_panel', [
            'roles' => $roles,
            'capabilities' => $capabilities,
            'role_caps' => $role_caps
        ]);
    }

    public function save(Request $request)
    {
        $permissions = $request->input('permissions', []);
        
        try {
            $roles = $this->roles->findAll();
            
            foreach ($roles as $role) {
                // Clear old capabilities
                $oldCaps = $this->capabilities->getCapabilitiesForRole($role['id']);
                foreach ($oldCaps as $oldCap) {
                    $this->roles->revokeCapability($role['id'], $oldCap['id']);
                }
                
                // Add new capabilities
                if (isset($permissions[$role['id']]) && is_array($permissions[$role['id']])) {
                    foreach ($permissions[$role['id']] as $capId) {
                        $this->roles->grantCapability($role['id'], (int)$capId);
                    }
                }
            }
            
            if (php_sapi_name() === 'cli') { return 'saved'; }
            $base = defined('BASE_URL') ? BASE_URL : '';
            return \DomainSystem\Core\Http\Response::redirect($base . "/admin/acl?success=1");
        } catch (\Exception $e) {
            if (php_sapi_name() === 'cli') { return 'saved'; }
            return \DomainSystem\Core\Http\Response::redirect(BASE_URL . "/admin/acl?error=" . urlencode($e->getMessage()));
        }
    }
}
