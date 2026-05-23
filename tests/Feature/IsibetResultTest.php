<?php

namespace Tests\Feature;

use App\Libraries\ADMConfigs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PGVirtual\Core\Models\Event;
use PGVirtual\GameDogs\Libraries\Markets;
use Tests\TestCase;

class IsibetResultTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {
        $eventToTest = 20665;
        $event = Event::find($eventToTest); // get event
        $requestData = [];
        $requestData['eventId']=$event->id;
        $adminConfig = ADMConfigs::load();
        $betCodes = ADMConfigs::getAvailableBetCodes();
        $gameCodes = ADMConfigs::getAvailableGameCodes();
        $requestData['bets'] = [];
      
        $toolResult=[[3],[3,1],[3,1,2],[3,1],[3,1],[3,1,2],[3,1,2],[2],[1]];
        $isibetResult = [];
        $probs=[];
        
         $eventOpts = json_decode($event->opts, true);
         foreach($eventOpts['racers'] as $key => $racer) {
                array_push($probs, $racer['chance']); 
                echo $racer['chance']."\n";
         }
         print_r($eventOpts['rns']);

            foreach (Markets::MARKETS as $key => $market) {
                if ($market == MARKETS::MARKET_WINNER) {
                    $result = array($eventOpts['arrivalOrder'][0]);
                    array_push($isibetResult, array($eventOpts['arrivalOrder'][0]));
                }
                elseif ($market == MARKETS::MARKET_PLACED) {
                    $result = array($eventOpts['arrivalOrder'][0],$eventOpts['arrivalOrder'][1]);
                    array_push($isibetResult, array($eventOpts['arrivalOrder'][0],$eventOpts['arrivalOrder'][1]));
                }
                elseif ($market == MARKETS::MARKET_SHOW) {
                    $result = array($eventOpts['arrivalOrder'][0],$eventOpts['arrivalOrder'][1],$eventOpts['arrivalOrder'][2]);
                    array_push($isibetResult, array($eventOpts['arrivalOrder'][0],$eventOpts['arrivalOrder'][1],$eventOpts['arrivalOrder'][2]));
                }
                elseif ($market == MARKETS::MARKET_EXACTA) {
                    $result = array($eventOpts['arrivalOrder'][0],$eventOpts['arrivalOrder'][1]);
                    array_push($isibetResult, array($eventOpts['arrivalOrder'][0],$eventOpts['arrivalOrder'][1]));
                }
                elseif ($market == MARKETS::MARKET_QUINELLA) {
                    $result = array($eventOpts['arrivalOrder'][0],$eventOpts['arrivalOrder'][1]);
                    array_push($isibetResult,array($eventOpts['arrivalOrder'][0],$eventOpts['arrivalOrder'][1])); 
                }
                elseif ($market == MARKETS::MARKET_TRIFECTA) {
                    $result = array($eventOpts['arrivalOrder'][0],$eventOpts['arrivalOrder'][1],$eventOpts['arrivalOrder'][2]);
                    array_push($isibetResult, array($eventOpts['arrivalOrder'][0],$eventOpts['arrivalOrder'][1],$eventOpts['arrivalOrder'][2]));
                }
                elseif ($market == MARKETS::MARKET_BOXEDTRIFECTA) {
                    $result = array($eventOpts['arrivalOrder'][0],$eventOpts['arrivalOrder'][1],$eventOpts['arrivalOrder'][2]);
        array_push($isibetResult,array($eventOpts['arrivalOrder'][0],$eventOpts['arrivalOrder'][1],$eventOpts['arrivalOrder'][2]));
                }
                elseif ($market == MARKETS::MARKET_EVENODD) {
                    $result = array(intval($eventOpts['arrivalOrder'][0]) % 2 == 0 ? 1 : 2);
                    array_push($isibetResult, array(intval($eventOpts['arrivalOrder'][0]) % 2 == 0 ? 1 : 2));
                }
                elseif ($market == MARKETS::MARKET_UNDEROVER) {
                    $resultIsUnder = (intval($eventOpts['arrivalOrder'][0]) <= count($eventOpts["arrivalOrder"]) / 2);
                    $result = array( $resultIsUnder ? 1 : 2);
                    array_push($isibetResult, array($resultIsUnder ? 1 : 2));
                }
                $market = $market=='underover' ? $market.count($eventOpts['racers']) : $market;
                $betCode = ADMConfigs::getBetCode($market);
                
                array_push($requestData['bets'],[
                    'code' => $betCode,'outcomes' => $result
                ]);
            
                
            }
            $valid = true;
            for($i=0;$i<count($toolResult);$i++){
                $difference = array_diff($toolResult[$i],$isibetResult[$i]);
                print_r($difference);
                if(!empty($difference)){
                    $valid = false;
                }
            }

        if($valid) $this->assertTrue(true);
            else $this->assertTrue(false);
           
           
        
    }
}
