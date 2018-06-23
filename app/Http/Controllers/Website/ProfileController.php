<?php

namespace App\Http\Controllers\Website;

use App\Models\Company;
use App\Models\User;
use App\Models\Offer;
use App\Models\Apply;
use App\Models\OffersHistory;
use App\Models\AppliesHistory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

class ProfileController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $id = Auth::user()->id;
        $company = DB::table('users')->where('users.id', $id)
            ->leftJoin('companies', 'users.id', '=', 'companies.user_id')
            ->select('users.id', 'users.email', 'users.role', 'users.created_at', 'companies.user_id', 'companies.name', 'companies.siret', 'companies.address', 'companies.phone', 'companies.description', 'companies.logo_filename', 'companies.logo_size')
            ->get()
            ->first();

        return view('website/profile/index', ['company' => $company]);
    }


    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * Edit the company profile
     */
    public function edit(Request $request)
    {
        $id = Auth::user()->id;
        
        // Inputs errors
        $validator = Validator::make($request->all(), [
            'edit_email' => 'required|email|min:6|max:255|unique:users,email,'.$id,
            'edit_name' => 'required',
            'edit_logo' => 'image|mimes:jpg,jpeg,png,gif,svg,bmp|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect('profile/edit')
            ->withErrors($validator)
            ->withInput()
            ->with('danger', "Vos changements n'ont pas été pris en compte. Veuillez vérifier vos champs.");
        }

        if (isset(request()->edit_logo)) {
            $logoName = "logo_" . time() . '.' . request()->edit_logo->getClientOriginalExtension();
            $logoSize = $request->edit_logo->getClientSize();

            $request->edit_logo->storeAs('public/logos', $logoName);
        }

        $user_data = Input::only('edit_email');
        $company_data = Input::only('edit_name', 'edit_siret', 'edit_address', 'edit_phone', 'edit_description');

        $user = User::where('id', $id)->first();
        $user->setAttribute('email', $user_data['edit_email']);
        $user->save();

        $company = Company::where(['user_id' => $id])->first();
        $company->setAttribute('name', $company_data['edit_name']);
        $company->setAttribute('siret', $company_data['edit_siret']);
        $company->setAttribute('address', $company_data['edit_address']);
        $company->setAttribute('phone', $company_data['edit_phone']);

        if (isset(request()->edit_logo)) {
            if (isset($company->logo_filename)) {
                File::delete(storage_path('app/public/logos') . '/' . $company->logo_filename);
            }
            $company->setAttribute('logo_filename', $logoName);
            $company->setAttribute('logo_size', $logoSize);
        }

        $company->setAttribute('description', $company_data['edit_description']);
        $company->save();

        return redirect()->route('indexProfile')->with('success', 'Votre profil a été correctement modifié.');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     * Display the profile edit page
     */
    public function editPage()
    {
        $id = Auth::user()->id;
        $company = DB::table('users')->where('users.id', $id)
            ->leftJoin('companies', 'users.id', '=', 'companies.user_id')
            ->select('users.id', 'users.email', 'users.role', 'users.created_at', 'companies.user_id', 'companies.name', 'companies.siret', 'companies.address', 'companies.phone', 'companies.description', 'companies.logo_filename', 'companies.logo_size')
            ->get()
            ->first();

        return view('website/profile/actions/edit', ['company' => $company]);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * Change the current user password
     */
    public function changePassword(Request $request)
    {

        // Inputs errors
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string|min:6|max:255',
            'new_password' => 'required|string|min:6',
            'new_password_confirm' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors()->getMessages()], 422);
        }

        $pass = Auth::user()->password;
        $current_password = Input::only('current_password');
        $new_password = Input::only('new_password', 'new_password_confirm');

        if(Hash::check($current_password["current_password"], $pass)) {

            if(!strcmp($current_password["current_password"], $new_password["new_password"]) == 0){

                if(strcmp($new_password["new_password"], $new_password["new_password_confirm"]) == 0){
                    //Change Password
                    $user = Auth::user();
                    $user->password = bcrypt($new_password["new_password"]);
                    $user->save();
                } else {
                    // New passwords don't match
//                    return response()->json(['error' => Lang::get('errors.' . 468)], 468);
                    return response()->json(['error' => Lang::get('errors.' . 468)], 468);
                }
            }
            else {
                // New password like the current
                return response()->json(['error' => Lang::get('errors.' . 469)], 469);
            }

        }
        else {
            // User password invalid
            return response()->json(['error' => Lang::get('errors.' . 470)], 470);
        }

        return redirect()->route('indexProfile');

    }
    public function settings()
    {
        return view('website/profile/settings/index');
    }
    public function deleteData(Request $request)
    {
        
        // Inputs errors
        $validator = Validator::make($request->all(), [
            'delete_password' => 'required|string|min:6|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors()->getMessages()], 422);
        }
        $id = Auth::user()->id;    
        $pass = Auth::user()->password;
        $delete_password = Input::only('delete_password');


        if(Hash::check($delete_password["delete_password"], $pass)) {
            // Password correct, delete all data form all tables in DB
            $company = Company::where('user_id', $id)->get()->first();
            $id_company = $company->id;
            $offers = Offer::where('company_id', '=', $id)->get();
            if ($offers) {
                foreach ($offers as $value) {
                    $offer_history = OffersHistory::where('offer_id', '=', $value->id)->get();
                    if ($offer_history) {
                        foreach ($offer_history as $line) {
                            // Delete offer history
                            $line->delete();
                        }
                    }
                    $applies = Apply::where('offer_id', '=', $value->id)->get();
                    if ($applies) {
                        foreach ($applies as $apply) {
                            if (isset($apply->cv_filename)) {
                                // Delete CV file
                                File::delete(storage_path('app/public/cv') . '/' . $apply->cv_filename);
                            }
                            $apply_history = AppliesHistory::where('apply_id', '=', $apply->id)->get();
                            if ($apply_history) {
                                foreach ($apply_history as $val) {
                                    // Delete apply history
                                    $val->delete();
                                }
                            }
                            // Delete apply
                            $apply->delete();
                        }
                    }
                    // Delete offer
                    $value->delete();
                }
            }
            // Delete form company table
            Company::where('user_id', $id)->delete();
            // Delete form verify_users table            
            DB::table('verify_users')->where('user_id', $id)->delete();
            // Delete form users table
            User::where('id', $id)->delete();

        }
        else {
            // User password invalid
            return response()->json(['error' => Lang::get('errors.' . 470)], 470);
        }

        return redirect()->to('/');
    }
    public function downloadData(Request $request)
    {
        // Inputs errors
        $validator = Validator::make($request->all(), [
            'download_password' => 'required|string|min:6|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors()->getMessages()], 422);
        }
        $id = Auth::user()->id;    
        $pass = Auth::user()->password;
        $download_password = Input::only('download_password');


        if(Hash::check($download_password["download_password"], $pass)) {
            // Password correct, delete all data form all tables in DB
            $data = array();
            $user = Auth::user()->toArray();
            array_push($data, $user);            
            $company = Company::where('user_id', $id)->get()->first()->toArray();
            array_push($data, $company);            
            $id_company = $company["id"];
            $offers = Offer::where('company_id', '=', $id)->get()->toArray();
            if($offers){
                foreach($offers as $offer){
                    array_push($data, $offer);
                    $offer_history = OffersHistory::where('offer_id', '=', $offer["id"])->get()->toArray();
                    if ($offer_history) {
                        foreach ($offer_history as $line) {
                            array_push($data, $line);
                        }
                    }
                    $applies = Apply::where('offer_id', '=', $offer["id"])->get()->toArray();
                    if ($applies) {
                        foreach ($applies as $apply) {
                            $apply_history = AppliesHistory::where('apply_id', '=', $apply["id"])->get()->toArray();
                            if ($apply_history) {
                                foreach ($apply_history as $val) {
                                    array_push($data, $val);
                                }
                            }
                            array_push($data, $apply);
                        }
                    }
                }
            }
            $this->outputCSV($data, 'download.csv');

            // return redirect()->route('profileSettings')->with('success', 'Vos données ont été exportées'); 

        }
        else {
            // User password invalid
            return response()->json(['error' => Lang::get('errors.' . 470)], 470);
        }

    }
    public function outputCSV($data,$file_name = 'data_stujobs.csv') {
        # output headers so that the file is downloaded rather than displayed
         header("Content-Type: text/csv");
         header("Content-Disposition: attachment; filename=$file_name");
         # Disable caching - HTTP 1.1
         header("Cache-Control: no-cache, no-store, must-revalidate");
         # Disable caching - HTTP 1.0
         header("Pragma: no-cache");
         # Disable caching - Proxies
         header("Expires: 0");
     
         # Start the ouput
         $output = fopen("php://output", "w");
         
          # Then loop through the rows
         foreach ($data as $row) {
             # Add the rows to the body
             fputcsv($output, $row); // here you can change delimiter/enclosure
         }
         # Close the stream off
         fclose($output);
     }
}
