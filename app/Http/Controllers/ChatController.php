<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\MessageSent;
use App\Models\User;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $users = User::where('id', '!=', Auth::id())->get();
        return view('chat_new', compact('users'));
    }

    public function fetchUsers()
    {
        $users = User::where('id', '!=', Auth::id())->get(['id', 'name']);
        return response()->json($users);
    }

    public function fetchMessages(Request $request)
    {
        $userId = $request->input('user_id');
        $authId = Auth::id();

        // Mark unread messages as read
        Message::where('sender_id', $userId)
            ->where('receiver_id', $authId)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        // Fetch all messages
        $messages = Message::where(function ($query) use ($userId, $authId) {
            $query->where('sender_id', $authId)
                ->where('receiver_id', $userId);
        })->orWhere(function ($query) use ($userId, $authId) {
            $query->where('sender_id', $userId)
                ->where('receiver_id', $authId);
        })
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }



    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string|max:1000',
        ]);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
        ]);
        broadcast(new MessageSent($message))->toOthers();
        return response()->json(['status' => 'Message sent!', 'message' => $message]);
    }

    public function delete($id)
    {
        $message = Message::findOrFail($id);

        // Optional: check if the current user is allowed to delete
        if ($message->sender_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message->delete();

        return response()->json(['success' => true]);
    }

}