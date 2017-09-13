<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Input;
use App\projectchecklist;
use App\Project;
use App\Projectphase;
use App\Projecttask;
use App\User;
use App\ProjectIssue;
use App\IssueType;
use App\Capacityunits;
use App\Employee_records;
use App\Issues_Comment;
use App\IssueLikeUnlike;
use DB;

class ProjectissuesController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
//      1 Not yet assigned, 2 assigned, 3 In progress,4 complete, 5 closed

        if (isset($_GET['list']) and ! empty($_GET['list'])) {

            if ($_GET['list'] == 'inprogress') {
                $status = 3;
            } elseif ($_GET['list'] == 'closed') {
                $status = 5;
            } elseif ($_GET['list'] == 'complete') {
                $status = 4;
            } elseif ($_GET['list'] == 'all') {
                $status = 'all';
            } else {
                $status = 0;
            }
            $projectIssues = $this->getProjectIssueListByStatus($status);
        } else {
            $projectIssues = $this->getProjectIssueListByStatus(3);
        }

        $ProjectManager = Employee_records::where('status', 1)->select('employee_id as id', 'employee_first_name as name')->get();
        $project = project::select('id', 'project_Id', 'project_desc')->get();
        $countes = array(
            'inprogress' => count($this->getProjectIssueListByStatus(3)),
            'closed' => count($this->getProjectIssueListByStatus(5)),
            'complete' => count($this->getProjectIssueListByStatus(4)),
            'all' => count($this->getProjectIssueListByStatus('all')),
        );


        return view('admin.projectissues.index')->with([
                    'status' => $countes,
                    'projectIssues' => $projectIssues,
                    'authorList' => $ProjectManager,
                    'projectId' => $project
        ]);
    }

    public function issueSearch(Request $request)
    {






        /* if($request->list=='inprogress'){$status=3;}
          elseif($request->list=='closed'){$status=5;}
          elseif($request->list=='complete'){$status=4;}
          else{$status=0;} */
		  
        /*

          $cardArray=array(
          'CARD'=>array($request->filter),

          );
          $json = json_encode($cardArray);
          setcookie('cards', $json);
         */



        $projectIssues = $this->getProjectIssueListByStatus_Filter($request->all());


        $ProjectManager = Employee_records::where('status', 1)->select('employee_id as id', 'employee_first_name as name')->get();
        $project = project::select('id', 'project_Id', 'project_desc')->get();


        $countes = array(
            'inprogress' => count($this->getProjectIssueListByStatus_FilterCount(3, $request->all())),
            'closed' => count($this->getProjectIssueListByStatus_FilterCount(5, $request->all())),
            'complete' => count($this->getProjectIssueListByStatus_FilterCount(4, $request->all())),
            'all' => count($this->getProjectIssueListByStatus_Filter($request->all())),
        );


        return view('admin.projectissues.search')->with([
                    'status' => $countes,
                    'projectIssues' => $projectIssues,
                    'authorList' => $ProjectManager,
                    'projectId' => $project
        ]);
    }

    public function issueSearchget()
    {
        return redirect('admin/projectissues');
    }

    public function closeIssueRequest(Request $request, $id)
    {


        $this->validate($request, [
            '_token' => 'required',
            'status' => 'required|integer',
        ]);
        $request['changed_by'] = Auth::user()->id;

        //  ProjectIssue::update($request->all());
        ProjectIssue::find($id)->update($request->all());
        session()->flash('flash_message', '#' . $id . ' Project Issue updated successfully...');
        return redirect('admin/viewIssue/' . $id . '');
    }

    public function getProjectIssueListByStatus($status)
    {
        if ($status == 'all') {
            $projectIssues = ProjectIssue::select('id', 'created_by', 'status', 'updated_at', 'assignee', 'created_at', 'title')->orderby('id', 'desc')->get()->toArray();
        } else {

            $projectIssues = ProjectIssue::select('id', 'created_by', 'status', 'updated_at', 'assignee', 'created_at', 'title')->where('status', $status)->orderby('id', 'desc')->get()->toArray();
        }
        //  return view('admin.projectchecklist.index', compact('projectchecklist'));
        $loop = array();
        if (isset($projectIssues) and count($projectIssues) > 0) {

            foreach ($projectIssues as $projectIssuesListName) {

                $loop[] = array('id' => $projectIssuesListName['id'],
                    'title' => $projectIssuesListName['title'],
                    'created_by' => $this->getName($projectIssuesListName['created_by']),
                    'status' => $projectIssuesListName['status'],
                    'updated_at' => $projectIssuesListName['updated_at'],
                    'assignee' => $this->getName($projectIssuesListName['assignee']),
                    'created_at' => $projectIssuesListName['created_at'],
                    'comments' => count($this->listCommentIssues($projectIssuesListName['id'])),
                );
            }
        }

        return $loop;
    }

    public function getProjectIssueListByStatus_FilterCount($status, $search)
    {




        $projectIssues = ProjectIssue::select('id', 'created_by', 'status', 'updated_at', 'assignee', 'created_at', 'title')
                ->orWhere('description', 'like', '%' . $search['filter'] . '%')
                ->orWhere('title', 'like', '%' . $search['filter'] . '%')
                ->Where(['assignee' => $search['assignee'], 'projectId' => $search['projectId'], 'priority' => $search['priority']])
                ->orderby('id', 'desc')
                ->get();


        $loop = array();
        if (isset($projectIssues) and count($projectIssues) > 0) {

            foreach ($projectIssues as $projectIssuesListName) {
                if ($projectIssuesListName['status'] == $status) {
                    $loop[] = array('id' => $projectIssuesListName['id'],
                        'title' => $projectIssuesListName['title'],
                        'created_by' => $this->getName($projectIssuesListName['created_by']),
                        'status' => $projectIssuesListName['status'],
                        'updated_at' => $projectIssuesListName['updated_at'],
                        'assignee' => $this->getName($projectIssuesListName['assignee']),
                        'created_at' => $projectIssuesListName['created_at'],
                        'comments' => count($this->listCommentIssues($projectIssuesListName['id'])),
                    );
                }
            }
        }

        return $loop;
    }

    public function getProjectIssueListByStatus_Filter($search = '')
    {




        $projectIssues = ProjectIssue::select('id', 'created_by', 'status', 'updated_at', 'assignee', 'created_at', 'title')
                ->orWhere('description', 'like', '%' . $search['filter'] . '%')
                ->orWhere('title', 'like', '%' . $search['filter'] . '%')
                ->Where(['assignee' => $search['assignee'], 'projectId' => $search['projectId'], 'priority' => $search['priority']])
                ->orderby('id', 'desc')
                ->get()
                ->toArray();




        /*
          DB::enableQueryLog();
          // $query = DB::getQueryLog();
          echo '<pre>';
          print_r($projectIssues);
          die(); */


        $loop = array();
        if (isset($projectIssues) and count($projectIssues) > 0) {

            foreach ($projectIssues as $projectIssuesListName) {
                // if($projectIssuesListName['status']==$status){
                $loop[] = array('id' => $projectIssuesListName['id'],
                    'title' => $projectIssuesListName['title'],
                    'created_by' => $this->getName($projectIssuesListName['created_by']),
                    'status' => $projectIssuesListName['status'],
                    'updated_at' => $projectIssuesListName['updated_at'],
                    'assignee' => $this->getName($projectIssuesListName['assignee']),
                    'created_at' => $projectIssuesListName['created_at'],
                    'comments' => count($this->listCommentIssues($projectIssuesListName['id'])),
                );
            }


            // }
        }

        return $loop;
    }

    public function getName($id)
    {

        $name = User::select('name', 'lname', 'email')->where('id', $id)->take(1)->get();

        if (isset($name) and count($name) > 0) {

            if (isset($name[0]->lname) and ! empty($name[0]->lname)) {
                $username = $name[0]->name . ' ' . $name[0]->lname;
            } elseif (isset($name[0]->name) and ! empty($name[0]->name)) {
                $username = $name[0]->name;
            } else {
                $username = $name[0]->email;
            }
        } else {
            $username = 'N/A';
        }

        return $username;
    }

    public function getProjectname(Request $request)
    {

        if (isset($request->id) and $request->id > 0) {

            $project = project::select('id', 'project_Id', 'project_desc')->where('id', $request->id)->where('company_id',  Auth::user()->company_id)->get();
            $Projectphase = Projectphase::select('id', 'phase_Id', 'phase_name')->where('project_id', $request->id)->get();
            $Projecttask = Projecttask::select('id', 'task_Id', 'task_name')->where('project_id', isset($project[0]->project_Id)?$project[0]->project_Id:'')->where('company_id',  Auth::user()->company_id)->where('status','<>','Completed')->get();

            //  return view('admin.projectchecklist.index', compact('projectchecklist'));
            $list = array();
            if (isset($project) and $project->count()) {

                foreach ($project as $projectList) {
                    // echo '<option value="'.$projectList->id.'">';
                    $list[] = $projectList->project_desc;
                    // echo '</option>';
                }
            }
            $listphase = array();
            if (isset($Projectphase) and $Projectphase->count()) {

                foreach ($Projectphase as $ProjectphaseList) {
                    // echo '<option value="'.$projectList->id.'">';
                    //  $listphase[]= '<option value="'.$ProjectphaseList->id.'">'.$ProjectphaseList->phase_name.'</option>';
                    $listphase[] = array($ProjectphaseList->id, $ProjectphaseList->phase_Id . ' (' . \Illuminate\Support\Str::words($ProjectphaseList['phase_name'], 5, '....') . ')');
                    // echo '</option>';
                }
            }
            
             $listtask = array();
            if (isset($Projecttask) and $Projecttask->count()) {

                foreach ($Projecttask as $ProjecttaskList) {
                    $listtask[] = array($ProjecttaskList->id, $ProjecttaskList->task_Id . ' (' . \Illuminate\Support\Str::words($ProjecttaskList['task_name'], 5, '....') . ')');
                    // echo '</option>';
                }
            }
            echo json_encode(array('desc' => $list, 'phaseList' => $listphase,'taskList'=>$listtask));
        }
    }

    public function getProjectPhase(Request $request)
    {
        
        if (isset($request->id) and $request->id > 0) {


            $Projectphase = Projectphase::select('id', 'phase_name', 'phase_name')->where('id', $request->id)->get();
            
            $phase = Projectphase::find($request->id);
            $projectTask = projecttask::select('id', 'task_Id', 'task_name')->where('phase_Id', $phase->phase_Id)->get();
            //  return view('admin.projectchecklist.index', compact('projectchecklist'));
            $list = array();
            if (isset($Projectphase) and $Projectphase->count()) {

                foreach ($Projectphase as $ProjectphaseList) {
                    // echo '<option value="'.$projectList->id.'">';
                    $list[] = $ProjectphaseList->phase_name;
                    // echo '</option>';
                }
            }
            $listphase = array();
            if (isset($projectTask) and $projectTask->count()) {

                foreach ($projectTask as $projectTaskList) {
                    // echo '<option value="'.$projectList->id.'">';
                    //  $listphase[]= '<option value="'.$ProjectphaseList->id.'">'.$ProjectphaseList->phase_name.'</option>';
                    $listphase[] = array($projectTaskList->id, $projectTaskList->task_Id . ' (' . \Illuminate\Support\Str::words($projectTaskList['task_name'], 5, '....') . ')');
                    // echo '</option>';
                }
            }
            echo json_encode(array('desc' => $list, 'phaseList' => $listphase));
        }
    }

    public function getProjectTask(Request $request)
    {
      
        if (isset($request->id) and $request->id > 0) {

            $projectTask = projecttask::select('id', 'task_name')->where('id', $request->id)->get();
            $Projectphase = Projectphase::select('id', 'phase_name')->where('id', $request->id)->get();
            //  return view('admin.projectchecklist.index', compact('projectchecklist'));
            $list = array();
            if (isset($projectTask) and $projectTask->count()) {

                foreach ($projectTask as $projectTasks) {
                    // echo '<option value="'.$projectList->id.'">';
                    $list[] = $projectTasks->task_name;
                    // echo '</option>';
                }
            }
            $listphase = array();
            if (isset($projectTask) and $projectTask->count()) {

                foreach ($projectTask as $projectTaskList) {
                    // echo '<option value="'.$projectList->id.'">';
                    //  $listphase[]= '<option value="'.$ProjectphaseList->id.'">'.$ProjectphaseList->phase_name.'</option>';
                    $listphase[] = array($projectTaskList->id, $projectTaskList->task_Id);
                    // echo '</option>';
                }
            }
            echo json_encode(array('desc' => $list, 'phaseList' => $listphase));
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $project = project::select('id', 'project_Id', 'project_desc')->get();
        $phase = Projectphase::select('id', 'phase_Id', 'phase_name')->get();
        $ProjectManager = Employee_records::where('status', 1)->select('employee_id as id', 'employee_first_name as name')->get(); //used employee_personnel_number instead of employee_id temporary
        $users = User::where('role_id', 3)->select('id', 'name')->get();
        $task = projecttask::select('id', 'task_Id', 'task_name')->get();
        $IssueType = IssueType::select('id', 'name')->get();
        $capacity_units = Capacityunits::select('id', 'name')->get();





        return view('admin.projectissues.create')->with(['project' => $project, 'phase' => $phase, 'task' => $task, 'user' => $users, 'isueType' => $IssueType, 'capacity_units' => $capacity_units, 'ProjectManager' => $ProjectManager]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {   $filedb = array();
        if (count($request->file('fileToUpload')))
            foreach ($request->file('fileToUpload') as $key => $file) {
                $md5Name = md5_file($file->getRealPath());
                $guessExtension = $file->getClientOriginalExtension();
                $name = $md5Name . md5(microtime());
                $path = $file->storeAs('public/attachments', $name . '.' . $guessExtension);
                $filedb[$file->getClientOriginalName()] = 'storage/attachments/' . $name . '.' . $guessExtension;
            }

        $this->validate($request, [
            'title' => 'required',
            'issueTypeId' => 'required|integer',
            'description' => 'required',
            'projectId' => 'required|integer',
            'due_date' => 'required',
        ]);
        $project_issue = $request->all();
        if (count($filedb) > 0) {
            $project_issue['attachment'] = json_encode($filedb);
        }
        $get = ProjectIssue::create($project_issue);
        session()->flash('flash_message', 'Project Issue created successfully...');
        return redirect('admin/viewIssue/' . $get->id . '');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $project = project::select('id', 'project_Id', 'project_desc')->get();
        $phase = Projectphase::select('id', 'phase_Id', 'phase_name')->get();
        $ProjectManager = Employee_records::where('status', 1)->select('employee_id as id', 'employee_first_name as name')->get();
        $users = User::where('role_id', 3)->select('id', 'name')->get();
        $task = projecttask::select('id', 'task_Id', 'task_name')->get();
        $IssueType = IssueType::select('id', 'name')->get();
        $capacity_units = Capacityunits::select('id', 'name')->get();

        $SingleIssue = ProjectIssue::where('id', $id)->get();
        return view('admin.projectissues.edit')->with(['attachment' => json_decode($SingleIssue[0]->attachment, true), 'project' => $project, 'phase' => $phase, 'task' => $task, 'user' => $users, 'isueType' => $IssueType, 'capacity_units' => $capacity_units, 'ProjectManager' => $ProjectManager, 'SingleIssue' => $SingleIssue]);
    }

    public function view($id)
    {
        /* $project = project::select('id','project_Id')->get();
          $phase=Projectphase::select('id','phase_Id')->get();
          $ProjectManager=Employee_records::where('status',1)->select('employee_id as id','employee_first_name as name')->get();
          $users=User::where('role_id',3)->select('id','name')->get();
          $task=projecttask::select('id','task_Id')->get();
          $IssueType=IssueType::select('id','name')->get();
          $capacity_units=Capacityunits::select('id','name')->get(); */

        $projectIssues = ProjectIssue::select('id', 'created_by', 'status', 'updated_at', 'assignee', 'created_at', 'title', 'description', 'attachment')->where('id', $id)->orderby('id', 'desc')->get()->toArray();

        $Issues_Comment = Issues_Comment::select('*')->where('ProjectissueId', $id)->orderby('id', 'asc')->get()->toArray();
        $loop = array();
        if (isset($projectIssues) and count($projectIssues) > 0) {

            foreach ($projectIssues as $projectIssuesListName) {

                $like = IssueLikeUnlike::where(['issueId' => $projectIssuesListName['id'], 'action' => 1])->count();
                $unlike = IssueLikeUnlike::where(['issueId' => $projectIssuesListName['id'], 'action' => 2])->count();


                $loop[] = array('id' => $projectIssuesListName['id'],
                    'title' => $projectIssuesListName['title'],
                    'created_by' => $this->getName($projectIssuesListName['created_by']),
                    'status' => $projectIssuesListName['status'],
                    'updated_at' => $projectIssuesListName['updated_at'],
                    'assignee' => $this->getName($projectIssuesListName['assignee']),
                    'created_at' => $projectIssuesListName['created_at'],
                    'description' => $projectIssuesListName['description'],
                    'melike' => $this->Melike($projectIssuesListName['id']),
                    'attachment' => json_decode($projectIssuesListName['attachment'], true),
                    'likeClount' => $like,
                    'unlikeClount' => $unlike,
                        // 'comments'=>$this->listCommentIssues($projectIssuesListName['id']),
                );
            }
        }


        $loopComment = array();
        if (isset($Issues_Comment) and count($Issues_Comment) > 0) {

            foreach ($Issues_Comment as $Issues_CommentListName) {

                $loopComment[] = array('id' => $Issues_CommentListName['id'],
                    'description' => $Issues_CommentListName['description'],
                    'userId' => $this->getName($Issues_CommentListName['userId']),
                    'created_at' => $Issues_CommentListName['created_at'],
                    'attachment' => json_decode($Issues_CommentListName['attachment'], true)
                );
            }
        }



        return view('admin.projectissues.view')->with(['SingleIssue' => $loop, 'Comment' => $loopComment]);
    }

    public function Melike($id)
    {

        $userId = Auth::user()->id;
        $getList = IssueLikeUnlike::select('action')->where(['issueId' => $id, 'userId' => $userId])->get()->toArray();

        if (isset($getList) and ! empty($getList)) {
            $data = $getList[0]['action'];
        } else {
            $data = '';
        }


        return $data;
    }

    public function listCommentIssues($id)
    {

        $list = Issues_Comment::where('ProjectissueId', $id)->get();
        return $list;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $projectchecklist = Projectchecklist::find($id);
        return view('admin.projectchecklist.create', compact('projectchecklist'));
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
        
        if (count($request->file('fileToUpload')))
            foreach ($request->file('fileToUpload') as $key => $file) {
                $md5Name = md5_file($file->getRealPath());
                $guessExtension = $file->getClientOriginalExtension();
                $name = $md5Name . md5(microtime());
                $path = $file->storeAs('public/attachments', $name . '.' . $guessExtension);
                $filedb[$file->getClientOriginalName()] = 'storage/attachments/' . $name . '.' . $guessExtension;
            }
        if (count($request->file('fileToUpload2')))
            foreach ($request->file('fileToUpload2') as $key => $file) {
                $md5Name = md5_file($file->getRealPath());
                $guessExtension = $file->getClientOriginalExtension();
                $name = $md5Name . md5(microtime());
                $path = $file->storeAs('public/attachments', $name . '.' . $guessExtension);
                $filedb[$file->getClientOriginalName()] = 'storage/attachments/' . $name . '.' . $guessExtension;
            }


        $this->validate($request, [
            'title' => 'required',
            'issueTypeId' => 'required|integer',
            'description' => 'required',
            'projectId' => 'required|integer',
            'due_date' => 'required',
        ]);

        $request['changed_by'] = Auth::user()->id;

        //  ProjectIssue::update($request->all());
        $project_issue = $request->except('fileToUpload');
        if (isset($filedb)) {
            $project_issue['attachment'] = json_encode($filedb);
        } else {
            $project_issue['attachment'] = null;
        }
        ProjectIssue::find($id)->update($project_issue);
        session()->flash('flash_message', '#' . $id . ' Project Issue updated successfully...');
        return redirect('admin/viewIssue/' . $id . '');
    }

    public function issueComment(Request $request)
    {

        if (count($request->file('fileToUpload')))
            foreach ($request->file('fileToUpload') as $key => $file) {
                $md5Name = md5_file($file->getRealPath());
                $guessExtension = $file->getClientOriginalExtension();
                $name = $md5Name . md5(microtime());
                $path = $file->storeAs('public/attachments', $name . '.' . $guessExtension);
                $filedb[$file->getClientOriginalName()] = 'storage/attachments/' . $name . '.' . $guessExtension;
            }

        $this->validate($request, [
            'description' => 'required',
            'ProjectissueId' => 'required|integer',
        ]);


        //  ProjectIssue::update($request->all());
        $issues_comment = $request->all();
        if (count($filedb) > 0) {
            $issues_comment['attachment'] = json_encode($filedb);
        }

        $rr = Issues_Comment::create($issues_comment);



        session()->flash('flash_message', '#' . $request->ProjectissueId . ' Project Comment Added successfully...');
        return redirect('admin/viewIssue/' . $request->ProjectissueId . '');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $projectchecklist = Projectchecklist::find($id);
        $projectchecklist->delete();
        session()->flash('flash_message', 'Project checklist deleted successfully...');
        return redirect('admin/projectchecklist');
    }

    public function addLikeIssus(Request $request)
    {

        if (isset($request->islikevalue)) {
            $userId = Auth::user()->id;
            $getList = IssueLikeUnlike::where(['issueId' => $request->id, 'userId' => $userId])->count();


            $records = array(
                'issueId' => $request->id,
                'action' => $request->islikevalue,
                'userId' => $userId
            );




            if ($getList == 0) {


                $data = IssueLikeUnlike::create($records);
            } else {
                $data = IssueLikeUnlike::where(['issueId' => $request->id, 'userId' => $userId])->update($records);
            }

            $like = IssueLikeUnlike::where(['issueId' => $request->id, 'action' => 1])->count();
            $unlike = IssueLikeUnlike::where(['issueId' => $request->id, 'action' => 2])->count();


            echo json_encode(array('Like' => $like, 'unlike' => $unlike));
            // 
            // return $result;
        }
    }

}
