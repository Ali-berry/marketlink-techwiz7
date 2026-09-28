<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    // Breeze login ke baad /dashboard bhejta hai, yahan se apne panel pe
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->route($request->user()->dashboardRouteName());
    }
}
