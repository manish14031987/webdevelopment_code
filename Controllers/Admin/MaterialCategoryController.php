<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\materialcategory;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class MaterialCategoryController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $materialcategory = materialcategory::all();
        return view('admin.material_category.view_material_category', compact('materialcategory'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        //
        return view('admin.material_category.add_material_category');
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
            'materialcategory.required' => 'Please enter MaterialCategory',
          
        ];

        $validator = Validator::make($data, [
                    'materialcategory' => 'required',
                   
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/addCategory/create')->withErrors($validator)->withInput(Input::all());
        }
        
        
        $data1 = materialcategory::create($data);



        session()->flash('flash_message', 'MaterialCategory created successfully...');
        return redirect('admin/addCategory');
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
        
        $materialcategory = materialcategory::find($id);
       
        return view('admin.material_category.add_material_category', compact('materialcategory'));
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
        $data = materialcategory::find($id);
        
        $dataInputs = Input::all();
        
         $validationmessages = [
            'materialcategory.required' => 'Please enter MaterialCategory',
          
        ];

        $validator = Validator::make($dataInputs, [
                    'materialcategory' => 'required',
                   
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/addCategory/'.$id.'/edit')->withErrors($validator)->withInput(Input::all());
        }
        
        $data->update($dataInputs);
        session()->flash('flash_message', 'MaterialCategory updated successfully...');
        return redirect('admin/addCategory');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        //
        $material_id = materialcategory::find($id);
        $material_id->delete($id);
        session()->flash('flash_message', 'MaterialCategory deleted successfully...');
        return redirect('admin/addCategory');
    }

}
