<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Capacityunits;

class CapacityunitsController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $capacityunits = Capacityunits::all();
        return view('admin.capacityunits.index', compact('capacityunits'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {

        return view('admin.capacityunits.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $this->validate($request, [
            'name' => 'required',
            'description' => 'required',
            'status' => 'required',
        ]);
        Capacityunits::create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'status' => $request->input('status'),
            'company_id' => Auth::user()->company_id
        ]);
        session()->flash('flash_message', 'Capacity Units created successfully...');
        return redirect('admin/capacityunits');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        $capacityunits = Capacityunits::find($id);
        return view('admin.capacityunits.create', compact('capacityunits'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        $capacityunits = Capacityunits::find($id);
        $this->validate($request, [
            'name' => 'required',
            'description' => 'required',
            'status' => 'required',
        ]);
        $capacityunits->update($request->all());
        session()->flash('flash_message', 'Capacity units updated successfully...');
        return redirect('admin/capacityunits');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $capacityunits = Capacityunits::find($id);
        $capacityunits->delete();
        session()->flash('flash_message', 'Capacity units deleted successfully...');
        return redirect('admin/capacityunits');
    }

}
