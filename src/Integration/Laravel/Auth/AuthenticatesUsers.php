<?php

namespace Firebase\Integration\Laravel\Auth;

use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class AuthenticatesUsers.
 */
trait AuthenticatesUsers
{

    public function getAuth(Request $request)
    {
        return view('vinkas.firebase.auth');
    }

    public function postAuth(Request $request)
    {
        $data = $request->all();
        $validator = $this->validator($data);
        if ($validator->fails()) {
            return $this->onFail($validator->errors()->first());
        }

        $idToken = $request->input('id_token');

        JWT::$leeway = 8;
        $content = file_get_contents("https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com");
        $kids = json_decode($content, true);
        $jwt = JWT::decode($idToken, $kids, array('RS256'));
        $fbpid = config('services.firebase.project_id');
        $issuer = 'https://securetoken.google.com/' . $fbpid;
        if ($jwt->aud != $fbpid)
            return $this->onFail('Invalid audience');
        elseif ($jwt->iss != $issuer)
            return $this->onFail('Invalid issuer');
        elseif (empty($jwt->sub))
            return $this->onFail('Invalid user');
        else {
            $uid = $jwt->sub;
            $user = $this->firebaseLogin($uid, $request);
            if ($user) {
                $user->token = $idToken;
                $user->save();
                return response()->json(['success' => true, 'redirectTo' => $this->redirectPath()]);
            }
            else {
                return $this->onFail('Error');
            }
        }
    }

    protected function onFail($message)
    {
        return response()->json(['success' => false, 'message' => $message]);
    }

    protected function firebaseLogin($uid, $request)
    {
        $user = Auth::getProvider()->retrieveById($uid);

        if (is_null($user)) {
            $this->firebaseRegister($uid, $request);
        }


        $remember = $request->has('remember') ? $request->input('remember') : false;
        return Auth::loginUsingId($uid, $remember);
    }

    protected function firebaseRegister($uid, $request)
    {
        $data['id'] = $uid;
        $data['name'] = $request->has('name') ? $request->input('name') : null;
        $data['email'] = $request->has('email') ? $request->input('email') : null;
        $data['photo_url'] = $request->has('photo_url') ? $request->input('photo_url') : null;
        $this->create($data);
    }


    protected function redirectPath()
    {
        return isset($this->redirectTo) ? $this->redirectTo : '/';
    }

}
