<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Meeting;
use App\User;
use JWTAuth;

class RegistrationController extends Controller
{    
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'meeting_id' => 'required',
            'user_id' => 'required',
        ]);

        $meeting_id = $request->input('meeting_id');
        $user_id = $request->input('user_id');        

        try {
                $meeting = Meeting::findOrFail($meeting_id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => 'Could not find meeting with id = '.$meeting_id], 500);
        }

        $user = User::findOrFail($user_id);

        $message = [
            'msg' => 'User is already registered for meeting',
            'user' => $user,
            'meeting' => $meeting,
            'unregister' => [
                'href' => 'api/v1/meeting/registration/' . $meeting->id,
                'method' => 'DELETE',
            ]
        ];
        if ($meeting->users()->where('users.id', $user->id)->first()) {
            return response()->json($message, 404);
        };

        $user->meetings()->attach($meeting);

        $response = [
            'msg' => 'User registered for meeting',
            'meeting' => $meeting,
            'user' => $user,
            'unregister' => [
                'href' => 'api/v1/meeting/registration/' . $meeting->id,
                'method' => 'DELETE'
            ]
        ];

        return response()->json($response, 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
                $meeting = Meeting::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => 'Could not find meeting with id = '.$id], 500);
        }

        if(!$user = JWTAuth::parseToken()->authenticate())
        {
            return response()->json(['msg' => "User not found", 404]);
        }

        if (!$meeting->users()->where('users.id', $user->id)->first()) {
            return response()->json(['msg' => 'user not registered for meeting, delete operation not successful'], 401);
        };

        $meeting->users()->detach($user->id);

        $response = [
            'msg' => 'User unregistered for meeting',
            'meeting' => $meeting,
            'user' => 'tbd',
            'register' => [
                'href' => 'api/v1/meeting/registration',
                'method' => 'POST',
                'params' => 'user_id, meeting_id'
            ]
        ];

        return response()->json($response, 200);
    }
}
