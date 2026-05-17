<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidStudent
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
   public function handle(Request $request, Closure $next)
{
    $studentId = $request->header('X-Student-ID');
    $department = $request->header('X-Department');

    // Logic: Only allow BDU students from specific departments
    if (!$studentId || !str_contains($studentId, 'BDU')) {
        return response()->json(['error' => 'Invalid Student Credentials'], 401);
    }

    // Attach the department to the request so the Controller can see it
    $request->merge(['student_dept' => $department]);

    return $next($request);
}
}
