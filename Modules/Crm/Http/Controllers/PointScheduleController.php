<?php

namespace Modules\Crm\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Crm\Entities\CustomerTier;
use Modules\Crm\Entities\PointDecrement;
use Modules\Crm\Entities\PointSchedule;
use Modules\Crm\Entities\SettingExp;
use Modules\Crm\Support\ExpSettingsRules;
use Modules\Crm\Entities\PointFrequency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Modules\Crm\Entities\Tier;

class PointScheduleController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('point-schedule.index')) {
            return $denied;
        }

        $data['data'] = PointSchedule::first();
        $data['frequencies'] = PointFrequency::all();
        $data['exp'] = SettingExp::first();
        return view('crm::point-schedule.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('point-schedule.create')) {
            return $denied;
        }

        return view('crm::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('point-schedule.store')) {
            return $denied;
        }

        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('crm::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        if ($denied = $this->requireAccess('point-schedule.edit')) {
            return $denied;
        }

        return view('crm::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('point-schedule.update')) {
            return $denied;
        }

        $validated = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'frequency' => ['required', 'integer', 'exists:crm_point_frequency,value'],
            'break' => ['required', 'integer', 'in:1,2'],
        ] + ExpSettingsRules::rules());

        DB::beginTransaction();
        try {
            $settingPoint = PointSchedule::findOrFail($id);
            $settingPoint->start_date = $validated['start_date'];
            $settingPoint->end_date = $validated['end_date'];
            $settingPoint->frequency = $validated['frequency'];
            $settingPoint->break = $validated['break'];
            $settingPoint->updated_by = Auth::id();
            $settingPoint->save();

            $settingExp = SettingExp::findOrFail($id);
            $settingExp->skala = $validated['skala'];
            $settingExp->value = $validated['value'];
            $settingExp->value_exp = $validated['value'] * $validated['skala'];
            $settingExp->updated_by = Auth::id();
            $settingExp->save();

            $customerTier = CustomerTier::where('tier_level', '>', 1)->get();
            $tier = Tier::all()->pluck('exp', 'level')->toArray();

            foreach ($customerTier as $key => $value) {
                $customerId = $value->customer_id;
                $tierLevel = $value->tier_level;
                $newLevel = $tierLevel - 1;
                $decrementPoint = $value->customer_exp - $tier[$newLevel];

                PointDecrement::insert([
                    'customer_id' => $customerId,
                    'exp' => $decrementPoint,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            DB::commit();

            return redirect()->route('point-schedule.index')->with('success', 'Atur Jadwal Reset Automatis berhasil');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput($request->all())
                ->with('error', 'Pembuatan Atur Jadwal Reset Automatis gagal.');
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('point-schedule.destroy')) {
            return $denied;
        }

        //
    }
}
