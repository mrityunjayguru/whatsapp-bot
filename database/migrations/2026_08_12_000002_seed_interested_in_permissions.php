<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $actions = [
            'view'   => 'View',
            'create' => 'Create',
            'edit'   => 'Edit',
            'delete' => 'Delete',
        ];

        $rows = [];
        foreach ($actions as $actionKey => $actionLabel) {
            $rows[] = [
                'module'       => 'interested-in',
                'action'       => $actionKey,
                'name'         => 'interested-in.' . $actionKey,
                'display_name' => $actionLabel . ' Interested In',
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }

        DB::table('permissions')->insert($rows);
    }

    public function down(): void
    {
        DB::table('permissions')->where('module', 'interested-in')->delete();
    }
};
