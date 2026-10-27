<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $this->repairZeroStaffIds();
        });

        $this->ensureStaffIdIsPrimaryAutoIncrement();
    }

    private function repairZeroStaffIds(): void
    {
        $zeroStaffRows = DB::table('staff')
            ->where('id', 0)
            ->orderBy('name')
            ->lockForUpdate()
            ->get(['name']);
        $zeroStaffByName = $zeroStaffRows->groupBy(fn ($staff) => $this->normalizeName($staff->name));

        if ($zeroStaffByName->contains(fn ($rows) => $rows->count() > 1)) {
            throw new RuntimeException('Cannot repair duplicate zero staff IDs because the staff names are ambiguous.');
        }

        $accountsByName = DB::table('users')
            ->where('staff_id', 0)
            ->orderBy('id_user')
            ->lockForUpdate()
            ->get(['id_user', 'nm_user'])
            ->groupBy(fn ($account) => $this->normalizeName($account->nm_user));

        if ($zeroStaffRows->isNotEmpty()) {
            $nextStaffId = ((int) DB::table('staff')->where('id', '<>', 0)->max('id')) + 1;

            foreach ($zeroStaffByName as $name => $staffRows) {
                $staff = $staffRows->first();
                $newStaffId = $nextStaffId++;
                $update = DB::table('staff')->where('id', 0);

                if (DB::getDriverName() === 'mysql') {
                    $update->whereRaw('BINARY `name` = ?', [$staff->name]);
                } else {
                    $update->where('name', $staff->name);
                }

                if ($update->update(['id' => $newStaffId]) !== 1) {
                    throw new RuntimeException('Could not safely assign a unique ID to a backfilled staff record.');
                }

                foreach ($accountsByName->get($name, collect()) as $account) {
                    DB::table('users')
                        ->where('id_user', $account->id_user)
                        ->where('staff_id', 0)
                        ->update(['staff_id' => $newStaffId]);
                }
            }
        }

        if (DB::table('users')->where('staff_id', 0)->exists()) {
            throw new RuntimeException('Some accounts still point to staff ID 0 after the repair.');
        }
    }

    public function down(): void
    {
        // Corrected IDs and account references are retained to avoid reintroducing duplicate IDs.
    }

    private function ensureStaffIdIsPrimaryAutoIncrement(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $indexes = collect(DB::select('SHOW INDEX FROM `staff`'));
        $primaryColumns = $indexes
            ->where('Key_name', 'PRIMARY')
            ->sortBy('Seq_in_index')
            ->pluck('Column_name')
            ->values()
            ->all();
        $idColumn = DB::selectOne("SHOW COLUMNS FROM `staff` LIKE 'id'");
        $isAutoIncrement = str_contains(strtolower((string) ($idColumn->Extra ?? '')), 'auto_increment');

        if ($primaryColumns !== [] && $primaryColumns !== ['id']) {
            throw new RuntimeException('The staff table uses an unexpected primary key; staff IDs were repaired but the key was not changed.');
        }

        $hasDuplicateIds = DB::table('staff')
            ->select('id')
            ->groupBy('id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateIds) {
            throw new RuntimeException('The staff table still contains duplicate IDs; primary key repair was stopped.');
        }

        if ($primaryColumns === []) {
            DB::statement('ALTER TABLE `staff` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`)');
        } elseif (!$isAutoIncrement) {
            DB::statement('ALTER TABLE `staff` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        }
    }

    private function normalizeName(?string $name): string
    {
        $visibleName = preg_replace('/[\p{Cf}\p{Z}\s]+/u', ' ', trim((string) $name));

        return mb_strtolower(trim($visibleName ?? (string) $name), 'UTF-8');
    }
};
