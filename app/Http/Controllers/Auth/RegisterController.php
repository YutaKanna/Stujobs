<?php

namespace App\Http\Controllers\Auth;

use App\Mail\VerifyMail;
use App\Models\Company;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Models\VerifyUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use App\Rules\Captcha;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'email' => 'required|string|email|max:255|unique:users',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6|confirmed',
            'g-recaptcha-response' => new Captcha(),
            'cgu' => 'required',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        $user_data = Input::only('email', 'password', 'password_confirmation');
        $company_data = Input::only('name', 'siret', 'address', 'phone');

        $user = new User();
        $user->setAttribute('email', $user_data['email']);
        $user->setAttribute('password', Hash::make($data['password']));
        $user->save();

        $company = new Company();
        $company->setAttribute('user_id', $user->getAttribute('id'));
        $company->setAttribute('name', $company_data['name']);
        $company->setAttribute('siret', $company_data['siret']);
        $company->setAttribute('address', $company_data['address']);
        $company->setAttribute('phone', $company_data['phone']);
        $company->save();

        $verifyUser = VerifyUser::create([
            'user_id' => $user->getAttribute('id'),
            'token' => str_random(40)
        ]);

        Mail::to($user->email)->send(new VerifyMail($user));

        return $user;
    }

    public function verifyUser($token)
    {
        $verifyUser = VerifyUser::where('token', $token)->first();
        if (isset($verifyUser)) {
            $user = $verifyUser->user;
            if (!$user->verified) {
                $verifyUser->user->verified = 1;
                $verifyUser->user->save();
                $status = "Votre adresse email est vérifiée. Vous pouvez vous connecter.";
            } else {
                $status = "Votre adresse email est déjà vérifiée. Vous pouvez vous connecter";
            }
        } else {
            return redirect('/login')->with('warning', "Désolé, votre email n'est pas valide.");
        }

        return redirect('/login')->with('status', $status);
    }

    /**
     * @param Request $request
     * @param $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function registered(Request $request, $user)
    {
        $this->guard()->logout();
        return redirect('/login')->with('status', 'Nous vous avons envoyé un email de vérification. Merci de cliquer sur le bouton de vérification dans le mail.');
    }
}
