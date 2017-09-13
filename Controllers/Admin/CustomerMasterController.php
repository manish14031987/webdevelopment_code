<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\customer_master;
use App\country;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\bank;
use Excel;

class CustomerMasterController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        //
        $customer_master = DB::table('customer_master')
                ->select('customer_master.*', 'country.country_name')
                ->leftJoin('country', 'customer_master.country', '=', 'country.id')
                ->get();

        //get country
        $country_alldata = country::all();
        foreach ($country_alldata as $country) {
            $country_data[$country->id] = $country->country_name;
        }



        return view('admin.customermaster.index', compact('customer_master', 'country_data'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        //get country
        $country_alldata = country::all();
        foreach ($country_alldata as $country) {
            $country_data[$country->id] = $country->country_name;
        }

        return view('admin.customermaster.create', compact('country_data'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $customer_data = Input::all();
        $validationmessages = [
            'name.required' => 'Please enter customer name',
            'customer_id.required' => 'Please enter customer id',
            'email.required' => 'Please enter email',
            'email.unique' => 'This email already used, please enter another',
            'website_address.required' => 'Please enter Website Address',
            'fax.required' => 'Please enter fax number',
            'street.required' => 'Please enter street',
            'office_phone.required' => 'Please enter office phone',
            'city.required' => 'Please enter city',
            'postal_code.required' => 'Please select postal code',
            'country.required' => 'Please select country',
        ];

        $validator = Validator::make($customer_data, [
                    'name' => 'required',
                    'customer_id' => 'required',
                    'email' => 'required|email|unique:customer_master',
                    'website_address' => 'required',
                    'fax' => 'required',
                    'street' => 'required',
                    'office_phone' => 'required',
                    'city' => 'required',
                    'postal_code' => 'required',
                    'country' => 'required',
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/customer_master/create')->withErrors($validator)->withInput(Input::all());
        }

        $data = customer_master::create($customer_data);


        session()->flash('flash_message', 'Customer created successfully...');
        return redirect('admin/customer_master');
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
        $customer = customer_master::find($id);

        $country_alldata = country::all();
        $country_data = array();
        foreach ($country_alldata as $country) {
            $country_data[$country->id] = $country->country_name;
        }

        //check for onetime customer
        $onetime_customer = '';
        if ($customer['onetime_customer'] == 'yes') {
            $onetime_customer = true;
        } else {
            $onetime_customer = false;
        }

        //check for status
        $status = '';
        if ($customer['status'] == 'active') {

            $status = 'active';
        } else {

            $status = 'inactive';
        }

        //check for approved customer
        $approved_customer = '';
        if ($customer['approved_customer'] == 'yes') {
            $approved_customer = true;
        } else {
            $approved_customer = false;
        }

        //check for e-invoice
        $e_invoice = '';
        if ($customer['e_invoice'] == 'yes') {
            $e_invoice = true;
        } else {
            $e_invoice = false;
        }

        //get bank name
        $bank_data = bank::all();
        $bank_name = array();
        foreach ($bank_data as $bank) {
            $bank_name[$bank->bank_name] = $bank->bank_name;
        }

        $countryCode = DB::table("country")
                        ->where("id", $customer->country)
                        ->first()->id;

        $state = DB::table("state")->select('state_name')
                ->where("country_id", $countryCode)
                ->get();

        $state_data = array();
        foreach ($state as $key => $value) {
            $state_data[$value->state_name] = $value->state_name;
        }
        return view('admin.customermaster.edit', compact('customer', 'country_data', 'onetime_customer', 'approved_customer', 'e_invoice', 'bank_name', 'office_phone', 'status', 'state_data'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        $customerId = customer_master::find($id);
        $customerInputs = Input::all();

        $validationmessages = [
            'name.required' => 'Please enter customer name',
            'customer_id.required' => 'Please enter customer id',
            'email.required' => 'Please enter email',
            'website_address.required' => 'Please enter Website Address',
            'fax.required' => 'Please enter fax number',
            'street.required' => 'Please enter street',
            'office_phone.required' => 'Please enter office phone',
            'city.required' => 'Please enter city',
            'postal_code.required' => 'Please select postal code',
            'country.required' => 'Please select country',
            'contact_email.required' => 'Please enter contact email'
        ];

        $validator = Validator::make($customerInputs, [
                    'name' => 'required',
                    'customer_id' => 'required',
                    'email' => 'required',
                    'website_address' => 'required',
                    'fax' => 'required',
                    'street' => 'required',
                    'office_phone' => 'required',
                    'city' => 'required',
                    'postal_code' => 'required',
                    'country' => 'required',
                    'contact_email' => 'required'
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/customer_master/' . $id . '/edit')->withErrors($validator)->withInput(Input::all());
        }

        $customerId->update($customerInputs);
        session()->flash('flash_message', 'Customer updated successfully...');
        return redirect('admin/customer_master');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $customer_id = customer_master::find($id);
        $customer_id->delete($id);
        session()->flash('flash_message', 'Customer deleted successfully...');
        return redirect('admin/customer_master');
    }

    public function export_cs() {

        $customer = DB::table('customer_master')
                ->select('customer_master.*', 'country.country_name')
                ->leftJoin('country', 'customer_master.country', '=', 'country.id')
                ->get();

        $header = "CustomerID" . ",";
        $header .= "Name" . ",";
        $header .= "Website Address" . ",";
        $header .= "Fax" . ",";
        $header .= "Street" . ",";
        $header .= "City" . ",";
        $header .= "Postal" . ",";
        $header .= "Country" . ",";
        $header .= "State" . ",";
        $header .= "Email Address" . ",";
        $header .= "Tax Number" . ",";
        $header .= "OneTime Customer" . ",";
        $header .= "Approved Customer" . ",";
        $header .= "Category" . ",";
        $header .= "Payment Terms" . ",";
        $header .= "ABN Number" . ",";
        $header .= "ACN Number" . ",";
        $header .= "E-invoice" . ",";
        $header .= "Bank Name" . ",";
        $header .= "BSB Number" . ",";
        $header .= "Account Number" . ",";
        $header .= "IFSC Code" . ",";
        $header .= "Contact Name" . ",";
        $header .= "Contact Role" . ",";
        $header .= "Contact Email" . ",";
        $header .= "Contact Phone" . ",";
        $header .= "Office Phone" . ",";
        $header .= "Status" . ",";


        print "$header\n";
        foreach ($customer as $customer_data) {

            if ($customer_data->onetime_customer == 'yes') {
                $onetimecustomer = 'yes';
            } else {
                $onetimecustomer = 'no';
            }
            if ($customer_data->approved_customer == 'yes') {

                $approvedcustomer = 'yes';
            } else {
                $approvedcustomer = 'no';
            }
            if ($customer_data->e_invoice == 'yes') {
                $einvoice = 'yes';
            } else {
                $einvoice = 'no';
            }

            $row1 = array();
            $row1[] = '"' . $customer_data->customer_id . '"';
            $row1[] = '"' . $customer_data->name . '"';
            $row1[] = '"' . $customer_data->website_address . '"';
            $row1[] = '"' . $customer_data->fax . '"';
            $row1[] = '"' . $customer_data->street . '"';
            $row1[] = '"' . $customer_data->city . '"';
            $row1[] = '"' . $customer_data->postal_code . '"';
            $row1[] = '"' . $customer_data->country_name . '"';
            $row1[] = '"' . $customer_data->state . '"';
            $row1[] = '"' . $customer_data->email . '"';
            $row1[] = '"' . $customer_data->tax_no . '"';
            $row1[] = '"' . $onetimecustomer . '"';
            $row1[] = '"' . $approvedcustomer . '"';
            $row1[] = '"' . $customer_data->category . '"';
            $row1[] = '"' . $customer_data->payment_terms . '"';
            $row1[] = '"' . $customer_data->abn_no . '"';
            $row1[] = '"' . $customer_data->acn_no . '"';
            $row1[] = '"' . $einvoice . '"';
            $row1[] = '"' . $customer_data->bank_name . '"';
            $row1[] = '"' . $customer_data->bsb . '"';
            $row1[] = '"' . $customer_data->account_no . '"';
            $row1[] = '"' . $customer_data->ifsc_code . '"';
            $row1[] = '"' . $customer_data->contact_name . '"';
            $row1[] = '"' . $customer_data->contact_role . '"';
            $row1[] = '"' . $customer_data->contact_email . '"';
            $row1[] = '"' . $customer_data->contact_phone . '"';
            $row1[] = '"' . $customer_data->office_phone . '"';
            $row1[] = '"' . $customer_data->status . '"';


            $data = join(",", $row1) . "\n";

            header("Content-type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=Customer.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
            print "$data";
        }
    }

    public function importExport() {

        return view('admin.customermaster.importExport');
    }

    public function importExcel(Request $request) {

        if ($request['new_records']) {

            if ($request->hasFile('import_file')) {
                $path = $request->file('import_file')->getRealPath();

                $data = Excel::load($path, function($reader) {
                            
                        })->get();


                if (!empty($data) && $data->count()) {

                    foreach ($data->toArray() as $key => $value) {

                        if (!empty($value)) {

//                            print_r($value);
//                            exit('=');
                            $insert[$key] = ['customer_id' => $value['customerid'], 'name' => $value['name'], 'website_address' => $value['website_address'], 'fax' => $value['fax'], 'street' => $value['city'], 'street' => $value['city'], 'postal_code' => isset($value['postal_code'])?$value['postal_code']:$value['postal'], 'state' => $value['state'], 'email' => $value['email_address'], 'tax_no' => $value['tax_number'], 'onetime_customer' => $value['onetime_customer'], 'approved_customer' => $value['approved_customer'], 'payment_terms' => $value['payment_terms'], 'abn_no' => $value['abn_number'], 'acn_no' => $value['acn_number'], 'e_invoice' => $value['e_invoice'], 'bank_name' => $value['bank_name'], 'bsb' => $value['bsb_number'], 'account_no' => $value['account_number'], 'ifsc_code' => $value['ifsc_code'], 'contact_name' => $value['contact_name'], 'contact_role' => $value['contact_role'], 'contact_email' => $value['contact_email'], 'contact_phone' => $value['contact_phone'], 'office_phone' => $value['office_phone'], 'status' => $value['status']];
                        }
                    }
//                    print_r($insert);
//                    exit('=');

                    if (!empty($insert)) {

                        customer_master::insert($insert);
                        return back()->with('success', 'Insert Record successfully.');
                    }
                }
            }
        } elseif ($request['update_records']) {

            if ($request->hasFile('import_file')) {
                $path = $request->file('import_file')->getRealPath();

                $data = Excel::load($path, function($reader) {
                            
                        })->get();

                if (!empty($data) && $data->count()) {

                    foreach ($data->toArray() as $key => $value) {

                        if (!empty($value)) {

//                            $insert[] = $value['customerid'];

//                            $customerid = customer_master::find($request);
                            
                             $insert[$key] = ['customer_id' => $value['customerid'], 'name' => $value['name'], 'website_address' => $value['website_address'], 'fax' => $value['fax'], 'street' => $value['city'], 'street' => $value['city'], 'postal_code' => isset($value['postal_code'])?$value['postal_code']:$value['postal'], 'state' => $value['state'], 'email' => $value['email_address'], 'tax_no' => $value['tax_number'], 'onetime_customer' => $value['onetime_customer'], 'approved_customer' => $value['approved_customer'], 'payment_terms' => $value['payment_terms'], 'abn_no' => $value['abn_number'], 'acn_no' => $value['acn_number'], 'e_invoice' => $value['e_invoice'], 'bank_name' => $value['bank_name'], 'bsb' => $value['bsb_number'], 'account_no' => $value['account_number'], 'ifsc_code' => $value['ifsc_code'], 'contact_name' => $value['contact_name'], 'contact_role' => $value['contact_role'], 'contact_email' => $value['contact_email'], 'contact_phone' => $value['contact_phone'], 'office_phone' => $value['office_phone'], 'status' => $value['status']];
                        }
                    }


                    if (!empty($insert)) {
                        
                        
                        
                        customer_master::insert($insert);
                        return back()->with('success', 'Insert Record successfully.');
                    }
                }
            }
        }

        return back()->with('error', 'Please Check your file, Something is wrong there.');
    }

}
