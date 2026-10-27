<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $accountsByName = DB::table('users')
                ->where('is_aktif', true)
                ->whereNull('staff_id')
                ->orderBy('id_user')
                ->lockForUpdate()
                ->get(['id_user', 'nm_user'])
                ->groupBy(fn ($account) => $this->normalizeName($account->nm_user));

            $staffByName = DB::table('staff')
                ->where('status', 'aktif')
                ->whereNull('user_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'name'])
                ->groupBy(fn ($staff) => $this->normalizeName($staff->name));

            foreach ($staffByName as $name => $staffRows) {
                $accountRows = $accountsByName->get($name, collect());

                if ($name === '' || $staffRows->count() !== 1 || $accountRows->count() !== 1) {
                    continue;
                }

                $staff = $staffRows->first();
                $account = $accountRows->first();

                $staffUpdated = DB::table('staff')
                    ->where('id', $staff->id)
                    ->where('status', 'aktif')
                    ->whereNull('user_id')
                    ->update(['user_id' => $account->id_user]);

                $accountUpdated = DB::table('users')
                    ->where('id_user', $account->id_user)
                    ->where('is_aktif', true)
                    ->whereNull('staff_id')
                    ->update(['staff_id' => $staff->id]);

                if ($staffUpdated !== 1 || $accountUpdated !== 1) {
                    throw new RuntimeException('Could not safely backfill the account–staff link.');
                }
            }
        });
    }

    public function down(): void
    {
        // Do not remove these links on rollback; they may have been edited since migration.
    }

    private function normalizeName(string $name): string
    {
        $visibleName = preg_replace('/[\p{Cf}\p{Z}\s]+/u', ' ', trim($name));

        return mb_strtolower(trim($visibleName ?? $name), 'UTF-8');
    }
};
