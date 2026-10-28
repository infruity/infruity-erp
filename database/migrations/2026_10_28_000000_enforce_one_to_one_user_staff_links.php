<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'staff_id')) {
            throw new RuntimeException('Cannot enforce account–staff links because users.staff_id does not exist.');
        }

        if (!Schema::hasColumn('staff', 'user_id')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable();
            });
        }

        DB::transaction(function () {
            DB::table('staff')->update(['user_id' => null]);

            $links = DB::table('users')
                ->whereNotNull('staff_id')
                ->when(Schema::hasColumn('users', 'is_aktif'), function ($query) {
                    $query->orderByRaw('CASE WHEN COALESCE(is_aktif, 0) = 1 THEN 0 ELSE 1 END');
                })
                ->orderBy('staff_id')
                ->orderBy('id_user')
                ->get(['id_user', 'staff_id']);

            foreach ($links->groupBy('staff_id') as $staffId => $accounts) {
                $staffId = (int) $staffId;
                $staffExists = DB::table('staff')->where('id', $staffId)->exists();

                if (!$staffExists) {
                    DB::table('users')->where('staff_id', $staffId)->update(['staff_id' => null]);
                    continue;
                }

                $keeper = $accounts->first();
                $duplicateIds = $accounts->skip(1)->pluck('id_user')->all();

                if ($duplicateIds !== []) {
                    DB::table('users')->whereIn('id_user', $duplicateIds)->update(['staff_id' => null]);
                }

                DB::table('staff')->where('id', $staffId)->update(['user_id' => $keeper->id_user]);
            }
        });

        if (!$this->hasUniqueIndex('users', ['staff_id'])) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('staff_id', 'users_staff_id_unique');
            });
        }

        if (!$this->hasUniqueIndex('staff', ['user_id'])) {
            Schema::table('staff', function (Blueprint $table) {
                $table->unique('user_id', 'staff_user_id_unique');
            });
        }
    }

    public function down(): void
    {
        // Duplicate links are intentionally not reconstructed; account records remain untouched.
    }

    private function hasUniqueIndex(string $table, array $columns): bool
    {
        return collect(Schema::getIndexes($table))->contains(
            fn (array $index) => ($index['unique'] ?? false) && ($index['columns'] ?? []) === $columns
        );
    }
};
