<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Departmenttype;

class DepartmenttypeController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $departmenttype = Departmenttype::all();
        return view('admin.departmenttype.index', compact('departmenttype'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {

        return view('admin.departmenttype.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        // print_r($request);die;

        $this->validate($request, [
            'name' => 'required',
            'status' => 'required',
        ]);
        Departmenttype::create([
            'name' => $request->input('name'),
            'status' => $request->input('status'),
            'company_id' => Auth::user()->company_id
        ]);
        session()->flash('flash_message', 'Department created successfully...');
        return redirect('admin/departmenttype');
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
        $departmenttype = departmenttype::find($id);
        return view('admin.departmenttype.create', compact('departmenttype'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        //echo "<pre>";print_r($request);die;
        $departmenttype = Departmenttype::find($id);

        $this->validate($request, [
            'name' => 'required',
            'status' => 'required',
        ]);
        $departmenttype->update($request->all());
        session()->flash('flash_message', 'Department updated successfully...');
        return redirect('admin/departmenttype');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $departmenttype = Departmenttype::find($id);
        $departmenttype->delete();
        session()->flash('flash_message', 'Department deleted successfully...');
        return redirect('admin/departmenttype');
    }

}
