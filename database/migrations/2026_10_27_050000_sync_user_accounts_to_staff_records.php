<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $staffByName = DB::table('staff')
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'name'])
                ->groupBy(fn ($staff) => $this->normalizeName($staff->name));

            $accountsByName = DB::table('users')
                ->orderBy('id_user')
                ->lockForUpdate()
                ->get(['id_user', 'nm_user', 'staff_id'])
                ->groupBy(fn ($account) => $this->normalizeName($account->nm_user));
            $nextStaffId = ((int) DB::table('staff')->max('id')) + 1;

            foreach ($accountsByName as $name => $accounts) {
                if ($name === '') {
                    continue;
                }

                $explicitStaffIds = $accounts->pluck('staff_id')
                    ->filter(fn ($staffId) => $staffId !== null)
                    ->map(fn ($staffId) => (int) $staffId)
                    ->unique()
                    ->values();

                if ($explicitStaffIds->count() > 1) {
                    continue;
                }

                if ($explicitStaffIds->count() === 1) {
                    $staffId = $explicitStaffIds->first();
                    if (!DB::table('staff')->where('id', $staffId)->exists()) {
                        continue;
                    }
                } else {
                    $staffMatches = $staffByName->get($name, collect());

                    if ($staffMatches->count() > 1) {
                        continue;
                    }

                    if ($staffMatches->count() === 1) {
                        $staffId = (int) $staffMatches->first()->id;
                    } else {
                        $displayName = $this->displayName($accounts->first()->nm_user);
                        if ($displayName === '') {
                            continue;
                        }

                        $staffId = $nextStaffId++;
                        DB::table('staff')->insert([
                            'id' => $staffId,
                            'name' => $displayName,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $staffByName->put($name, collect([(object) ['id' => $staffId, 'name' => $displayName]]));
                    }
                }

                foreach ($accounts as $account) {
                    if ($account->staff_id !== null) {
                        continue;
                    }

                    DB::table('users')
                        ->where('id_user', $account->id_user)
                        ->whereNull('staff_id')
                        ->update(['staff_id' => $staffId]);
                }
            }
        });
    }

    public function down(): void
    {
        // Keep links and generated staff rows; account/staff data may have changed after backfill.
    }

    private function normalizeName(?string $name): string
    {
        return mb_strtolower($this->displayName($name), 'UTF-8');
    }

    private function displayName(?string $name): string
    {
        $visibleName = preg_replace('/[\p{Cf}\p{Z}\s]+/u', ' ', trim((string) $name));

        return trim($visibleName ?? (string) $name);
    }
};
