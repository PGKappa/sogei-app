<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PGVirtual\Core\Models\Operator;
use PGVirtual\Core\Models\User;
use PGVirtual\Core\Models\Language;
use PGVirtual\Core\Models\Currency;
use PGVirtual\Core\Models\UserChannelLink;
use PGVirtual\Core\Controllers\OperatorController;
use Illuminate\Support\Facades\Auth;
use PGVirtual\Core\Models\Error as ErrorModel;
use PGVirtual\Core\Models\Viewer;
use \Exception;

class ValidateLocalSession
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
        try {
            if (!$request->hasHeader('Authorization')) {
                throw new Exception("Missing Authorization header.");
            }
            if (!$request->hasHeader('Operator')) {
                throw new Exception("Missing Operator header.");
            }
            $operatorHeader = $request->header('Operator');
            $uiTypeHeader = $request->header('UI-Type');
            $token = $request->bearerToken();
            $operatorQuery = Operator::where('name', $operatorHeader);
            if (!$operatorQuery->exists()) {
                return ErrorModel::response(ErrorModel::$OPERATOR_NOT_FOUND);
            }
            if ($uiTypeHeader == 'viewer') {
                $viewerQuery = Viewer::where('macaddress', $token)->where('monitor', $request->monitor);
                if (!$viewerQuery->exists()) {
                    return ErrorModel::response(ErrorModel::$VIEWER_NOT_FOUND);
                }
                $viewer = $viewerQuery->first();
                $user = User::where('id', $viewer->user_id)->first();
                Auth::login($user);
                return $next($request);
            }
            $operator = $operatorQuery->first();
            if (empty($token)) {
                return ErrorModel::response(ErrorModel::$SESSION_INVALID," (2)");
            }
            if (!User::where('remember_token', $token)->exists()) {
                //return ErrorModel::response(ErrorModel::$SESSION_INVALID," (1)");
                $userQuery = User::where('name', '~anonymous-user');
            }
            else {
                $userQuery = User::where('remember_token', $token);
            }
            /*if (!$userQuery->exists()) {
                return ErrorModel::response(ErrorModel::$SESSION_INVALID," (3)");
            }*/
            $user = $userQuery->first();
            Auth::login($user);
            return $next($request);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }
}
