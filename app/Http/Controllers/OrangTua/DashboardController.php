<?php

namespace App\Http\Controllers\OrangTua;

use App\Http\Controllers\Controller;
use App\Services\ParentDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, ParentDashboardService $service): View
    {
        abort_unless($request->user()?->role === 'orang_tua', 403);
        return view('orang-tua.dashboard', $service->dashboard($request->user()));
    }
}
