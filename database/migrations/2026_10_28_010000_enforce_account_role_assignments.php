<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('role_user')) {
            throw new RuntimeException('Cannot enforce role assignments because role_user does not exist.');
        }

        DB::transaction(function () {
            $duplicates = DB::table('role_user')
                ->select('id_user', 'id_role')
                ->groupBy('id_user', 'id_role')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($duplicates as $duplicate) {
                $keepId = DB::table('role_user')
                    ->where('id_user', $duplicate->id_user)
                    ->where('id_role', $duplicate->id_role)
                    ->when(Schema::hasColumn('role_user', 'deleted_at'), function ($query) {
                        $query->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END');
                    })
                    ->orderBy('id_ru')
                    ->value('id_ru');

                DB::table('role_user')
                    ->where('id_user', $duplicate->id_user)
                    ->where('id_role', $duplicate->id_role)
                    ->where('id_ru', '<>', $keepId)
                    ->delete();
            }
        });

        $this->ensureRoleUserPrimaryKey();

        if (!$this->hasUniqueIndex('role_user', ['id_user', 'id_role'])) {
            Schema::table('role_user', function (Blueprint $table) {
                $table->unique(['id_user', 'id_role'], 'role_user_account_role_unique');
            });
        }
    }

    public function down(): void
    {
        // Keep the repaired surrogate key and deduplicated assignments intact.
    }

    private function ensureRoleUserPrimaryKey(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $indexes = Schema::getIndexes('role_user');
        $primary = collect($indexes)->first(fn (array $index) => $index['primary'] ?? false);

        if ($primary && ($primary['columns'] ?? []) !== ['id_ru']) {
            throw new RuntimeException('The existing role_user primary key is not id_ru; refusing to rewrite role assignment identifiers.');
        }

        if (DB::table('role_user')->whereNull('id_ru')->exists()) {
            throw new RuntimeException('role_user.id_ru contains NULL values; repair them before enforcing role assignment keys.');
        }

        $hasDuplicateIds = DB::table('role_user')
            ->select('id_ru')
            ->groupBy('id_ru')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateIds) {
            throw new RuntimeException('role_user.id_ru contains duplicate values; repair them before enforcing role assignment keys.');
        }

        if (!$primary) {
            DB::statement('ALTER TABLE role_user ADD PRIMARY KEY (id_ru)');
        }

        DB::statement('ALTER TABLE role_user MODIFY id_ru INT NOT NULL AUTO_INCREMENT');
    }

    private function hasUniqueIndex(string $table, array $columns): bool
    {
        return collect(Schema::getIndexes($table))->contains(
            fn (array $index) => ($index['unique'] ?? false) && ($index['columns'] ?? []) === $columns
        );
    }
};
