<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Session;
use App\Http\Resources\ChatResource;
use App\Events\PrivateChatEvent;
use App\Events\MsgReadEvent;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use App\Providers\AppServiceProvider;

class ChatController extends Controller
{
    
    public function send(Session $session, Request $request)
    {
        $message = $session->messages()->create([
            'content' => $request->content,
            'stringlength' => 0]);

        $chat = $message->createForSend($session->id);

        $message->createForReceive($session->id, $request->to_user);

        broadcast(new PrivateChatEvent($message->content, $message->image, $message->stringlength, $chat));
        
        return response($chat->id, 200);
    }

    public function upload(Session $session, Request $request)
    {
        // \Log::info($request->all());
        
        foreach ($request->images as $file) {
            $exploded = explode(',', $file);
            $decoded = base64_decode($exploded[1]);

            if(str_contains($exploded[0], 'jpeg'))
                $extension = 'jpg';
            else
                $extension = 'png';
            
            $fileName = str_random().'.'.$extension;
            $path = public_path().'/images/'.$fileName;

            file_put_contents($path, $decoded);

            $stringLength = strlen($fileName);

            $message = $session->messages()->create([
                'image' => $fileName,
                'stringlength' => $stringLength]);

            $chat = $message->createForSend($session->id);

            $message->createForReceive($session->id, $request->to_user);

            broadcast(new PrivateChatEvent($message->content, $message->image, $message->stringlength, $chat));           
        }
        
        return response($chat->id, 200);
        
    }
    

    public function chats(Session $session)
    {
        $allChats = ChatResource::collection($session->chats->where('user_id', auth()->id())->sortByDesc("message_id")->paginate(10));
                   
        return $allChats;
    }

    public function read(Session $session)
    {
        $chats = $session->chats->where('read_at', null)->where('type', 0)->where
        ('user_id', '!=', auth()->id());

        foreach ($chats as $chat){
            $chat->update(['read_at' => Carbon::now()]);
            broadcast(new MsgReadEvent(new ChatResource($chat),$chat->session_id));
        }
    }

    public function clear(Session $session)
    {
        $session->deleteChats();
        $session->chats->count() == 0 ? $session->deleteMessages() : '';
        return response('cleared', 200);
    }
}
