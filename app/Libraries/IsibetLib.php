<?php

namespace App\Libraries;


use App\Libraries\ADMConfigs;
use Carbon\Carbon;
use Exception;
use Faker\Core\Number;
use Illuminate\Support\Facades\Log;
use PGVirtual\Core\Libraries\Math;
use PGVirtual\Core\Models\ApiLog;
use PGVirtual\Core\Models\Channel;
use PGVirtual\Core\Models\Event;
use PGVirtual\Core\Models\Game;
use PGVirtual\Core\Models\RacersRegistry;
use PGVirtual\Core\Models\Error as ErrorModel;
use PGVirtual\Core\Models\User;
use PGVirtual\Core\Models\Configuration;

use PhpParser\Node\Expr\Cast\Double;
use Ramsey\Uuid\Rfc4122\UuidV4;
use UnableToLoadConfigsException;

class IsibetLib
{

    /* swagger https://wstest.isibet.it/pgvirtualinterface/swagger/index.html */

    //public static $ENDPOINT= 'https://0e874461-65a2-42cb-ae3c-d67640c6dc79.mock.pstmn.io';


    public static function getPalinsesto($eventId, $controllerId)
    {
        echo "getPalinsesto :: controllo palinsesto Controller:" . $controllerId . "  evento: " . $eventId . PHP_EOL;
        try {
            $endpoint = Configuration::where('key', 'ISIBET_ENDPOINT')->first()->getValue();
            $client = new \GuzzleHttp\Client();
            $requestData = self::makeRequest($controllerId);


            $requestData['eventId'] = $eventId;
            $response = $client->post(
                $endpoint . "/EventCodes",
                [
                    \GuzzleHttp\RequestOptions::JSON => $requestData
                ]
            );

            $result = json_decode($response->getBody(), true);
            print_r($result);
            $apiLog = ApiLog::create([
                'method_name' => "getPalinsesto",
                'operator_id' => '1',
                'URL' => $endpoint . "/EventCodes",
                'request_data' => $eventId,
                'response_data' => json_encode($result)
            ]);


            Log::info(json_encode($result, JSON_PRETTY_PRINT));
            Event::where('id', $eventId)->update(['ext_pal_id' => $result['regulatorScheduleCode'], 'ext_event_id' => $result['regulatorEventCode']]);
        } catch (Exception $ex) {
            Log::info("ERRORE AGGIORNAMENTO PALINSESTO {eventId}", ['eventId' => $eventId]);

            Log::info($ex->getMessage());
        }
    }

    public static function makeRequest($controllerId)
    {
        $requestData = [
            'protocolVersion' => 1,
            'controllerId' => $controllerId,
            'transactionId' => UuidV4::uuid4()->toString(),
            'messageDateTime' => Carbon::now()->format('Y-m-d\TH:i:s\Z'),

        ];

        return $requestData;
    }

    public static function favourite($eventData, &$racers)
    {



        /* calcolo il favorito prendendo la quota più bassa dei winnerOdds */
        $winnerOdds = $eventData['current']['odds']['winner'];

        $tempOdds = null;
        $favourite = 0;
        for ($i = 1; $i <= count($winnerOdds); $i++) {



            if ($tempOdds == null) {

                $tempOdds = $winnerOdds[$i];

                $favourite = $i;
            }

            if ($tempOdds['odd'] > $winnerOdds[$i]['odd']) {

                $tempOdds = $winnerOdds[$i];
                $favourite = $i;
            }
        }
        if ($favourite > 0)
            $racers[$favourite - 1]['favourites'] = true;

        return $favourite;
    }

    public static function show($palimpsest_id, $event_id)
    {

        $user = User::where('id', 27)->first();
        $eventQuery = Event::where('int_pal_id', $palimpsest_id)
            ->where('int_event_id', $event_id);
        if (!$eventQuery->exists()) {
            return ErrorModel::response(ErrorModel::$EVENT_NOT_FOUND);
        }
        $event = $eventQuery->first();

        $channel = Channel::where('id', $event->channel_id)->first();
        $game = Game::where('id', $channel->game_id)->first();
        $eventController = 'PGVirtual\\Game' . ucfirst(strtolower($game->name)) . '\\Controllers\\EventController';
        $exp = $eventController::show($event, $user);
        if (is_string($exp)) {
            return $exp;
        }
        $exp['ret_code'] = 1024;
        return $exp;
    }




    public static function createVirtualEvent2($event, $eventData)
    {



        //die();

        $sportcode = 0;
        if ($event['game']['id'] == 1) $sportcode = 1;
        if ($event['game']['id'] == 2) $sportcode = 2;
        if ($event['game']['id'] == 3) $sportcode = 3;
        if ($event['game']['id'] == 4) $sportcode = 1;
        if ($event['game']['id'] == 5) $sportcode = 2;
        if ($event['game']['id'] == 6) $sportcode = 3;
        if ($event['game']['id'] == 7) $sportcode = 1;
        if ($event['game']['id'] == 8) $sportcode = 2;
        if ($event['game']['id'] == 9) $sportcode = 3;
        if ($event['game']['id'] == 10) $sportcode = 1;
        if ($event['game']['id'] == 11) $sportcode = 2;
        if ($event['game']['id'] == 12) $sportcode = 3;


        $amsGame = ADMConfigs::getGameByCode($sportcode);
        $virtualEvent = [
            'racers' => [],
            'betTypes' => [],
            'eventId' => $event["event"]["Id"],
            'sportCode' => $sportcode,
            'eventName' => $amsGame["desc"],
            "eventDateTime" => $event["event"]["time"],
            'trackName' => $event["channel"]["track_name"]
        ];
        for ($i = 0; $i < count($eventData['current']['racers']); $i++) {

            $racerData = $eventData['current']['racers'][$i];
            $r = RacersRegistry::where('id', '=', $racerData['racerId'])->first();
            $racer['number'] = $racerData['number'];
            $racer['name'] = $r->name;
            $racer['probWin'] = $event['opts']['racers'][$i]['chance'];
            $racer['favourites'] = false;
            //$racers[] = $racer;
            array_push($virtualEvent['racers'], $racer);
        }

        self::favourite($eventData, $virtualEvent['racers']);


        /* BetTypes */
        $odds = $eventData['current']['odds'];
        foreach ($odds as $key => $odd) {

            $market = ADMConfigs::getMarket($key);
            $betType['betCode'] = $market['code'];
            /* calcolo rtp */

            $betType['isPivot'] = $market['code'] == 101 ? true : false;
            $betType['outcomes'] = [];
            $outcome = array();

            //print_r($odd);
            $valRtps = array_column($odd, 'rtp');
            $sumRtps = array_sum($valRtps);
            $betType['rtp'] = round(($sumRtps / count($eventData['current']['racers'])) / 1000000, 2);
            foreach ($odd as $key2 => $value2) {




                if ($key == "evenodd" || $key == "underover" . count($eventData['current']['racers'])) {
                    //print_r($value2);
                    $outcome['code'] = '' . $key2;
                    /* switch ($key2){
                        case "under":
                            $outcome['code']= 1;
                            break;
                        case "over":
                            $outcome['code']= 2;
                            break;
                        case "even":
                            $outcome['code']= 1;
                            break;
                        case "odd":
                            $outcome['code']= 2;
                            break;

                    } */
                    if ($key == "evenodd" &&  $key2 == 1) {
                        $outcome['description'] = "pari";
                    } else if ($key == "evenodd" &&  $key2 == 2) {
                        $outcome['description'] = "dispari";
                    } else if ($key == "underover" . count($eventData['current']['racers']) &&  $key2 == 1) {
                        $outcome['description'] = "under";
                    } else if ($key == "underover" . count($eventData['current']['racers']) &&  $key2 == 2) {
                        $outcome['description'] = "over";
                    }


                    //$outcome['description'] = $key.' - '.$key2;

                    $outcome['odd'] = (float)$value2['odd'] / 100;
                    $outcome['probWin'] = (float)$value2['prob'];
                    $valRtps = array_column($value2, 'rtp');
                    $sumRtps = array_sum($valRtps);
                    $betType['rtp'] = round(($sumRtps / count($value2)) / 1000000, 2);
                    array_push($betType['outcomes'], $outcome);
                } else if ($key == "exacta" || $key == "quinella") {

                    $valRtps = array_column($value2, 'rtp');
                    $sumRtps = array_sum($valRtps);
                    $betType['rtp'] = round(($sumRtps / count($value2)) / 1000000, 2);
                    foreach ($value2 as $key3 => $value3) {
                        $outcome['description'] = $key2 . "-" . $key3;
                        $outcome['code'] = $key2 . "-" . $key3;
                        $outcome['odd'] = (float) $value3['odd'] / 100;
                        $outcome['probWin'] = (float)$value3['prob'];
                        array_push($betType['outcomes'], $outcome);
                    }
                } else if ($key == "trifecta" || $key == "boxedtrifecta") {



                    foreach ($value2 as $key3 => $value3) {

                        $valRtps = array_column($value3, 'rtp');
                        $sumRtps = array_sum($valRtps);
                        $betType['rtp'] = round(($sumRtps / count($value3)) / 1000000, 2);
                        foreach ($value3 as $key4 => $value4) {
                            $outcome['description'] = $key2 . "-" . $key3 . "-" . $key4;
                            $outcome['code'] = $key2 . "-" . $key3 . "-" . $key4;
                            $outcome['odd'] = (float)$value4['odd'] / 100;
                            $outcome['probWin'] = (float)$value4['prob'];
                            array_push($betType['outcomes'], $outcome);
                        }
                    }
                } else {

                    $outcome['code'] = '' . $key2;
                    $outcome['description'] = $virtualEvent["racers"][$key2 - 1]['name'];
                    $outcome['odd'] = (float)$value2['odd'] / 100;
                    $outcome['probWin'] = (float)$value2['prob'];
                    array_push($betType['outcomes'], $outcome);
                }
            }

            $virtualEvent['betTypes'][] = $betType;
        }


        return $virtualEvent;
    }



    public static function create_event_card2(Event $event)
    {

        $ITERATORS = null;
        $channel = Channel::where('id', $event->channel_id)->first()->toArray();
        $game = Game::where('id', $channel['game_id'])->first()->toArray();




        ADMConfigs::load();
        $betCodes = ADMConfigs::getAvailableBetCodes();
        $gameCodes = ADMConfigs::getAvailableGameCodes();
        $opts = $event->getOpts();
        /*  print_r( $opts);
        echo "<hr>"; */
        $numOfRunners = count($opts['racers']);
        /* echo "runners: ".$numOfRunners;*/
        $winnerProbs = array_map(function ($x) {
            return $x['chance'];
        }, $opts['racers']);


        $markets = 'PGVirtual\\Game' . ucfirst(strtolower($game['name'])) . '\\Libraries\\Markets';
        if (!isset($ITERATORS)) {
            $ITERATORS = [
                $markets::MARKET_EXACTA => Math::getExactaPermutations($numOfRunners),
                $markets::MARKET_QUINELLA => Math::getQuinellaCombinations($numOfRunners),
                $markets::MARKET_TRIFECTA => Math::getTrifectaPermutations($numOfRunners),
                $markets::MARKET_BOXEDTRIFECTA => Math::getBoxedTrifectaCombinations($numOfRunners)
            ];
        }

        $oddsConfig = array('winnerProbs' => $winnerProbs);


        $oddsControllerClass = 'PGVirtual\\Game' . ucfirst(strtolower($game['name'])) . '\\Controllers\\OddsController';
        $oddsController = new $oddsControllerClass($oddsConfig);

        $oddsOut = [];

        foreach ($betCodes as $betCode) {

            switch ($betCode) {
                case ADMConfigs::getBetCode($markets::MARKET_WINNER):

                    $winnerOdds = $oddsController->getWinnerOdds();
                    foreach (range(1, $numOfRunners) as $runnerId) {
                        $probs = $oddsConfig['winnerProbs'][$runnerId - 1];
                        $fixedOdds = $winnerOdds[$runnerId] * 100;
                        $realRTP = $fixedOdds * $probs;
                        $oddsOut[$markets::MARKET_WINNER][$runnerId] = ['prob' => $probs, 'odd' => $fixedOdds, 'rtp' => $realRTP];
                    }
                    break;
                case ADMConfigs::getBetCode($markets::MARKET_PLACED):

                    $placedOdds = $oddsController->getPlacedOdds();
                    foreach (range(1, $numOfRunners) as $runnerId) {
                        $probs = $oddsController->placedProbs[$runnerId - 1];
                        $fixedOdds = $placedOdds[$runnerId] * 100;
                        $realRTP = $fixedOdds * $probs;
                        $oddsOut[$markets::MARKET_PLACED][$runnerId] = ['prob' => $probs, 'odd' => $fixedOdds, 'rtp' => $realRTP];
                    }
                    break;
                case ADMConfigs::getBetCode($markets::MARKET_SHOW):

                    $showProbs = $oddsController->getShowProbs();
                    $showOdds = $oddsController->getShowOdds();

                    foreach (range(1, $numOfRunners) as $runnerId) {
                        $probs = $showProbs[$runnerId - 1];
                        $fixedOdds = $showOdds[$runnerId] * 100;
                        $realRTP = $fixedOdds * $probs;
                        $oddsOut[$markets::MARKET_SHOW][$runnerId] = ['prob' => $probs, 'odd' => $fixedOdds, 'rtp' => $realRTP];
                    }
                    break;
                case ADMConfigs::getBetCode($markets::MARKET_EXACTA):

                    $exactaOdds = $oddsController->getExactaOdds();
                    foreach ($ITERATORS[$markets::MARKET_EXACTA] as [$pos_1, $pos_2]) {
                        $probs = $oddsController->exactaProbs[$pos_1][$pos_2];
                        $fixedOdds = $exactaOdds[$pos_1][$pos_2] * 100;
                        $realRTP = $fixedOdds * $probs;
                        $oddsOut[$markets::MARKET_EXACTA][$pos_1][$pos_2] = ['prob' => $probs, 'odd' => $fixedOdds, 'rtp' => $realRTP];
                    }
                    break;
                case ADMConfigs::getBetCode($markets::MARKET_QUINELLA):

                    $quinellaOdds = $oddsController->getQuinellaOdds();
                    foreach ($ITERATORS[$markets::MARKET_QUINELLA] as [$pos_1, $pos_2]) {
                        $probs = $oddsController->quinellaProbs[$pos_1][$pos_2];
                        $fixedOdds = $quinellaOdds[$pos_1][$pos_2] * 100;
                        $realRTP = $fixedOdds * $probs;
                        $oddsOut[$markets::MARKET_QUINELLA][$pos_1][$pos_2] = ['prob' => $probs, 'odd' => $fixedOdds, 'rtp' => $realRTP];
                    }
                    break;
                case ADMConfigs::getBetCode($markets::MARKET_TRIFECTA):
                    $admMarket = ADMConfigs::getMarket($markets::MARKET_WINNER);
                    $trifectaOdds = $oddsController->getTrifectaOdds();
                    foreach ($ITERATORS[$markets::MARKET_TRIFECTA] as [$pos_1, $pos_2, $pos_3]) {
                        $probs = $oddsController->trifectaProbs[$pos_1][$pos_2][$pos_3];
                        $fixedOdds = $trifectaOdds[$pos_1][$pos_2][$pos_3] * 100;
                        $realRTP = $fixedOdds * $probs;
                        $oddsOut[$markets::MARKET_TRIFECTA][$pos_1][$pos_2][$pos_3] = ['prob' => $probs, 'odd' => $fixedOdds, 'rtp' => $realRTP];
                    }
                    break;
                case ADMConfigs::getBetCode($markets::MARKET_BOXEDTRIFECTA):

                    $boxedtrifectaOdds = $oddsController->getBoxedtrifectaOdds();
                    foreach ($ITERATORS[$markets::MARKET_BOXEDTRIFECTA] as [$pos_1, $pos_2, $pos_3]) {
                        $probs = $oddsController->boxedtrifectaProbs[$pos_1][$pos_2][$pos_3];

                        $fixedOdds = $boxedtrifectaOdds[$pos_1][$pos_2][$pos_3] * 100;
                        $realRTP = $fixedOdds * $probs;
                        $oddsOut[$markets::MARKET_BOXEDTRIFECTA][$pos_1][$pos_2][$pos_3] = ['prob' => $probs, 'odd' => $fixedOdds, 'rtp' => $realRTP];
                    }
                    break;
                case ADMConfigs::getBetCode($markets::MARKET_EVENODD):

                    $evenProbs = array_sum(array_map(function ($i) use ($oddsConfig) {
                        return $oddsConfig['winnerProbs'][$i];
                    }, range(1, $numOfRunners - 1, 2)));
                    $oddProbs = array_sum(array_map(function ($i) use ($oddsConfig) {
                        return $oddsConfig['winnerProbs'][$i];
                    }, range(0, $numOfRunners - 1, 2)));
                    $evenoddOdds = [];
                    foreach ($oddsController->getEvenoddOdds() as $key => $value) {
                        $evenoddOdds[$key] = $value * 100;
                    }

                    $fixedEvenOdds = $evenoddOdds['even'];
                    $fixedOddOdds = $evenoddOdds['odd'];
                    $realEvenRTP = $fixedEvenOdds * $evenProbs;
                    $realOddRTP = $fixedOddOdds * $oddProbs;
                    $oddsOut[$markets::MARKET_EVENODD][1] = ['prob' => $evenProbs, 'odd' => $fixedEvenOdds, 'rtp' => $realEvenRTP];
                    $oddsOut[$markets::MARKET_EVENODD][2] = ['prob' => $oddProbs, 'odd' => $fixedOddOdds, 'rtp' => $realOddRTP];

                    break;
                case ADMConfigs::getBetCode($markets::MARKET_UNDEROVER . $numOfRunners):
                    $admMarket = ADMConfigs::getMarket($markets::MARKET_WINNER);
                    $underProbs = array_sum(array_map(function ($i) use ($oddsConfig) {
                        return $oddsConfig['winnerProbs'][$i];
                    }, range(0, ($numOfRunners / 2) - 1)));
                    $overProbs = array_sum(array_map(function ($i) use ($oddsConfig) {
                        return $oddsConfig['winnerProbs'][$i];
                    }, range(($numOfRunners / 2), $numOfRunners - 1)));
                    $underoverOdds = [];
                    foreach ($oddsController->getUnderOverOdds() as $key => $value) {
                        $underoverOdds[$key] = $value * 100;
                    }
                    $fixedUnderOdds = $underoverOdds['under'];
                    $fixedOverOdds = $underoverOdds['over'];
                    $realUnderRTP = $fixedUnderOdds * $underProbs;
                    $realOverRTP = $fixedOverOdds * $overProbs;
                    $oddsOut[$markets::MARKET_UNDEROVER . $numOfRunners][1] = ['prob' => $underProbs, 'odd' => $fixedUnderOdds, 'rtp' => $realUnderRTP];
                    $oddsOut[$markets::MARKET_UNDEROVER . $numOfRunners][2] = ['prob' => $overProbs, 'odd' => $fixedOverOdds, 'rtp' => $realOverRTP];

                    break;
            }
        }

        //echo json_encode($oddsOut);

        $virtualEvent = self::createVirtualEvent2(
            ["event" => [
                "Id" => $event->id,
                "pal_id" => $event->int_pal_id,
                "int_event_id" => $event->int_event_id,
                "status" => $event->status,
                "time" => Carbon::create($event->time)->format('Y-m-d\TH:i:s\Z')
            ], "game" => $game, "channel" => $channel,  "opts" => $opts, "betCodes" => $betCodes, "gameCodes" => $gameCodes],
            ["current" => ["racers" => $opts['racers'], "odds" => $oddsOut]]
        );

        return $virtualEvent;
    }



    public static function sendEventsCard($events, $controllerId)
    {
        $requestData = self::makeRequest($controllerId);
        print_r($requestData);
        try {


            $requestData['events'] = $events;
            $requestData['events'] = $requestData['events'];


            $client = new \GuzzleHttp\Client();
            $url = config('constants.URL_API_NOTIFY_WHIST');
            //Log::info(json_encode($requestData, JSON_PRETTY_PRINT));

            //echo json_encode($events);



            $endpoint = Configuration::where('key', 'ISIBET_ENDPOINT')->first()->getValue();


            $response = $client->post(
                $endpoint . "/EventsCard",
                [
                    \GuzzleHttp\RequestOptions::JSON => $requestData
                ]
            );


            echo date('Y-m-d H:i:s') . " - Inviati " . count($events["virtualEvents"]) . " eventi" . PHP_EOL;

            /* Log */
            //echo json_encode(['jsondata'=>$requestData,"response"=>$response->getBody()]);
            Log::channel('daily')->info(Carbon::now()->toIso8601String() . " EVENTS: " . PHP_EOL);
            Log::channel('daily')->info(json_encode(['jsondata' => $requestData, "response" => $response->getBody()]));
            Log::channel('daily')->info("------------- " . PHP_EOL);
            return $requestData;
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            echo json_encode(['jsondata' => $requestData, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
