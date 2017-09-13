<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Costcentretype;


class CostcentretypeController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $costcentretype = Costcentretype::all();
        return view('admin.costcentretype.index', compact('costcentretype'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        
        return view('admin.costcentretype.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       $this->validate($request, [
            'name' => 'required',
            'description' => 'required',
            'status' => 'required',
        ]);

        $create = $request->all();
        $create['company_id'] = Auth::user()->company_id;
        Costcentretype::create($create);
        
        session()->flash('flash_message', 'Cost centre type created successfully...');
        return redirect('admin/costcentretype');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $costcentretype = Costcentretype::find($id);
        return view('admin.costcentretype.create', compact('costcentretype'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $costcentretype = Costcentretype::find($id);
        $this->validate($request, [
            'name' => 'required',
            'description' => 'required',
            'status' => 'required',
        ]); 
        $costcentretype->update($request->all());
        session()->flash('flash_message', 'Cost centre type updated successfully...');
        return redirect('admin/costcentretype');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $costcentretype = Costcentretype::find($id);
        $costcentretype->delete();
        session()->flash('flash_message', 'Cost centre type deleted successfully...');
        return redirect('admin/costcentretype');
    }
}
