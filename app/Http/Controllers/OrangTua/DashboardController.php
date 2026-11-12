<?php

namespace App\Http\Controllers\OrangTua;

use App\Http\Controllers\Controller;
use App\Services\ParentDashboardService;
use App\Services\WebsiteNotificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        ParentDashboardService $service,
        WebsiteNotificationService $websiteNotifications
    ): View {
        abort_unless($request->user()?->role === 'orang_tua', 403);
        $validated = $request->validate([
            'child_id' => ['sometimes', 'integer', 'min:1'],
        ]);
        $data = $service->dashboard(
            $request->user(),
            isset($validated['child_id']) ? (int) $validated['child_id'] : null
        );
        $data['notifications'] = $websiteNotifications->latestFor($request->user());
        $data['unreadNotificationCount'] = $websiteNotifications->unreadCountFor($request->user());

        return view('orang-tua.dashboard', $data);
    }
}
