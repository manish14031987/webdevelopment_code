<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Projectphase;
use App\phasetype;
use App\Project;
use App\Personresponsible;
use App\Roleauth;


class ProjectphaseController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        Roleauth::check('project.phase.index');
        
        $projectphase = Projectphase::with('phaseType')->with('projectId')->with('personResponsible')->get();
        //echo "<pre>";print_r($projectphase);die;
        
        return view('admin.projectphase.index', compact('projectphase'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        Roleauth::check('project.phase.create');

        $phasetype = Phasetype::pluck("name", "id")->prepend('Please select', '');
        $project_id = Project::pluck("project_Id", "id")->prepend('Please select', '');
        
        return view('admin.projectphase.create',compact('phasetype','project_id'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Roleauth::check('project.phase.create');
        
       $this->validate($request, [
            'phase_Id' => 'required',
            'phase_name' => 'required',
            
        ]); 
        
        Projectphase::create($request->all());
        
        session()->flash('flash_message', 'Project phase created successfully...');
        return redirect('admin/projectphase');
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
    
    public function getprojectname($id)
    {
        $project_name = project::find($id);
        $p= $project_name->project_name;
        return response()->json($p);
    }
    
    public function edit($id)
    {
        Roleauth::check('project.phase.edit');
        $projectphase = Projectphase::find($id);
        if(!isset($projectphase)) {
            return redirect('admin/projectphase');
        }

        $phasetype = Phasetype::pluck("name", "id")->prepend('Please select', '');
        $personResponsible = Personresponsible::pluck("name", "id")->prepend('Please select', '');
        $project_id = Project::pluck("project_Id", "id")->prepend('Please select', '');
        
        return view('admin.projectphase.create', compact('projectphase','phasetype','project_id','personResponsible'));
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
        Roleauth::check('project.phase.edit');
        $projectphase = Projectphase::find($id);
        if(!isset($projectphase)) {
            return redirect('admin/projectphase');
        }
        
        /*$request['updated_predecessor_name'] = $projectphase->phase_name;
        $request['predecessor_name'] = $request['phase_name'];*/
        
        //echo "<pre>"; print_r($request);exit;
        
        $this->validate($request, [
            'phase_Id' => 'required',
            'phase_name' => 'required',
        ]); 
                
        $projectphase->update($request->all());
        session()->flash('flash_message', 'Project phase updated successfully...');
        return redirect('admin/projectphase');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Roleauth::check('project.phase.edit');
        $projectphase = Projectphase::find($id);
        if(!isset($projectphase)) {
            return redirect('admin/projectphase');
        }
        
        $projectphase->delete();
        session()->flash('flash_message', 'Project phase deleted successfully...');
        return redirect('admin/projectphase');
    }
}
