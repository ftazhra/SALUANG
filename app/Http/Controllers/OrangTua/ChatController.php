<?php

namespace App\Http\Controllers\OrangTua;

use App\Http\Controllers\Controller;
use App\Services\ParentTeacherChatService;
use App\Services\ChatPresenceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    public function activity(Request $request, ChatPresenceService $presence): JsonResponse
    {
        abort_unless(in_array($request->user()?->role, ['guru', 'orang_tua'], true), 403);
        $presence->recordActivity($request->user());

        return response()->json(['recorded' => true]);
    }

    public function teachers(Request $request, ParentTeacherChatService $chat): JsonResponse
    {
        abort_unless($request->user()?->role === 'orang_tua', 403);
        return response()->json(['teachers' => $chat->teacherThreads($request->user())]);
    }

    public function messages(Request $request, ParentTeacherChatService $chat): JsonResponse
    {
        abort_unless($request->user()?->role === 'orang_tua', 403);
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'min:1'],
            'teacher_id' => ['required', 'integer', 'min:1'],
        ]);
        $thread = $chat->parentThread(
            $request->user(),
            (int) $data['student_id'],
            (int) $data['teacher_id']
        );

        return response()->json([
            'messages' => $chat->messages($thread, $request->user()),
        ]);
    }

    public function store(Request $request, ParentTeacherChatService $chat): JsonResponse
    {
        abort_unless($request->user()?->role === 'orang_tua', 403);
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'min:1'],
            'teacher_id' => ['required', 'integer', 'min:1'],
            'body' => ['required', 'string', 'max:2000', 'not_regex:/^\s*$/'],
            'client_message_id' => ['required', 'uuid'],
        ]);
        $thread = $chat->parentThread(
            $request->user(),
            (int) $data['student_id'],
            (int) $data['teacher_id']
        );
        $message = $chat->send(
            $thread,
            $request->user(),
            $data['body'],
            $data['client_message_id']
        );

        return response()->json(['message' => 'Pesan terkirim.', ...$message], 201);
    }
}
