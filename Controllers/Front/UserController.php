<?php
namespace App\Http\Controllers\Front;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\register;
use App\Role;
use App\phoneverify;
use App\Company;


class UserController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(Auth::check()){
    		return redirect()->intended('admin/dashboard');
    	}
		//return view('welcome');
        $role = Role::where('is_admin','N')->get();
        return view('front.register.index', compact('role'));
    }
    
    
    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @return \Illuminate\Http\Response
     */
    
    public function otpview($id)
    {
        return view('front.register.verify_otp');
    }
    
    public function loginotpview($id)
    {
        return view('front.register.login_otp');
    }

    
    public function store(Request $request)
    {
        
        $this->validate($request, [
            'name' => 'required',
            'lname' => 'required',
            'email' => 'required|unique:users,email,?????',
            'password' => 'required|min:5',
            'confirm_password' => 'required|min:5|same:password',
            'company_name' => 'required',
            'phone' => 'required|min:9',
            'agree' => 'required',
        ]);

        $company = Company::create([
            'name' => $request['company_name'],
            'website' => $request['web']
        ]);
        
        $request['password'] = bcrypt($request['password']);
        $request['role_id'] = 2;
        $request['company_id'] = $company->id;
                
        $AccountSid = env('TWILIO_ACCOUNT_SID', 'AC1c0f122e286d45d2f7117935360f68d7');
        $AuthToken = env('TWILIO_AUTH_TOKEN', '2bed13b3d4b8227b5c1762529356cea2');
        $url = env('TWILIO_URL', 'https://api.twilio.com/2010-04-01/Accounts/AC1c0f122e286d45d2f7117935360f68d7/Messages');
        
        $six_digit_random_number = mt_rand(100000, 999999);
        $from = '61476856876';
        $to = $request['phone'];
        $body = 'Your verification code is "'.$six_digit_random_number.'"';
        
        $toSend = "From=".$from."&To=".$to."&Body=".$body;
        
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$AccountSid:$AuthToken");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $toSend);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $result = curl_exec($ch);
        
        $request['verify_code'] = $six_digit_random_number;
        $user = register::create($request->all());	
        $insertedId = $user->id;
        return [
            'error' => false,
            'otpid' => $insertedId,
            'otpurl' => url('/api/v1/checkotp/' . $insertedId)
        ];
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
        return view('admin.portfolio.create', compact());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    
    public function checkotp(Request $request, $id)
    {
        $register = register::find($id);
        
        $request_code = $request->verify_code;
        $register_code = $register->verify_code;
        
        $this->validate($request, [
            'verify_code' => 'required',
        ]);
        
        if($request_code == $register_code)
        {
            $register->update([
                'status' => 'active',
            ]);
            Auth::loginUsingId($id);

            return [
                'error' => false,
                'redirect' => url('/admin/dashboard')
            ];
        }
        
        return [
            'error' => true,
            'message' => 'You have entered wrong OTP.'
        ];
        
    }
    
    
    
    public function logincheckotp(Request $request, $id)
    {
        $register = register::find($id);
        $request_code = $request->verify_code;
        $register_code = $register->verify_code;
        $email = $register->email;
        
        $this->validate($request, [
            'verify_code' => 'required',
        ]);
            
        if($request_code === $register_code) {
            Auth::loginUsingId($id);
            return [
                'error' => false,
                'redirect' => url('/admin/dashboard')
            ];
        }
        else
        {   
            return [
                'error' => true,
                'message' => 'You have entered wrong OTP.'
            ];
        }
    }
    
     public function checklogin(Request $request){
         
    	$this->validate($request, [
		    'email' => 'required|email',
		    'password' => 'required'
		]);
         
    	$email=$request->input('email');
    	$password=$request->input('password');
        
        if(!Auth::once(['email' => $email, 'password' => $password])) {
            return [
                'error' => true,
                'message' => 'Login Failed.'
            ];
        }
        
    	$request['password']=bcrypt($request->input('password'));

         $userDetail = register::where('email', $email)->get();
         
         $phone = $userDetail[0]['phone'];
         $id = $userDetail[0]['id'];
                
        $AccountSid = env('TWILIO_ACCOUNT_SID', 'AC1c0f122e286d45d2f7117935360f68d7');
        $AuthToken = env('TWILIO_AUTH_TOKEN', '2bed13b3d4b8227b5c1762529356cea2');
        $url = env('TWILIO_URL', 'https://api.twilio.com/2010-04-01/Accounts/AC1c0f122e286d45d2f7117935360f68d7/Messages');
        
        $six_digit_random_number = mt_rand(100000, 999999);
        $from = '61476856876';
        $to = $phone;
        $body = 'Your verification code is "'.$six_digit_random_number.'"';
        
        $toSend = "From=".$from."&To=".$to."&Body=".$body;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$AccountSid:$AuthToken");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $toSend);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $result = curl_exec($ch);
        
        $request['verify_code'] = $six_digit_random_number;
        $udetails = register::find($id);
         
        $uuSER = $udetails->update($request->all());
         
         if($uuSER == 1)
         {
            return [
                'error' => false,
                'otpid' => $id,
                'otpurl' => url('/api/v1/logincheckotp/' . $id)
            ];
         }
    }

    public function logout(){
    	Auth::logout();
    	return redirect()->intended('login');
    }
    
    
    public function update(Request $request, $id)
    {
        $portfolio = register::find($id);
        
        $this->validate($request, [
            'projects' => 'required',
        ]);
        
        $portfolio->update($request->all());
        
        
        session()->flash('flash_message', 'Portfolio updated successfully...');
        return redirect('admin/portfolio');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $portfolio = Portfolio::find($id);
        $portfolio->delete();
        session()->flash('flash_message', 'Portfolio deleted successfully...');
        return redirect('admin/portfolio');
    }
}
