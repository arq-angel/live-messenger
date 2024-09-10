<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Traits\FileUploadTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessengerController extends Controller
{
    use FileUploadTrait;

    public function index()
    {
        return view('messenger.index');
    }

    /** Search User Profiles */
    public function search(Request $request)
    {
        $getRecords = null;
        $query = $request['query'];
        $records = User::where('id', '!=', Auth::user()->id)
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('user_name', 'LIKE', "%{$query}%")
            ->paginate(10);

        if ($records->total() < 1) {
            $getRecords .= "<p class='text-center'>Nothing to show!</p>";
        }

        foreach ($records as $record) {
            $getRecords .= view('messenger.components.search-item', compact('record'))->render();
        }

        return response()->json([
            'records' => $getRecords,
            'last_page' => $records->lastPage(),
        ]);
    }

    // fetch user by id
    public function fetchIdInfo(Request $request)
    {
        $fetch = User::where('id', '=', $request['id'])->first();

        return response()->json([
            'fetch' => $fetch,
        ]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            "message" => ["nullable"],
            "id" => ["required", "integer"],
            "temporaryMsgId" => ["required"],
            "attachment" => ["nullable", "max:1024", "image"],
        ]);

        // store the message in DB
        $attachmentPath = $this->uploadFile($request, "attachment");
        $message = new Message();
        $message->from_id = Auth::user()->id;
        $message->to_id = $request->id;
        $message->body = $request->message;
        if ($attachmentPath) {
            $message->attachment = json_encode($attachmentPath);
        }
        $message->save();

        return response()->json([
            'message' => $message->attachment ? $this->messageCard($message, true) : $this->messageCard($message),
            'tempID' => $request->temporaryMsgId,
        ]);
    }

    private function messageCard($message, $attachment = false)
    {
        return view("messenger.components.message-card", compact('message', 'attachment'))->render();
    }

    // fetch messages from database
    public function fetchMessages(Request $request)
    {
        $messages = Message::where("from_id", Auth::user()->id)->where('to_id', $request['id'])
            ->orWhere("from_id", $request->id)->where('to_id', AUth::user()->id)
            ->latest()->paginate(20);

        $response = [
            'last_page' => $messages->lastPage(),
            'last_message' => $messages->last(),
            'messages' => '',
        ];

        if (count($messages) < 1) {
            $response['messages'] = "<div class='d-flex justify-content-center align-items-center mx-auto h-100'><p>Say 'Hi' and start messaging.</p></div>";
            return response()->json($response);
        }

        $allMessages = '';
        foreach ($messages->reverse() as $message) {
            $allMessages .= $this->messageCard($message, $message->attachment ? true : false);
        }

        $response['messages'] = $allMessages;

        return response()->json($response);
    }
}
