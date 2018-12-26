<?php

namespace App\Http\Controllers;
use App\Session;
use Illuminate\Http\Request;
use App\Http\Resources\SessionResource;
use App\Events\SessionEvent;

class SessionController extends Controller
{
    public function create(Request $request)
    {
        $session = Session::create(['user1_id' => auth()->id(), 'user2_id' => $request->friend_id]);

        $modifiedSession = new SessionResource($session);
        
        broadcast(new SessionEvent($modifiedSession, auth()->id()));

        return $modifiedSession;
    }
}
