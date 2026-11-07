<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackLastSeen
{
    private const INTERVAL_IN_MINUTES = 5;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user && ($user->last_seen_at === null || $user->last_seen_at->lt(now()->subMinutes(self::INTERVAL_IN_MINUTES)))) {
            User::whereKey($user->id)->toBase()->update(['last_seen_at' => now()]);
        }

        return $next($request);
    }
}
