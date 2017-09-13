<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\vendor;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use App\country;
use Illuminate\Support\Facades\DB;
use App\bank;
use Excel;

class VendorController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $vendor_data = DB::table('vendor')
                ->select('vendor.*', 'country.country_name')
                ->leftJoin('country', 'vendor.country', '=', 'country.id')
                ->get();


        //get country
        $country_alldata = country::all();
        foreach ($country_alldata as $country) {
            $country_data[$country->id] = $country->country_name;
        }

        //get bank name
        $bank_name = array();
        $bank_data = bank::all();
        foreach ($bank_data as $bank) {
            $bank_name[$bank->id] = $bank->bank_name;
        }


        return view('admin.vendor_module.index', compact('vendor_data', 'country_data', 'bank_name'));
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
        $state_data = array();
        return view('admin.vendor_module.create', compact('country_data', 'state_data'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {

        $vendor_data = Input::all();
        $validationmessages = [
            'name.required' => 'Please enter vendor name',
            'vendor_id.required' => 'Please enter vendor id',
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

        $validator = Validator::make($vendor_data, [
                    'name' => 'required',
                    'vendor_id' => 'required',
                    'email' => 'required|email|unique:vendor',
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
            return redirect('admin/vendor/create')->withErrors($validator)->withInput(Input::all());
        }

        $data = vendor::create($vendor_data);


        session()->flash('flash_message', 'Vendor created successfully...');
        return redirect('admin/vendor');
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
        //

        $vendor = vendor::find($id);
        $country_alldata = country::all();
        foreach ($country_alldata as $country) {
            $country_data[$country->id] = $country->country_name;
        }

        //check for onetime vendor
        $onetime_vendor = '';
        if ($vendor['onetime_vendor'] == 1) {
            $onetime_vendor = true;
        } else {
            $onetime_vendor = false;
        }

        //check for office phone
        $office_phone = '';
        if ($vendor['office_phone'] == 'active') {

            $office_phone = 'active';
        } else {

            $office_phone = 'inactive';
        }

        //check for status
        $status = '';
        if ($vendor['status'] == 'active') {

            $status = 'active';
        } else {

            $status = 'inactive';
        }

        //check for approved vendor
        $approved_vendor = '';
        if ($vendor['approved_vendor'] == 1) {
            $approved_vendor = true;
        } else {
            $approved_vendor = false;
        }

        //check for e-invoice
        $e_invoice = '';
        if ($vendor['e_invoice'] == 1) {
            $e_invoice = true;
        } else {
            $e_invoice = false;
        }

        //get bank name
        $bank_name = array();
        $bank_data = bank::all();
        foreach ($bank_data as $bank) {
            $bank_name[$bank->id] = $bank->bank_name;
        }

        $countryCode = DB::table("country")
                        ->where("id", $vendor->country)
                        ->first()->id;

        $state = DB::table("state")->select('state_name')
                ->where("country_id", $countryCode)
                ->get();

        $state_data = array();
        foreach ($state as $key => $value) {
            $state_data[$value->state_name] = $value->state_name;
        }
        return view('admin.vendor_module.edit', compact('vendor', 'country_data', 'onetime_vendor', 'approved_vendor', 'e_invoice', 'bank_name', 'office_phone', 'status', 'state_data'));
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
        $vendorId = vendor::find($id);
        $vendorInputs = Input::all();
        $vendorInputs['updated_at'] = date('Y-m-d H:i:s');

        //set default value 0 for onetime_vendor
        if (isset($vendorInputs['onetime_vendor'])) {
            $vendorInputs['onetime_vendor'] = 1;
        } else {
            $vendorInputs['onetime_vendor'] = 0;
        }

        //set default value 0 for approved_vendor
        if (isset($vendorInputs['approved_vendor'])) {
            $vendorInputs['approved_vendor'] = 1;
        } else {
            $vendorInputs['approved_vendor'] = 0;
        }

        //set default value 0 for e-invoice
        if (isset($vendorInputs['e_invoice'])) {
            $vendorInputs['e_invoice'] = 1;
        } else {
            $vendorInputs['e_invoice'] = 0;
        }
        $validationmessages = [
            'name.required' => 'Please enter vendor name',
            'vendor_id.required' => 'Please enter vendor id',
            'email.required' => 'Please enter email',
            'website_address.required' => 'Please enter Website Address',
            'fax.required' => 'Please enter fax number',
            'street.required' => 'Please enter street',
            'office_phone.required' => 'Please enter office phone',
            'city.required' => 'Please enter city',
            'postal_code.required' => 'Please select postal code',
            'country.required' => 'Please select country',
        ];

        $validator = Validator::make($vendorInputs, [
                    'name' => 'required',
                    'vendor_id' => 'required',
                    'email' => 'required|email',
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
            return redirect('admin/vendor/' . $id . '/edit')->withErrors($validator)->withInput(Input::all());
        }

        $vendorId->update($vendorInputs);
        session()->flash('flash_message', 'Vendor updated successfully...');
        return redirect('admin/vendor');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $vendor_id = vendor::find($id);
        $vendor_id->delete($id);
        session()->flash('flash_message', 'Vendor deleted successfully...');
        return redirect('admin/vendor');
    }

    public function export_cs() {

        $vendor = DB::table('vendor')
                ->select('vendor.*', 'country.country_name', 'bank.bank_name')
                ->leftJoin('country', 'vendor.country', '=', 'country.id')
                ->leftJoin('bank', 'vendor.bank_name', '=', 'bank.id')
                ->get();

        $header = "VendorID" . ",";
        $header .= "Name" . ",";
        $header .= "Website Address" . ",";
        $header .= "Fax" . ",";
        $header .= "Street" . ",";
        $header .= "City" . ",";
        $header .= "Postal" . ",";
        $header .= "Country" . ",";
        $header .= "State" . ",";
        //$header .= "Email Address" . ",";
        $header .= "Email" . ",";
        $header .= "Tax Number" . ",";
        $header .= "OneTime Vendor" . ",";
        $header .= "Approved Vendor" . ",";
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
        foreach ($vendor as $vendor_data) {

            if ($vendor_data->onetime_vendor == '1' || $vendor_data->approved_vendor == '1' || $vendor_data->e_invoice == '1') {
                $onetimevendor = 'yes';
                $approvedvendor = 'yes';
                $einvoice = 'yes';
            } else {
                $onetimevendor = 'no';
                $approvedvendor = 'no';
                $einvoice = 'no';
            }

            $postal_code = '';
            if ($vendor_data->postal_code == '1') {
                $postal_code = 3000;
            } elseif ($vendor_data->postal_code == '2') {
                $postal_code = 4000;
            } elseif ($vendor_data->postal_code == '3') {
                $postal_code = 6000;
            } elseif ($vendor_data->postal_code == '4') {
                $postal_code = 7000;
            }

            $row1 = array();
            $row1[] = '"' . $vendor_data->vendor_id . '"';
            $row1[] = '"' . $vendor_data->name . '"';
            $row1[] = '"' . $vendor_data->website_address . '"';
            $row1[] = '"' . $vendor_data->fax . '"';
            $row1[] = '"' . $vendor_data->street . '"';
            $row1[] = '"' . $vendor_data->city . '"';
            $row1[] = '"' . $postal_code . '"';
            $row1[] = '"' . $vendor_data->country_name . '"';
            $row1[] = '"' . $vendor_data->state . '"';
            $row1[] = '"' . $vendor_data->email . '"';
            $row1[] = '"' . $vendor_data->tax_no . '"';
            $row1[] = '"' . $onetimevendor . '"';
            $row1[] = '"' . $approvedvendor . '"';
            $row1[] = '"' . $vendor_data->category . '"';
            $row1[] = '"' . $vendor_data->payment_terms . '"';
            $row1[] = '"' . $vendor_data->abn_no . '"';
            $row1[] = '"' . $vendor_data->acn_no . '"';
            $row1[] = '"' . $einvoice . '"';
            $row1[] = '"' . $vendor_data->bank_name . '"';
            $row1[] = '"' . $vendor_data->bsb . '"';
            $row1[] = '"' . $vendor_data->account_no . '"';
            $row1[] = '"' . $vendor_data->ifsc_code . '"';
            $row1[] = '"' . $vendor_data->contact_name . '"';
            $row1[] = '"' . $vendor_data->contact_role . '"';
            $row1[] = '"' . $vendor_data->contact_email . '"';
            $row1[] = '"' . $vendor_data->contact_phone . '"';
            $row1[] = '"' . $vendor_data->office_phone . '"';
            $row1[] = '"' . $vendor_data->status . '"';
            $data = join(",", $row1) . "\n";

            header("Content-type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=Vendor.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
            print "$data";
        }
    }

    public function importExport() {

        return view('admin.vendor_module.importExport');
    }

    /**
     * Import file into database Code
     *
     * @var array
     */
    public function importExcel(Request $request) {
        $vendorMaster = Input::all();
        $validationmessages = [
            'import_file.required' => 'Please select file.',
        ];
        //server side validation for file 
        $validator = Validator::make($vendorMaster, [
                    'import_file' => 'required',
                        ], $validationmessages);
        if ($validator->fails()) {
            $msgs = $validator->messages();

            return redirect('admin/vendor_importcsv')->withErrors($validator)->withInput($request->all());
        }
        //validation for csv file     
        if ($vendorMaster['import_file']->getClientOriginalExtension() != 'csv') {
            return redirect('admin/vendor_importcsv')->with(['msg' => 'Please select csv file.']);
        }

        if ($vendorMaster['importOption'] == 'newRecords') {
            if ($vendorMaster['import_file']) {
                $path = $vendorMaster['import_file']->getRealPath();

                $data = Excel::load($path, function($reader) {
                            
                        })->get();

                if (!empty($data) && $data->count()) {
                    foreach ($data->toArray() as $key => $value) {
                        //check the server side validation
                        $csvValidator = $this->csvValueValidation($value);
                        if ($csvValidator->fails()) {
                            $msgs = $csvValidator->messages();
                            return redirect('admin/vendor_importcsv')->withErrors($csvValidator)->withInput($request->all());
                        }
                        $insert = array();
                        $insert = ['vendor_id' => $value['vendorid'], 'name' => $value['name'], 'website_address' => $value['website_address'], 'fax' => $value['fax'], 'street' => $value['street'], 'city' => $value['city'], 'postal_code' => $value['postal'], 'country' => $value['country'], 'state' => $value['state'], 'email' => $value['email'], 'tax_no' => $value['tax_number'], 'onetime_vendor' => $value['onetime_vendor'], 'approved_vendor' => $value['approved_vendor'], 'payment_terms' => $value['payment_terms'], 'abn_no' => $value['abn_number'], 'acn_no' => $value['acn_number'], 'e_invoice' => $value['e_invoice'], 'bank_name' => $value['bank_name'], 'bsb' => $value['bsb_number'], 'account_no' => $value['account_number'], 'ifsc_code' => $value['ifsc_code'], 'contact_name' => $value['contact_name'], 'contact_role' => $value['contact_role'], 'contact_email' => $value['contact_email'], 'contact_phone' => $value['contact_phone'], 'office_phone' => $value['office_phone'], 'status' => $value['status'], 'category' => $value['category']];
                        if (!empty($insert)) {
                            vendor::insert($insert);
                        }
                    }
                    return back()->with('success', 'Insert Record successfully.');
                }
            }
        } elseif ($vendorMaster['importOption'] == 'updateRecord') {

            if ($vendorMaster['import_file']) {
                $path = $vendorMaster['import_file']->getRealPath();

                $data = Excel::load($path, function($reader) {
                            
                        })->get();

                if (!empty($data) && $data->count()) {
                    foreach ($data->toArray() as $key => $value) {
                        //check the server side validation
                        $isUpdate = true;
                        $csvValidator = $this->csvValueValidation($value, $isUpdate);
                        if ($csvValidator->fails()) {
                            $msgs = $csvValidator->messages();
                            return redirect('admin/vendor_importcsv')->withErrors($csvValidator)->withInput($request->all());
                        }
                        $insert = array();
                        $insert = ['vendor_id' => $value['vendorid'], 'name' => $value['name'], 'website_address' => $value['website_address'], 'fax' => $value['fax'], 'street' => $value['street'], 'city' => $value['city'], 'postal_code' => $value['postal'], 'country' => $value['country'], 'state' => $value['state'], 'email' => $value['email'], 'tax_no' => $value['tax_number'], 'onetime_vendor' => $value['onetime_vendor'], 'approved_vendor' => $value['approved_vendor'], 'payment_terms' => $value['payment_terms'], 'abn_no' => $value['abn_number'], 'acn_no' => $value['acn_number'], 'e_invoice' => $value['e_invoice'], 'bank_name' => $value['bank_name'], 'bsb' => $value['bsb_number'], 'account_no' => $value['account_number'], 'ifsc_code' => $value['ifsc_code'], 'contact_name' => $value['contact_name'], 'contact_role' => $value['contact_role'], 'contact_email' => $value['contact_email'], 'contact_phone' => $value['contact_phone'], 'office_phone' => $value['office_phone'], 'status' => $value['status'], 'category' => $value['category']];
                        $oldVender = vendor::where('email', '=', $value['email'])->first();
                        if ($oldVender) {
                            //update old recored if email id is already exist
                            $oldVender->update($insert);
                        } else {
                            //insert new recored
                            vendor::insert($insert);
                        }
                    }
                    return back()->with('success', 'New record inserted and old is updated successfully.');
                }
            }
        }
        return back()->with('error', 'Please Check your file, Something is wrong there.');
    }

    public function getStateList(Request $request) {
        try {
            $countryCode = DB::table("country")
                            ->where("id", $request->countryId)
                            ->first()->id;

            $state = DB::table("state")->select('state_name')
                    ->where("country_id", $countryCode)
                    ->get();

            $stateList = array();
            foreach ($state as $key => $value) {
                $stateArray = array(
                    'state_name' => $value->state_name,
                );
                array_push($stateList, $stateArray);
            }

            return response()->json(array('status' => true, 'results' => $stateList));
        } catch (\Exception $ex) {
            return response()->json(array('status' => false, 'message' => $ex->getMessage()));
        }
    }

    public function csvValueValidation($venderArray, $isUpdate = FALSE) {
        $csvValidationMessages = [
            'name.required' => 'Please enter vendor name',
            'vendorid.required' => 'Please enter vendor id',
            'vendorid.numeric' => 'Please enter vender id in numbers only',
            'email.required' => 'Please enter email',
            'email.email' => 'Please enter valid email',
            'email.unique' => 'This email already used, please enter another',
            'website_address.required' => 'Please enter Website Address',
            'fax.required' => 'Please enter fax number',
            'street.required' => 'Please enter street',
            'office_phone.required' => 'Please enter office phone',
            'city.required' => 'Please enter city',
            'postal.required' => 'Please enter postal code',
            'country.required' => 'Please enter country',
        ];
        //$validateRules = array();
        if ($isUpdate) {
            $validateRules = ['name' => 'required',
                'vendorid' => 'required|numeric',
                'email' => 'required|email',
                'website_address' => 'required',
                'fax' => 'required',
                'street' => 'required',
                'office_phone' => 'required',
                'city' => 'required',
                'postal' => 'required',
                'country' => 'required',
                    //'email' => 'unique:vendor'
            ];
        } else {
            $validateRules = ['name' => 'required',
                'vendorid' => 'required|numeric',
                'email' => 'required|email',
                'website_address' => 'required',
                'fax' => 'required',
                'street' => 'required',
                'office_phone' => 'required',
                'city' => 'required',
                'postal' => 'required',
                'country' => 'required',
                'email' => 'unique:vendor'
            ];
        }

        $csvValidator = Validator::make($venderArray, $validateRules, $csvValidationMessages);
        return $csvValidator;
    }

}
