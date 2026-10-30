<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\StudentGuardian;
use App\Services\ParentTeacherChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParentContactChatController extends Controller
{


    public function messages(
        Request $request,
        StudentGuardian $studentGuardian,
        ParentTeacherChatService $chat
    ): JsonResponse {
        $thread = $chat->teacherThread(
            $request->user(),
            (int) $studentGuardian->id
        );

        return response()->json([
            'messages' => $chat->messages(
                $thread,
                $request->user()
            ),
        ]);
    }

    public function store(
        Request $request,
        StudentGuardian $studentGuardian,
        ParentTeacherChatService $chat
    ): JsonResponse {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000', 'not_regex:/^\s*$/'],
            'client_message_id' => ['required', 'uuid'],
        ]);
        $thread = $chat->teacherThread($request->user(), (int) $studentGuardian->id);
        $message = $chat->send(
            $thread,
            $request->user(),
            $data['body'],
            $data['client_message_id']
        );

        return response()->json(['message' => 'Balasan terkirim.', ...$message], 201);
    }
}
