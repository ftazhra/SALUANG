<?php

namespace App\Http\Controllers;

use App\Services\WebsiteNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function open(Request $request, int $notification, WebsiteNotificationService $notifications): RedirectResponse
    {
        return redirect()->to($notifications->open($request->user(), $notification));
    }

    public function destroy(Request $request, int $notification, WebsiteNotificationService $notifications): JsonResponse
    {
        $notifications->deleteFor($request->user(), $notification);

        return response()->json(['deleted' => true]);
    }
}
