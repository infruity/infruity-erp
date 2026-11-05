<?php

namespace Modules\Master\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Master\Entities\Region;
use DB;

class RegionController extends Controller
{
    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('master::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('master::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('master::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }

    public function getProvince(Request $request)
    {
        $search = $request->get('search');

        $data = DB::table('reg_provinces')
            ->where('name', 'like', '%' . $search . '%')
            ->select('id', 'name')
            ->limit(10)
            ->get();
    
        return response()->json($data);
    }

    public function getCity(Request $request)
    {
        return DB::table('reg_regencies')
            ->where('province_id', $request->province_id)
            ->where('name', 'like', '%' . $request->search . '%')
            ->select('id', 'name')
            ->limit(20)
            ->get();
    }

    public function getDistrict(Request $request)
    {
        return DB::table('reg_districts')
            ->where('regency_id', $request->city_id)
            ->where('name', 'like', '%' . $request->search . '%')
            ->select('id', 'name')
            ->limit(20)
            ->get();
    }

    public function getVillage(Request $request)
    {
        return DB::table('reg_villages')
            ->where('district_id', $request->district_id)
            ->where('name', 'like', '%' . $request->search . '%')
            ->select('id', 'name')
            ->limit(20)
            ->get();
    }

}
