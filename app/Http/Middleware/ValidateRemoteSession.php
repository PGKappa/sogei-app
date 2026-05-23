<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PGVirtual\Core\Models\Operator;
use PGVirtual\Core\Models\User;
use PGVirtual\Core\Models\Language;
use PGVirtual\Core\Models\Currency;
use PGVirtual\Core\Models\UserChannelLink;
use PGVirtual\Core\Models\UserLevel;
use PGVirtual\Core\Models\OperatorLevel;
use PGVirtual\Core\Models\Error as ErrorModel;
use PGVirtual\Core\Models\Group;
use PGVirtual\Core\Controllers\OperatorController;

class ValidateRemoteSession
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
        if (!$request->hasHeader('Authorization')) {
            return ErrorModel::response(ErrorModel::$ACCESS_DENIED);
        }

        if (!$request->hasHeader('Operator')) {
            return ErrorModel::response(ErrorModel::$OPERATOR_MISSING_DATA);
        }

        $operatorId = $request->header('Operator');
        $operator = Operator::where('name', $operatorId)->first();
        if (!$operator) {
            return ErrorModel::response(ErrorModel::$OPERATOR_NOT_FOUND, 'remote');
        }

        $token = $request->bearerToken();
        $operatorResponse = OperatorController::validation($operator, $token);
        if (!$operatorResponse) {
            return ErrorModel::response(ErrorModel::$INVALID_VALIDATION_RESPONSE);
        }

        if (empty($operatorResponse['status']) || $operatorResponse['status'] != 1024) {
            return ErrorModel::response(ErrorModel::$INVALID_STATUS);
        }

        if (empty($operatorResponse['playerId']) || empty($operatorResponse['currency']) || empty($operatorResponse['lang'])) {
            return ErrorModel::response(ErrorModel::$INVALID_MISSING_DATA);
        }

        $operatorLevelResponse = OperatorLevel::where([
            ['operator_id', $operator->id],
            ['level_id', isset($operatorResponse['level'])?$operatorResponse['level']:0]
        ]);
        if ($operatorLevelResponse->doesntExist()) {

           return ErrorModel::response(ErrorModel::$INVALID_OPERATOR_LEVEL);
        
        }

        $languageQuery = Language::where('name', $operatorResponse['lang']);
        if ($languageQuery->doesntExist()) {
            return ErrorModel::response(ErrorModel::$LANGUAGE_NOT_FOUND);
        }

        $language = $languageQuery->first();
        $currencyQuery = Currency::where('name', $operatorResponse['currency']);
        if ($currencyQuery->doesntExist()) {
            return ErrorModel::response(ErrorModel::$CURRENCY_NOT_FOUND);
        }
        
        $currency = $currencyQuery->first();
        User::where('remember_token', $token)->update(['remember_token' => null]);
        if (User::where('name', $operatorResponse['playerId'])->exists()) {
            $user = User::where('name', $operatorResponse['playerId'])->first();
             $user->language_id = $language->id;
        } else {
            $user = new User;
            $user->name = $operatorResponse['playerId'];
            $user->password = md5(rand(100000, 999999));
            $user->operator_id = $operator->id;
            $user->timezone = $operatorResponse['timezone'] ?? 'Europe/Rome';
            $user->language_id = $language['id'] ?? 2;
            $user->currency_id = $currency['id'] ?? 1;

            if (isset($operatorResponse['level'])) {
                $userLevel = $operatorResponse['level'];
            }

            if (!isset($userLevel) || UserLevel::where('id', $userLevel)->doesntExist()) {
                $userLevel = 1;
            }
        
            $user->level = $userLevel;
            $user->status = 1;
        }
        $user->remember_token = $token;
        if (!empty($operatorResponse['group']['id']) && !empty($operatorResponse['group']['name'])) {
            Group::updateOrInsert(
                 [
                    'operator_id' =>  $operator->id,
                    'ext_id' => $operatorResponse['group']['id']
                ],
                [
                    'name' => $operatorResponse['group']['name']
                ]
            );
            $group = Group::where('ext_id', $operatorResponse['group']['id'])
                ->where('operator_id', $operator->id)
                ->first();
            $user->group_id = $group->id;
        }
        if (!empty($operatorResponse['level'])) {
            $userLevel = $operatorResponse['level'];

            if (!isset($userLevel) || UserLevel::where('id', $userLevel)->doesntExist()) {
                $userLevel = 1;
            }
            $user->level = $userLevel;
        }
        $user->save();

        $DEFAULT_CHANNELS_TO_ENABLE = ['1', '3'];
        if ($token == 'ffffffff-ffff-ffff-ffff-ffffffffffff') {
            $DEFAULT_CHANNELS_TO_ENABLE = ['5', '7'];
        }
        if ($token == 'ffffffff-ffff-ffff-ffff-ffffffffff11') {
            $DEFAULT_CHANNELS_TO_ENABLE = ['9', '11'];
        }

        foreach ($DEFAULT_CHANNELS_TO_ENABLE as $channelId) {
            if (!UserChannelLink::where('user_id', $user->id)->where('channel_id', $channelId)->exists()) {
                $ucl = new UserChannelLink;
                $ucl->user_id = $user->id;
                $ucl->channel_id = $channelId;
                $ucl->save();
            }
        }
        return $next($request);
    }
}
