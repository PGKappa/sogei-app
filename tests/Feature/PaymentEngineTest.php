<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Helmich\JsonAssert\JsonAssertions;
use Tests\TestCase;
//use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Contracts\Console\Kernel;
use PGVirtual\Core\Models\User;
use Illuminate\Testing\TestResponse;
use PGVirtual\GameDogs\Libraries\ProbConfigs;
use PGVirtual\Core\Controllers\TicketController;
//use phpmock\MockBuilder;
use PGVirtual\GameDogs\Libraries\Probabilities;
use PGVirtual\Core\Models\Event;
use PGVirtual\GameDogs\Controllers\EventController;
use Mockery;
use App;
use Illuminate\Support\Facades\Auth;
use PGVirtual\Core\Models\Ticket;
use PGVirtual\Core\Models\Error as ErrorModel;
use PGVirtual\Core\Models\Game;
use DateTime;

require_once 'vendor/pgvirtual/game-dogs/src/Libraries/ProbConfigs.php';
require_once 'vendor/pgvirtual/game-dogs/src/Libraries/Probabilities.php';

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */

class PaymentEngineTest extends TestCase
{
    
    use JsonAssertions, WithoutMiddleware;

    protected static $DEBUG = false;
    protected static $NUM_PALIMPSESTS = 1;
    protected static $NUM_EVENTS_PER_PALIMPSEST = 100;
    protected static $NUM_TICKETS = 14400000;

    /*



    */

    protected static $events;
    protected static $eventOdds = [];
    protected static $selections;


    protected static $PALIMPSEST_ID_OFFSET = 1000000000;
    protected static $EVENT_ID_OFFSET = 1;

    protected static $palimpsestIds = [];
    protected static $eventIds = [];

    protected static function resetSelections()
    {
        self::$selections = [
            "winner" => [1, 2, 3, 4, 5, 6],
            "placed" => [1, 2, 3, 4, 5, 6],
            "show" => [1, 2, 3, 4, 5, 6],
            "trifecta" => [],
            "boxedtrifecta" => [],
        ];
        for ($i = 1;$i <= 6; $i++) {
            for ($k = 1;$k <= 6; $k++) {
                for ($j = 1;$j <= 6; $j++) {
                    if (($i != $k) && ($i != $j) && ($k != $j)) {
                        array_push(self::$selections['trifecta'],implode('-',[$i,$k,$j]));
                    }
                }
            }
        }
        for ($i = 1;$i <= 6; $i++) {
            for ($k = 1;$k <= 6; $k++) {
                for ($j = 1;$j <= 6; $j++) {
                    if (($i != $k) && ($i != $j) && ($k != $j)) {
                        if (($i < $k) && ($k < $j)) {
                            array_push(self::$selections['boxedtrifecta'],implode('-',[$i,$k,$j]));
                        }
                    }
                }
            }
        }
        //print_r(self::$selections);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::resetSelections();
    }

    private function setupMyThings()
    {
        \Mockery::getConfiguration()->setConstantsMap([
            'PGVirtual\Core\Models\Event' => [
                'STATUS_GENERATED_ARRIVAL' => '2'
            ]
        ]);

        $mock2 = \Mockery::mock('overload:PGVirtual\Core\Models\Event');
        $mock2->shouldReceive('where')
            ->withArgs(function ($argument, $value) {
                echo "argument: $argument, value: $value";
                $this->${$argument} = $value;
            })
            ->andReturnSelf();

        $mock2->shouldReceive('first')
            ->withArgs(function () {
                $palId = $this->int_pal_id;
                $eventId = $this->int_event_id;
                return self::$events;
            })
            ->andReturn(1);


        $mock3 = \Mockery::mock('overload:PGVirtual\Core\Models\Game');

        $mock3->shouldReceive('where')
            ->andReturnSelf();

        $mock3->shouldReceive('first')
            ->andReturn([
                "racers" => 6
            ]);
    }

    private function getEventOdds($event)
    {
        $currentEventOpts = $event->getOpts();

        $oddsConfig['winnerProbs'] = array_map(function ($x) {
            return $x['chance'];
        }, $currentEventOpts['racers']);

        $palId = $event->int_pal_id;
        $eventId = $event->int_event_id;

        $oddsController = new \PGVirtual\GameDogs\Controllers\OddsController($oddsConfig);
        foreach (\PGVirtual\GameDogs\Controllers\OddsController::MARKETS as $market) {
            $exp[$market] = $oddsController->{'get' . ucfirst($market) . 'Odds'}();
        }

        //self::$eventOdds[$palId] = self::$eventOdds[$palId] ?? [];
        //self::$eventOdds[$palId][$eventId] = self::$eventOdds[$palId][$eventId] ?? $exp;

        return $exp;
    }


    private function getPlayableEvents($opts)
    {
        //$num_concrete_events = count($opts['eventIds']) * count($opts['palimpsestIds'] );
        $events = [];

        //$j = 0;
        foreach ($opts['palimpsestIds'] as $palimpsestId) {
            //$i = 0;
            foreach ($opts['eventIds'] as $eventId) {
                array_push($events, [
                    "palimpsestId" => $palimpsestId, //self::$PALIMPSEST_ID_OFFSET + $j,
                    "eventId" => $eventId, //self::$EVENT_ID_OFFSET + $i,
                    "isBanker" => false,
                    "markets" => $this->getMarkets(array_merge($opts, [
                        "palimpsestId" => $palimpsestId,
                        "eventId" => $eventId
                    ]))
                ]);
                //$i++;
            }
            //$j++;
        }

        return $events;
    }

    //['markets'], $opts['numSelectionsPerMarket']

    private function getMarkets($opts)
    {
        if ($opts['selectMaxOddsOnly']) {
            //print_r(self::$eventOdds);
            return array_map(function ($marketId) use ($opts) {
                //shuffle(self::$selections[$marketId]);
                $marketSelections = self::$selections[$marketId];
                $currentOdds = self::$eventOdds[$opts['palimpsestId']][$opts['eventId']][$marketId];

                return [
                    "description" => $marketId,
                    "selections" => [
                        [
                            "description" => $marketSelections[array_search(
                                max($currentOdds),
                                $currentOdds
                            ) - 1]
                        ]
                    ]
                ];
            }, $opts['markets']);
        } elseif ($opts['selectMinOddsOnly']) {
            return array_map(function ($marketId) use ($opts) {
                //shuffle(self::$selections[$marketId]);
                $marketSelections = self::$selections[$marketId];
                $currentOdds = self::$eventOdds[$opts['palimpsestId']][$opts['eventId']][$marketId];
                return [
                    "description" => $marketId,
                    "selections" => [
                        [
                            "description" => $marketSelections[array_search(min($currentOdds), $currentOdds) - 1]
                        ]
                    ]
                ];
            }, $opts['markets']);
        }
        return array_map(function ($marketId) use ($opts) {
            $num_selections = min($opts['numSelectionsPerMarket'], count(self::$selections[$marketId]));
            shuffle(self::$selections[$marketId]);
            return [
                "description" => $marketId,
                "selections" => array_map(function ($i) use ($marketId) {
                    return [
                        "description" => self::$selections[$marketId][$i]
                    ];
                }, range(0, $num_selections - 1))
            ];
        }, $opts['markets']);
    }

    public function generateTicketMockup($opts)
    {
        return [
            "placeBet" => [
                "currency" => "EUR",
                "system" => $opts['system'],
                "selections" => array_map(function ($eventNode) {
                    return [
                        "palimpsestId" => $eventNode['palimpsestId'],
                        "eventId" => $eventNode['eventId'],
                        "isBanker" => $eventNode['isBanker'],
                        "markets" => $eventNode['markets']
                    ];
                }, $this->getPlayableEvents($opts))
            ]
        ];
    }

    public function generateProbabilitiesMockup()
    {
        $dogs6 = 1;
        $HORSE6 = 2;
        $numRacers = 6;
        $rngConfig = new \PGVirtual\GameDogs\Libraries\ProbConfigs($numRacers);
        return new Probabilities($rngConfig);
    }

    public function generateResult($event)
    {
        $universe = array();
        $opts = json_decode($event->opts);
        foreach ($opts->racers as $racer) {
            for ($k = 0; $k < $racer->chance; $k++) {
                array_push($universe, $racer->number);
            }
        }

        for ($i = 0; $i < count($opts->racers); $i++) {
            $randomRacerNumber = $universe[array_rand($universe)];
            foreach (array_keys($universe, $randomRacerNumber) as $key) {
                unset($universe[$key]);
            }
            $arrivalOrder[$i] = $randomRacerNumber;
        }
        $opts->arrivalOrder = $arrivalOrder;
        $event->opts = json_encode($opts);
        $event->status = Event::STATUS_GENERATED_ARRIVAL;
        return $event;
    }

    private function ticketCreate($rawTicket)
    {
        $cleanCount = 0;
        $bankerCount = 0;

        $ticketEventSelections = $rawTicket['placeBet']['selections'];
        $ticketHasSelections = !empty($ticketEventSelections);
        $ticketHasAmounts = !empty($rawTicket['placeBet']['system']);

        $exp = [];
        if ($ticketHasSelections && $ticketHasAmounts) {
            foreach ($ticketEventSelections as $selection) {
                $selectionHashId = $selection['palimpsestId'] . '-' . $selection['eventId'];
                $exp[$selectionHashId]['selections'] = array();
                $isBanker = isset($selection['isBanker']) && ($selection['isBanker'] == 'true');
                if ($isBanker) {
                    $bankerCount++;
                } else {
                    $cleanCount++;
                }

                $exp[$selectionHashId]['isBanker'] = $isBanker; //$selection['isBanker']??false;

                foreach ($selection['markets'] as $market) {
                    foreach ($market['selections'] as $marketSelection) {
                        array_push($exp[$selectionHashId]['selections'], [
                            'market' => $market['description'],
                            'selection' => $marketSelection['description']
                        ]);
                    }
                }
            }


            $systems = array_keys($rawTicket['placeBet']['system']);
            // $systems sono i grps
            if ((min($systems) > 0) && (min($systems) >= $bankerCount)) {
                $cleanSelections = array();
                $bankerSelections = array();

                $defCombinations = [];
                $i = 0;
                $b = 0;
                $user = User::where('id', Auth::id())->first();
                foreach ($exp as $selectionHashId => $evValue) {
                    $palEventExplode = explode('-', $selectionHashId);
                    $palimpsestId = $palEventExplode[0];
                    $eventId = $palEventExplode[1];

                    $eventOdds = $this->getEventOdds(self::$events[$palimpsestId][$eventId]);
                    //print_r($eventOdds);
                    
                    if (self::$DEBUG) {
                        print_r("\nODDS: winner\n\n");
                        print_r($eventOdds['winner']);
                        print_r("Overround Winner" . array_sum(array_map(function ($el) {
                            return 100 / $el;
                        }, $eventOdds['winner'])));
                        print_r("\n\n\n");
                        print_r("\nODDS: placed\n\n");
                        print_r($eventOdds['placed']);
                        print_r("Overround placed" . array_sum(array_map(function ($el) {
                            return 100 / $el;
                        }, $eventOdds['placed'])));
                        print_r("\n\n\n");
                        print_r("\nODDS: show\n\n");
                        print_r($eventOdds['show']);
                        print_r("Overround show" . array_sum(array_map(function ($el) {
                            return 100 / $el;
                        }, $eventOdds['show'])));
                    }
                    
                    foreach ($evValue['selections'] as $selection) {
                        
                            if ($selection['market'] == 'trifecta') {
                                
                                $selectionExplode = explode('-',$selection['selection']);
                                //sort($selectionExplode);
                                if (isset($eventOdds[strtolower($selection['market'])][$selectionExplode[0]][$selectionExplode[1]][$selectionExplode[2]])) {
                                    $temp['odds'] = $eventOdds[strtolower($selection['market'])][$selectionExplode[0]][$selectionExplode[1]][$selectionExplode[2]];
                                } else {
                                    //echo strtolower($selection['market']) . " " . $selection['selection'];
                                    return ErrorModel::response(ErrorModel::$ODDS_NOT_FOUND);
                                }
                            }
                            elseif ($selection['market'] == 'boxedtrifecta') {
                                $selectionExplode = explode('-',$selection['selection']);
                                
                                if (isset($eventOdds[strtolower($selection['market'])][$selectionExplode[0]][$selectionExplode[1]][$selectionExplode[2]])) {
                                    $temp['odds'] = $eventOdds[strtolower($selection['market'])][$selectionExplode[0]][$selectionExplode[1]][$selectionExplode[2]];
                                } else {
                                    //echo strtolower($selection['market']) . " " . $selection['selection'];
                                    return ErrorModel::response(ErrorModel::$ODDS_NOT_FOUND);
                                }
                            }
                            else {
                                if (isset($eventOdds[strtolower($selection['market'])][$selection['selection']])) {
                                    $temp['odds'] = $eventOdds[strtolower($selection['market'])][$selection['selection']];
                                } else {
                                    //echo strtolower($selection['market']) . " " . $selection['selection'];
                                    return ErrorModel::response(ErrorModel::$ODDS_NOT_FOUND);
                                }
                            }

                        $temp['palimpsestId'] = $palimpsestId;
                        $temp['eventId'] = $eventId;
                        $temp['market'] = $selection['market'];
                        $temp['selection'] = $selection['selection'];

                        $isBanker = isset($evValue['isBanker']) && ($evValue['isBanker'] == 'true');
                        if ($isBanker) {
                            $temp['isBanker'] = 'true';
                            if (empty($bankerSelections[$b])) {
                                // non si può fare solo push?
                                $bankerSelections[$b] = [];
                            }
                            array_push($bankerSelections[$b], $temp);
                        } else {
                            // clean selections -> NOT BANKER
                            $temp['isBanker'] = 'false';
                            if (empty($cleanSelections[$i])) {
                                // non si può fare solo push?
                                $cleanSelections[$i] = [];
                            }
                            array_push($cleanSelections[$i], $temp);
                        }
                    }

                    if ($isBanker) {
                        $b++;
                    } else {
                        $i++;
                    }
                    // } else {
                    //     return ErrorModel::response(ErrorModel::$EVENT_NOT_FOUND);
                    // }
                }
            } else {
                return ErrorModel::response(ErrorModel::$SYSTEM_VARIABLE_ERROR);
            }
            $bankerSystemCombinations = array();
            $cleanSystemCombinations = array();
            $amount = 0;
            foreach ($rawTicket['placeBet']['system'] as $system => $value) {
                //echo 'system: '.$system."\n";
                if ($bankerCount <= $system) { ///search by keys
                    //$bankerCombsCount = $system - $cleanCount;
                    //echo '-bankerCount--------- '.$bankerCount."\n";
                    //echo '-system--------- '.$system."\n";
                    //echo '--------------------- '."\n";
                    $bankerSystemCombinations = Ticket::getSystemCombinations($bankerSelections, $bankerCount);
                    //print_r($bankerSystemCombinations);
                    //echo "b: ".$system."\n";
                }
                if ($bankerCount == $system) {
                    ////// $defCombinations = array();
                    $defCombinations[count($bankerSystemCombinations)] = $bankerSystemCombinations;
                }
                $amount += $value;
            }

            foreach ($rawTicket['placeBet']['system'] as $system => $value) {
                // per ogni grp
                //echo 'system: '.$system.' value: '.$value."\n";
                $cleanCombsCount = $system - $bankerCount;
                $cleanSystemCombinations = array_merge($cleanSystemCombinations, Ticket::getSystemCombinations($cleanSelections, $cleanCombsCount));
            }
            if (count($cleanSystemCombinations) > 0) {
                foreach ($cleanSystemCombinations as $combination) {
                    if (!empty($bankerSystemCombinations)) {
                        foreach ($bankerSystemCombinations as $bankerCombination) {
                            $newCombination = array_merge($bankerCombination, $combination);
                            $defCombinations[count($newCombination)][] = $newCombination;
                        }
                    } else {
                        $defCombinations[count($combination)][] = $combination;
                    }
                }
            } else {
                $defCombinations = $bankerSystemCombinations;
            }
            $minStakePerComb = 5;
            //Configuration::select('value')->where('key', 'STAKE_MIN')->where('operator_id', $user->operator_id)->where('currency_id', $user->currency_id)->first()->getValue();
            foreach ($defCombinations as $systemLengthKey => $systemLengthValue) {
                foreach ($systemLengthValue as $combinationKey => $combinationValue) {
                    $stakePerComb = number_format($rawTicket['placeBet']['system'][$systemLengthKey] / count($defCombinations[$systemLengthKey]), 4, '.', '');
                    if ($minStakePerComb <= $stakePerComb) {
                        $defCombinations[$systemLengthKey][$combinationKey]['stakePerComb'] = $stakePerComb;
                        $defCombinations[$systemLengthKey][$combinationKey]['totalOdds'] = 1;
                        $defCombinations[$systemLengthKey][$combinationKey]['potentialWin'] = 1;
                        foreach ($combinationValue as $selectionKey => $selectionValue) {
                            $defCombinations[$systemLengthKey][$combinationKey]['combination'][] = $selectionValue;
                            $defCombinations[$systemLengthKey][$combinationKey]['potentialWin'] *= $selectionValue['odds'];
                            $defCombinations[$systemLengthKey][$combinationKey]['totalOdds'] *= $selectionValue['odds'];
                            unset($defCombinations[$systemLengthKey][$combinationKey][$selectionKey]);
                        }
                        $defCombinations[$systemLengthKey][$combinationKey]['potentialWin'] = number_format($defCombinations[$systemLengthKey][$combinationKey]['stakePerComb'] * $defCombinations[$systemLengthKey][$combinationKey]['potentialWin'], 2, '.', '');
                        $defCombinations[$systemLengthKey][$combinationKey]['totalOdds'] = number_format($defCombinations[$systemLengthKey][$combinationKey]['totalOdds'], 2, '.', '');
                    } else {
                        return json_encode([$minStakePerComb, $stakePerComb]);
                        //return ErrorModel::response(ErrorModel::$MIN_STAKE_NOT_REACHED);
                    }
                }
            }
        } else {
            return ErrorModel::response(ErrorModel::$MALFORMED_TICKET);
        }
        $ticketBody['ticketinfo'] = $defCombinations;
        $ticketBody['systems'] = $rawTicket['placeBet']['system'];
        //print_r($defCombinations);
        $ticket = new Ticket();
        $ticket->user_id = Auth::id();
        $ticket->time = new DateTime();
        $ticket->timezone = 'Europe/Rome';
        //$ticket->currency_id = $user->currency_id;
        $ticket->amount = $amount;
        $ticket->amount_won = 0;
        $ticket->amount_refund = 0;
        $ticket->ticketbody = json_encode($ticketBody);
        $ticket->eventlist = json_encode(array_keys($exp));
        $ticket->status = Ticket::STATUS_CREATED;
        return $ticket;
    }

    public function dogSelectionIsWinning($event, $market, $selection)
    {
        $market = strtolower($market);
        $eventOpts = $event->getOpts();
        if (!empty($eventOpts['arrivalOrder'])) {
            if (in_array($market, \PGVirtual\GameDogs\Controllers\TicketController::MARKETS)) {
                if ($market == \PGVirtual\GameDogs\Controllers\TicketController::MARKET_WINNER) {
                    if ($eventOpts['arrivalOrder'][0] == $selection) {
                        return true;
                    } else {
                        return false;
                    }
                } elseif ($market == \PGVirtual\GameDogs\Controllers\TicketController::MARKET_PLACED) {
                    if (($eventOpts['arrivalOrder'][0] == $selection) || ($eventOpts['arrivalOrder'][1] == $selection)) {
                        return true;
                    } else {
                        return false;
                    }
                } elseif ($market == \PGVirtual\GameDogs\Controllers\TicketController::MARKET_SHOW) {
                    if (($eventOpts['arrivalOrder'][0] == $selection) || ($eventOpts['arrivalOrder'][1] == $selection) || ($eventOpts['arrivalOrder'][2] == $selection)) {
                        return true;
                    } else {
                        return false;
                    }
                } elseif ($market == \PGVirtual\GameDogs\Controllers\TicketController::MARKET_EXACTA) {
                    $exSel = explode('-', $selection);
                    if (($eventOpts['arrivalOrder'][0] == $exSel[0]) && ($eventOpts['arrivalOrder'][1] == $exSel[1])) {
                        return true;
                    } else {
                        return false;
                    }
                } elseif ($market == \PGVirtual\GameDogs\Controllers\TicketController::MARKET_QUINELLA) {
                    $exSel = explode('-', $selection);
                    if ((($eventOpts['arrivalOrder'][0] == $exSel[0]) && ($eventOpts['arrivalOrder'][1] == $exSel[1])) || (($eventOpts['arrivalOrder'][0] == $exSel[1]) && ($eventOpts['arrivalOrder'][1] == $exSel[0]))) {
                        return true;
                    } else {
                        return false;
                    }
                } elseif ($market == \PGVirtual\GameDogs\Controllers\TicketController::MARKET_TRIFECTA) {
                    $exSel = explode('-', $selection);
                    if (($eventOpts['arrivalOrder'][0] == $exSel[0]) && ($eventOpts['arrivalOrder'][1] == $exSel[1]) && ($eventOpts['arrivalOrder'][2] == $exSel[2])) {
                        return true;
                    } else {
                        return false;
                    }
                }  elseif ($market == \PGVirtual\GameDogs\Controllers\TicketController::MARKET_BOXEDTRIFECTA) {
                    $exSel = explode('-', $selection);
                    $i = 0;
                    foreach($exSel as $key => $value) {
                        if (($value == $eventOpts['arrivalOrder'][0]) || ($value == $eventOpts['arrivalOrder'][1]) || ($value == $eventOpts['arrivalOrder'][2])) {
                            $i++;
                        }
                    }
                    return  ($i == 3);
                }
                elseif ($market == \PGVirtual\GameDogs\Controllers\TicketController::MARKET_EVENODD) {
                    if (($eventOpts['arrivalOrder'][0] % 2 == 0) && ($selection == 'even')) {
                        return true;
                    } elseif (($eventOpts['arrivalOrder'][0] % 2 != 0) && ($selection == 'odd')) {
                        return true;
                    } else {
                        return false;
                    }
                } elseif ($market == \PGVirtual\GameDogs\Controllers\TicketController::MARKET_UNDEROVER) {
                    if (($eventOpts['arrivalOrder'][0] > 0) && ($eventOpts['arrivalOrder'][0] <= 3) && ($selection == 'under')) {
                        return true;
                    } elseif (($eventOpts['arrivalOrder'][0] > 3) && ($eventOpts['arrivalOrder'][0] <= 6) && ($selection == 'over')) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function ticketIsWinning($event, $market, $selection)
    {
        return $this->dogSelectionIsWinning($event, $market, $selection);
    }


    public function ticketWinCalculation($ticket)
    {
        $ticketBody = json_decode($ticket->ticketbody, 1);
        $win = 0;
        $eventCache = [];

        foreach ($ticketBody['ticketinfo'] as &$ticketInfo) {
            foreach ($ticketInfo as $grp => &$combination) {
                $winStatus = true;
                $foundVoidEvent = false;
                foreach ($combination['combination'] as &$combinationItem) {
                    $palimpsestId = $combinationItem['palimpsestId'];
                    $eventId = $combinationItem['eventId'];

                    $eventCache[$palimpsestId][$eventId] = $eventCache[$palimpsestId][$eventId]
                        ?? self::$events[$palimpsestId][$eventId];
                    $event = $eventCache[$palimpsestId][$eventId];

                    if ($event->status === Event::STATUS_VOID) {
                        $foundVoidEvent = true;
                        $combinationItem['isVoid'] = true;
                        if (empty($combinationItem['originalOdds'])) {
                            $combinationItem['originalOdds'] = $combinationItem['odds'];
                        }
                        $combinationItem['odds'] = 1;
                    } else {
                        if (!$this->ticketIsWinning($event, $combinationItem['market'], $combinationItem['selection'])) {
                            // tutte i combinationItem devono essere vincenti per avere una vincita per quella combinazione di lunghezza grp.
                            $winStatus = false;
                            break;
                        }
                    }
                }

                if ($foundVoidEvent) {
                    if (empty($combination['originalTotalOdds'])) {
                        $combination['originalTotalOdds'] = $combination['totalOdds'];
                    }
                    if (empty($combination['originalPotentialWin'])) {
                        $combination['originalPotentialWin'] = $combination['potentialWin'];
                    }

                    $combination['totalOdds'] = array_product(array_map(function ($combinationItem) {
                        return $combinationItem['odds'];
                    }, $combination['combination']));

                    $combination['potentialWin'] = $combination['stakePerComb'] * $combination['totalOdds'];
                }

                // questa somma nel win dev'essere sempre successiva al recalcolo della vincita post Evento Void
                if ($winStatus) {
                    $win += $combination['potentialWin'];
                    /*echo "\n------------------------------------------------\n";
                    echo "EVENT:\n";
                    print_r($event['opts']);
                    echo "\n---------------\n";
                    echo "combinationItem['market']:\n";
                    print_r($combinationItem['market']);
                    echo "\n----------------\n";
                    echo "combinationItem['selection']:\n";
                    print_r($combinationItem['selection']);
                    echo "\n----------------\n";
                    echo "Win:\n";
                    print_r($combination['potentialWin']/ 100 ) ;
                    echo "\n------------------------------------------------\n";*/
                }
            }
        }
        return $win;
    }
    /**
     * Test default template name
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testWinnerMarket() //$event, $gameid
    {
        //$this->expectOutputString('');

        $totalWinAmount = 0;
        $totalStakeAmount = 0;




        $rng = $this->generateProbabilitiesMockup();

        self::$events = [];
        $eventCount = 0;
        for ($j = 0; $j < self::$NUM_PALIMPSESTS; $j++) {
            $palId = self::$PALIMPSEST_ID_OFFSET + $j;
            array_push(self::$palimpsestIds, $palId);
            for ($i = 0; $i < self::$NUM_EVENTS_PER_PALIMPSEST; $i++) {
                $probs = $rng->generate();
                if (self::$DEBUG) {
                    print_r($probs);
                }

                $event = new Event();
                $event->int_pal_id = $palId;
                $event->int_event_id = self::$EVENT_ID_OFFSET + $i;

                $racerNumber = 1;
                $event->opts = json_encode([
                    "racers" => array_map(function ($prob) use (&$racerNumber) {
                        return [
                            "chance" => $prob,
                            "number" => $racerNumber++,
                        ];
                    }, $probs)
                ], 1);
                //$eventWithResults = EventController::generateResult($event);
                $eventWithResults = $this->generateResult($event);

                /*
                if (self::$DEBUG) {
                    var_dump(json_decode($eventWithResults));
                    //var_dump($eventWithResults->opts);
                }
                */

                //array_push($events, $eventWithResults);
                $eventId = $event->int_event_id;
                self::$events[$event->int_pal_id] = self::$events[$event->int_pal_id] ?? [];
                self::$events[$event->int_pal_id][$eventId] = self::$events[$event->int_pal_id][$eventId] ?? $eventWithResults;


                $currentEventOpts = self::$events[$event->int_pal_id][$eventId]->getOpts();

                $oddsConfig['winnerProbs'] = array_map(function ($x) {
                    return $x['chance'];
                }, $currentEventOpts['racers']);

                self::$eventOdds[$palId] = self::$eventOdds[$palId] ?? [];
                self::$eventOdds[$palId][$eventId] = self::$eventOdds[$palId][$eventId] ?? $this->getEventOdds($event);


                if (self::$DEBUG) {
                    print_r(json_decode($eventWithResults->opts)->arrivalOrder);
                }
                array_push(self::$eventIds, $event->int_event_id);

                // if (($eventCount % 2) == 0) {
                //     //echo "\nN event created: " . $eventCount . "\n";
                //     fwrite(STDOUT, print_r("\nN event created: " . $eventCount . "\n", true));
                // }
                $eventCount++;
                //
            }
            echo "\nN Events created: " . $eventCount . "\n";
        }


        $wonAmounts = [];
        for ($i = 0; $i < self::$NUM_TICKETS; $i++) {
            shuffle(self::$palimpsestIds);
            shuffle(self::$eventIds);

            //$concreteEventIdsForThisTicket = array_slice(self::$eventIds, 0, rand(1, count(self::$eventIds)));
            //$concreteEventIdsForThisTicket = array_slice(self::$eventIds, 0, rand(1, 5));
            $concreteEventIdsForThisTicket = array_slice(self::$eventIds, 0, 2);
            $numConcreteEventsForThisTicket = count(self::$palimpsestIds) * count($concreteEventIdsForThisTicket);

            $ticketConfigs = [
                "palimpsestIds" => self::$palimpsestIds,
                "eventIds" => $concreteEventIdsForThisTicket,
                "events" => self::$events,
                "markets" => ['boxedtrifecta'],
                /*"markets" => ['show'],*/
                /*"markets" => ['winner', 'show'],*/
                "selectMaxOddsOnly" => false,
                "selectMinOddsOnly" => false,
                "numSelectionsPerMarket" => 1,
                "system" => [
                    '1' => 100,
                    //'2' => 100,
                ] // x / 10
            ];

            $ticketMockup = $this->generateTicketMockup($ticketConfigs);

            /*
            $stakeAmount = array_sum(array_map(function ($value) {
                return $value / 100;
            }, array_values($ticketConfigs['system'])));*/
            $stakeAmount = array_sum(array_values($ticketConfigs['system']));
            $ticket = $this->ticketCreate($ticketMockup);
            // if (self::$DEBUG) {
            if (!is_object($ticket)) {
                print_r($ticket);
            }
            // }
            

            $ticketBody = json_decode($ticket->ticketbody);

            if (self::$DEBUG) {
                print_r($ticketBody);
            }

            $won_amount = $this->ticketWinCalculation($ticket);
            $wonAmount = ($won_amount / 100);
            array_push($wonAmounts, $wonAmount);

            $totalWinAmount += $wonAmount;
            $totalStakeAmount += $stakeAmount;

            // if ($i % 2 == 0) {
            //     //echo "N Tickets played: " . $i;
            //     fwrite(STDOUT, print_r("\nN Tickets played: " . $i . "\n", true));
            //     print_r("\nTotal won_amount: " . ($totalWinAmount / 100) . "\n ");
            //     print_r("\nTotal totalStakeAmount: " . ($totalStakeAmount / 100) . "\n ");
            //     print_r("\nTotal totalStakeAmount: " . ($totalWinAmount * 100 / $totalStakeAmount) . "\n ");
            // }
        }
        print_r("N Tickets played: " . ($i));


        //$totalWinAmount /= 100;
        $totalStakeAmount /= 100;

        //print_r("\nWon Amounts:\n ");
        //print_r($wonAmounts);
        print_r("\nTotal won_amount: $totalWinAmount\n ");
        print_r("\nTotal totalStakeAmount: $totalStakeAmount\n ");
        $totalWinPerc = $totalWinAmount * 100 / $totalStakeAmount;
        print_r("\nTotal totalWinPerc: $totalWinPerc %\n ");
        

        if (self::$DEBUG) {
            print_r(json_decode($eventWithResults->opts)->arrivalOrder);
        }
        


        //$this->assertTrue(10 > 1);
    }
}
