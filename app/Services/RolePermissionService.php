<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;

class RolePermissionService
{
    /**
     * Standard permission catalog organized by module
     */
    public static function getPermissionCatalog(): array
    {
        return [
            'conversations' => [
                'label' => 'Conversations & Chat',
                'description' => 'Live chat inbox, message replies, conversation status, and bot handoff.',
                'permissions' => [
                    'conversations.view'   => 'View Conversations',
                    'conversations.reply'  => 'Send Messages & Reply',
                    'conversations.assign' => 'Assign / Reassign Conversations',
                    'conversations.status' => 'Change Status (Open / Pending / Resolve / Close)',
                    'conversations.crm'    => 'Push Lead to CRM',
                ],
            ],
            'contacts' => [
                'label' => 'Contacts & Leads',
                'description' => 'Customer profiles, contact history, and customer details management.',
                'permissions' => [
                    'contacts.view'   => 'View Contacts',
                    'contacts.create' => 'Create New Contacts',
                    'contacts.edit'   => 'Edit Contact Details',
                    'contacts.delete' => 'Delete Contacts',
                ],
            ],
            'tags' => [
                'label' => 'Tags Management',
                'description' => 'Conversation and customer segmentation tags.',
                'permissions' => [
                    'tags.view'   => 'View Tags',
                    'tags.create' => 'Create Tags',
                    'tags.edit'   => 'Edit Tags',
                    'tags.delete' => 'Delete Tags',
                ],
            ],
            'faqs' => [
                'label' => 'Knowledge Base & FAQs',
                'description' => 'Chatbot training questions, answers, and FAQ categories.',
                'permissions' => [
                    'faqs.view'   => 'View FAQs & Categories',
                    'faqs.create' => 'Create FAQs & Categories',
                    'faqs.edit'   => 'Edit FAQs & Categories',
                    'faqs.delete' => 'Delete FAQs & Categories',
                ],
            ],
            'employees' => [
                'label' => 'Employee & Team Management',
                'description' => 'Staff profiles, agents, departments, and designations.',
                'permissions' => [
                    'employees.view'   => 'View Employees',
                    'employees.create' => 'Add New Employee',
                    'employees.edit'   => 'Edit Employee Details',
                    'employees.delete' => 'Delete Employee',
                    'departments.manage' => 'Manage Departments',
                    'designations.manage' => 'Manage Designations',
                ],
            ],
            'roles' => [
                'label' => 'Roles & Permissions',
                'description' => 'Define custom team roles and manage granular permission matrices.',
                'permissions' => [
                    'roles.view'   => 'View Roles & Permissions',
                    'roles.create' => 'Create Custom Roles',
                    'roles.edit'   => 'Edit Role Permissions',
                    'roles.delete' => 'Delete Custom Roles',
                ],
            ],
            'settings' => [
                'label' => 'Bot Settings & Integrations',
                'description' => 'AI prompt behavior, widget configuration, and WhatsApp number settings.',
                'permissions' => [
                    'settings.bot'      => 'Manage Bot Configuration',
                    'settings.channels' => 'Manage Channels (Widget / WhatsApp)',
                ],
            ],
        ];
    }

    /**
     * Ensure all permissions in catalog exist in the database
     */
    public static function seedPermissions(): void
    {
        $catalog = self::getPermissionCatalog();

        foreach ($catalog as $moduleKey => $module) {
            foreach ($module['permissions'] as $name => $label) {
                Permission::firstOrCreate(
                    ['name' => $name],
                    [
                        'module' => $moduleKey,
                        'module_label' => $module['label'],
                        'label' => $label,
                    ]
                );
            }
        }
    }

    /**
     * Ensure default roles exist for a tenant and have appropriate permissions
     */
    public static function ensureTenantDefaultRoles(?int $tenantId): void
    {
        self::seedPermissions();

        // 1. Admin Role (All Permissions)
        $adminRole = Role::firstOrCreate(
            ['tenant_id' => $tenantId, 'slug' => 'admin'],
            [
                'name' => 'Admin',
                'description' => 'Full administrative access across all modules, employees, and settings.',
                'is_system' => true,
            ]
        );
        $allPermIds = Permission::pluck('id')->all();
        $adminRole->permissions()->sync($allPermIds);

        // 2. Employee Role (Only Conversations Permissions by default)
        $employeeRole = Role::firstOrCreate(
            ['tenant_id' => $tenantId, 'slug' => 'employee'],
            [
                'name' => 'Employee',
                'description' => 'Standard employee with access to conversations and live chat.',
                'is_system' => true,
            ]
        );
        // Default only conversations permissions
        $employeePermIds = Permission::where('module', 'conversations')->pluck('id')->all();
        $employeeRole->permissions()->sync($employeePermIds);

        // Remove legacy predefined roles (manager, agent) created previously if they have no employees assigned
        Role::where('tenant_id', $tenantId)
            ->whereIn('slug', ['manager', 'agent'])
            ->where('is_system', true)
            ->whereDoesntHave('employees')
            ->delete();
    }
}
