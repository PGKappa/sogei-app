<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PGVirtual\Core\Models\Operator;

class Integration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $request->validate([
          'operator' => 'required',
        ]);
        if (Operator::where('name',$request->operator)->exists()) {
          $operator = Operator::where('name',$request->operator)->first();
          $opts = json_decode($operator->opts,1);
          if ((!empty($opts['whitelistedIPs'])) && (in_array($request->ip(),$opts['whitelistedIPs']))) {
            $request->attributes->add(['operator_id' => $operator->id]);
            return $next($request);
          }
          else {
            $exp['ret_code'] = '11008';
            $exp['description'] = 'UNABLE TO EXECUTE LOGIN';
            return json_encode($exp);
          }
        }
        else {
          $exp['ret_code'] = '11007';
          $exp['description'] = 'UNABLE TO EXECUTE LOGIN';
          return json_encode($exp);
        }

        //dd(Operator::all());
    }
}
