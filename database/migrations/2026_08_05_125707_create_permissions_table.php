<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('module');        // lead-source, lead-status, leads
            $table->string('action');        // view, create, edit, delete
            $table->string('name')->unique(); // lead-source.view etc.
            $table->string('display_name');  // View Lead Source
            $table->timestamps();
        });

        // Seed all permissions
        $modules = [
            'lead-source'  => 'Lead Source',
            'lead-status'  => 'Lead Status',
            'leads'        => 'Leads',
        ];
        $actions = [
            'view'   => 'View',
            'create' => 'Create',
            'edit'   => 'Edit',
            'delete' => 'Delete',
        ];

        $rows = [];
        foreach ($modules as $moduleKey => $moduleLabel) {
            foreach ($actions as $actionKey => $actionLabel) {
                $rows[] = [
                    'module'       => $moduleKey,
                    'action'       => $actionKey,
                    'name'         => $moduleKey . '.' . $actionKey,
                    'display_name' => $actionLabel . ' ' . $moduleLabel,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }
        }
        DB::table('permissions')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
