<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\milestone_type;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class MilestoneTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // display the default page in milestone view
        $milestone = milestone_type::all();
        return view('admin.milestone_type.view_milestone_type', compact('milestone'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.milestone_type.add_milestone_type');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
         $data = Input::all();
         
         $validationmessages = [
            'milestonetype.required' => 'Please enter milestonetype',
          
        ];

        $validator = Validator::make($data, [
                    'milestonetype' => 'required',
                   
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/addMilestone/create')->withErrors($validator)->withInput(Input::all());
        }
        
        
        $data1 = milestone_type::create($data);



        session()->flash('flash_message', 'milestone type created successfully...');
        return redirect('admin/addMilestone');


    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
         
        $milestone = milestone_type::find($id);
        
       
        return view('admin.milestone_type.add_milestone_type', compact('milestone'));
   
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = milestone_type::find($id);
        
        $dataInputs = Input::all();
        
         $validationmessages = [
            'milestonetype.required' => 'Please enter milestone type',
          
        ];

        $validator = Validator::make($dataInputs, [
                    'milestonetype' => 'required',
                   
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/addMilestone/'.$id.'/edit')->withErrors($validator)->withInput(Input::all());
        }
        
        $data->update($dataInputs);
        session()->flash('flash_message', 'milestone type updated successfully...');
        return redirect('admin/addMilestone');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
     $milestone = milestone_type::find($id);
      $milestone  ->delete();
        session()->flash('flash_message', 'milestone type deleted successfully...');
        return redirect('admin/addMilestone');
    }
}
