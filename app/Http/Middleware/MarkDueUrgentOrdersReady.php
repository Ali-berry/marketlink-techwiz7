<?php

namespace App\Http\Middleware;

use App\Services\UrgentOrderService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// scheduler ka backup - farmer / customer apna dashboard ya orders page khole to pehle us ke due urgent
// orders ready ho jate hain, "schedule:work" na chal raha ho tab bhi
class MarkDueUrgentOrdersReady
{
    public function __construct(private readonly UrgentOrderService $urgentOrderService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $this->urgentOrderService->markDueOrdersReady($request->user());
        }

        return $next($request);
    }
}
