<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\projecttype;

class ProjecttypesController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $projecttypes = Projecttype::all();
        return view('admin.projecttype.projecttype', compact('projecttypes'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.projecttype.addprojecttype');
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
            'name' => 'required|unique:project_type',
        ]);
        
        $create = $request->all();
        $create['company_id'] = Auth::user()->company_id;
        Projecttype::create($create);
        session()->flash('flash_message', 'Project type created successfully...');
        return redirect('admin/projecttype');
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
        $projecttype = Projecttype::find($id);
        return view('admin.projecttype.addprojecttype', compact('projecttype'));
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
        $projecttype = Projecttype::find($id);
        $this->validate($request, [
            'name' => 'required',
            /* 'description' => 'required' */
        ]);
        $projecttype->update($request->all());
        session()->flash('flash_message', 'Project type updated successfully...');
        return redirect('admin/projecttype');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $projecttype = Projecttype::find($id);
        $projecttype->delete();
        session()->flash('flash_message', 'Project type deleted successfully...');
        return redirect('admin/projecttype');
    }
}
