<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Planningunit;

class PlanningunitController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $planningunit = Planningunit::all();
        return view('admin.planningunit.index', compact('planningunit'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {

        return view('admin.planningunit.create');
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
        Planningunit::create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'status' => $request->input('status'),
            'company_id' => Auth::user()->company_id
        ]);
        session()->flash('flash_message', 'Planning unit created successfully...');
        return redirect('admin/planningunit');
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
        $planningunit = Planningunit::find($id);
        return view('admin.planningunit.create', compact('planningunit'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        $planningunit = Planningunit::find($id);
        $this->validate($request, [
            'name' => 'required',
            'description' => 'required',
        ]);
        $planningunit->update($request->all());
        session()->flash('flash_message', 'Planning unit updated successfully...');
        return redirect('admin/planningunit');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $planningunit = Planningunit::find($id);
        $planningunit->delete();
        session()->flash('flash_message', 'Planning unit deleted successfully...');
        return redirect('admin/planningunit');
    }

}
