<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\materialgroup;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class MaterialGroupController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $materialgroup = materialgroup::all();
        return view('admin.material_group.view_material_group', compact('materialgroup'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        //
        return view('admin.material_group.add_material_group');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        //
        $data = Input::all();
        
        $validationmessages = [
            'materialgroup.required' => 'Please enter MaterialGroup',
          
        ];

        $validator = Validator::make($data, [
                    'materialgroup' => 'required',
                   
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/addGroup/create')->withErrors($validator)->withInput(Input::all());
        }
        
       
        $data1 = materialgroup::create($data);



        session()->flash('flash_message', 'MaterialGroup created successfully...');
        return redirect('admin/addGroup');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
            
        $materialgroup = materialgroup::find($id);      
        return view('admin.material_group.add_material_group', compact('materialgroup'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        //
        $data = materialgroup::find($id);
        
        $dataInputs = Input::all();
        
         $validationmessages = [
            'materialgroup.required' => 'Please enter MaterialGroup',
          
        ];

        $validator = Validator::make($dataInputs, [
                    'materialgroup' => 'required',
                   
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/addGroup/'.$id.'/edit')->withErrors($validator)->withInput(Input::all());
        }
        
        $data->update($dataInputs);
        session()->flash('flash_message', 'MaterialGroup updated successfully...');
        return redirect('admin/addGroup');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        //
        $material_id = materialgroup::find($id);
        $material_id->delete($id);
        session()->flash('flash_message', 'MaterialGroup deleted successfully...');
        return redirect('admin/addGroup');
    }

}
