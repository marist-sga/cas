<?php namespace Subfission\Cas\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Subfission\Cas\Facades\Cas;
use App\User;

class CASAuth
{

    protected $auth;
    protected $cas;

    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
        $this->cas = app('cas');
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->auth->guest() || ! session()->has('cas_user')) {
            if ($request->ajax()) {
                return response('Unauthorized.', 401);
            }
            if ( ! $this->cas->isAuthenticated()) {
                $this->cas->authenticate();
            }
            // We setup CAS here to reduce the amount of objects we need to build at runtime.  This
            // way, we only create the CAS calls only if the user has not yet authenticated.
            session()->put('cas_user', $this->cas->user());
        } else {
            $user = Cas::user();
            $splitUser = explode($user, "@");
            $inputUser = User::firstOrCreate(['email' => $user, 'cwid' => $splitUser[0]]);
        }

        return $next($request);
    }
}
