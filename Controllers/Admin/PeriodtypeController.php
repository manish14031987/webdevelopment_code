<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Periodtype;


class PeriodtypeController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $periodtype = Periodtype::all();
        return view('admin.periodtype.index', compact('periodtype'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        
        return view('admin.periodtype.create');
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
        Periodtype::create($request->all());
        
        session()->flash('flash_message', 'Period type created successfully...');
        return redirect('admin/periodtype');
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
        $periodtype = Periodtype::find($id);
        return view('admin.periodtype.create', compact('periodtype'));
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
        $periodtype = Periodtype::find($id);
        $this->validate($request, [
            'name' => 'required',
            'description' => 'required',
            'status' => 'required',
        ]); 
        $periodtype->update($request->all());
        session()->flash('flash_message', 'Period type updated successfully...');
        return redirect('admin/periodtype');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $periodtype = Periodtype::find($id);
        $periodtype->delete();
        session()->flash('flash_message', 'Period type deleted successfully...');
        return redirect('admin/periodtype');
    }
}
