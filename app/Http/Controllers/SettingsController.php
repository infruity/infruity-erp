<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Master\Entities\Staff;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $staff = Staff::query()
            ->with('position')
            ->where('email', $user->email)
            ->first();

        $joinedAt = $staff?->date_in ?: $user->created_at;
        $joinedDate = $joinedAt ? Carbon::parse($joinedAt)->locale('id') : null;
        $tenure = $joinedAt ? Carbon::parse($joinedAt)->diff(Carbon::now()) : null;

        $tenureParts = [];
        if ($tenure) {
            $tenureParts[] = $tenure->y . ' Tahun';
            $tenureParts[] = $tenure->m . ' Bulan';
            $tenureParts[] = $tenure->d . ' Hari';
        }

        $branchNames = DB::table('user_branch')
            ->join('branch', 'branch.id', '=', 'user_branch.branch_id')
            ->where('user_branch.user_id', $user->id_user)
            ->orderBy('branch.name')
            ->pluck('branch.name');

        $roleName = data_get(session('role'), 'nm_role')
            ?: data_get(session('role'), 'name');
        $positionName = $staff?->position?->name;
        $jobTitles = collect([$roleName, $positionName])->filter()->unique()->values();

        return view('settings.index', [
            'user' => $user,
            'staff' => $staff,
            'joinedDateLabel' => $joinedDate?->translatedFormat('j F Y') ?? 'Belum diatur',
            'tenureLabel' => $tenureParts ? implode(', ', $tenureParts) : 'Belum diatur',
            'jobTitleLabel' => $jobTitles->isNotEmpty() ? $jobTitles->implode(', ') : 'Belum diatur',
            'branchLabel' => $branchNames->isNotEmpty() ? $branchNames->implode(', ') : 'Belum diatur',
        ]);
    }
}
