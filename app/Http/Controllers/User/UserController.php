<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\ApiController;
use App\Mail\UserCreated;
use App\Transformers\UserTransformer;
use App\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;

class UserController extends ApiController
{

    public function __construct()
    {
        $this->middleware('client.credentials')->only(['store', 'resend']);

        $this->middleware('auth:api')->except(['store','verify','resend']);

        $this->middleware('transform.input:' . UserTransformer::class)->only(['store', 'update']);

        $this->middleware('scope:manage-account')->only(['show','update']);

        $this->middleware('can:view,user')->only('show');

        $this->middleware('can:update,user')->only('update');

        $this->middleware('can:delete,user')->only('destroy');

    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $users = User::all();

        return $this->showAll($users);

//        return $this->showOne($users)

    }

//    /**
//     * Show the form for creating a new resource.
//     *
//     * @return \Illuminate\Http\Response
//     */
//    public function create()
//    {
//        //
//    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed'
        ]);

        $data =  $request->all();
        $data['password'] = bcrypt($request->password);
        $data['verified'] = User::UNVERIFIED_USER;
        $data['verification_token'] = User::generateVerificationCode();
        $data['admin'] = User::REGULAR_USER;



        $user = User::create($data);

        $token = $user->createToken('Laravel Password Grant Client')->accessToken;


//        return response()->json(['data' => $users], 201);
        return $this->showOne($user);

    }

    /**
     * Display the specified resource.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function show(User $user)
    {
//        return response()->json(['data' => $user], 200);

        return $this->showOne($user);

    }

//    /**
//     * Show the form for editing the specified resource.
//     *
//     * @param int $id
//     * @return void
//     */
//    public function edit($id)
//    {
//        //
//    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function update(Request $request, User $user)
    {

        $request->validate([
            'email' => 'email|unique:users,email,' . $user->id,
            'password' => 'min:6|confirmed',
            'admin' => 'in:' . User::ADMIN_USER . ',' . User::REGULAR_USER,
        ]);

        if ($request->has('name')){
            $user->name = $request->name;
        }

        if ($request->has('email') && $user->email != $request->email){
            $user->verified = User::UNVERIFIED_USER;
            $user->verification_token = User::generateVerificationCode();
            $user->email = $request->email;
        }

        if ($request->has('password')){
            $user->password = Hash::make($request->password);
        }

        if ($request->has('admin')){
            if (!$user->isVerified()){
                return $this->errorResponse('Only verified user can modify the admin field',409);
            }
            $user->admin = $request->admin;
        }

        if (!$user->isDirty()){
            return $this->errorResponse('You need to specify a different value to update',422);
        }

        $user->save();

        return $this->showOne($user);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(User $user)
    {
        $user->delete();

//        return response()->json(['data' => $user], 200);
        return $this->showOne($user);
    }

    public function verify($token){
        $user = User::where('verification_token',$token)->firstOrFail();

        $user->verified = User::VERIFIED_USER;
        $user->verification_token = null;

        $user->save();

        return $this->showMessage('The account has been verified successfully');
    }

    public function resend(User $user){
        if ($user->isVerified()){
            return $this->errorResponse('This user is already verified',409);
        }

        try {
            retry(5, function () use ($user) {
                Mail::to($user)->send(new UserCreated($user));
            }, 100);
        } catch (Exception $e) {
            $this->errorResponse($e, 409);
        }

        return $this->showMessage('The verification email has been resent');
    }
}
