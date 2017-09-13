<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Portfolio;
use App\Buckets;
use App\Bucketfinancialplanning;

class BucketfpController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //$bucketfp = Bucketfinancialplanning::all();
		
		$bucketfp = Bucketfinancialplanning::join('portfolio', 'portfolio.id', '=', 'bucket_financial_planning.portfolio_id')->get();
		
        return view('admin.bucketfp.index', compact('bucketfp'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
		$buckets = Buckets::pluck("name", "id")->prepend('Please select', '');
		$portfolio = Portfolio::pluck("name", "id")->prepend('Please select', '');
        return view('admin.bucketfp.create', compact('portfolio','buckets'));
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
            'portfolio_id' => 'required',
            'bucket_id' => 'required',
           
        ]);
        Bucketfinancialplanning::create($request->all());
        
        session()->flash('flash_message', 'Bucket Financial Planning created successfully...');
        return redirect('admin/bucketfp');
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
        $bucketfp = Bucketfinancialplanning::find($id);
		$buckets = Buckets::pluck("name", "id")->prepend('Please select', '');
		$portfolio = Portfolio::pluck("name", "id")->prepend('Please select', '');
        return view('admin.bucketfp.create', compact('bucketfp','buckets','portfolio'));
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
        $bucketfp = Bucketfinancialplanning::find($id);
        $this->validate($request, [
            'portfolio_id' => 'required',
            'bucket_id' => 'required',
        ]);
        $bucketfp->update($request->all());
        session()->flash('flash_message', 'Bucket Financial Planning updated successfully...');
        return redirect('admin/bucketfp');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $bucketfp = Bucketfinancialplanning::find($id);
        $bucketfp->delete();
        session()->flash('flash_message', 'Bucket Financial Planning deleted successfully...');
        return redirect('admin/bucketfp');
    }
}
