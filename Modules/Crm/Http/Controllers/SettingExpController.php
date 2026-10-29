<?php

namespace Modules\Crm\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Crm\Entities\SettingExp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SettingExpController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('setting-exp.index')) {
            return $denied;
        }

        $data['data'] = SettingExp::first();
        return view('crm::setting-exp.index2', $data);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('setting-exp.create')) {
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
        if ($denied = $this->requireAccess('setting-exp.store')) {
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
        if ($denied = $this->requireAccess('setting-exp.edit')) {
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
        if ($denied = $this->requireAccess('setting-exp.update')) {
            return $denied;
        }

        // dd($request->all());
        DB::beginTransaction();
        try {
            $settingExp = SettingExp::findOrFail($id);
            $settingExp->skala = $request->skala;
            $settingExp->value = $request->value;
            $settingExp->value_exp = $request->value * $request->skala;
            $settingExp->updated_by = Auth::id();
            $settingExp->save();

            DB::commit();

            return redirect()->route('setting-exp.index')->with('success', 'Pembuatan Setting Exp berhasil');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput($request->all())
                ->with('error', 'Pembuatan Setting Exp gagal' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('setting-exp.destroy')) {
            return $denied;
        }

        //
    }
}
