<?php

namespace App\Console\Commands;

use App\Libraries\IsibetLib;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PGVirtual\Core\Models\Event;
use PGVirtual\Core\Models\ApiLog;
use PGVirtual\Core\Models\Channel;
use PGVirtual\Core\Models\EventExt;
use PGVirtual\Core\Models\Configuration;



class PublishEventsToIsibet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pg:publishEventsToIsibet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invia gli eventi ad Isibet';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {




        $isibetLog = Configuration::where('key', 'ISIBET_LOG')->first()->getValue();

        // Blocca l'evento  
        //   return 0;
        //  while(false){
        while (true) {

            /* recupero il totale dei canali */

            $channels = Channel::all();
            $date = Carbon::now();

            foreach ($channels as $ch) {
                echo "Elaboro channel " . $ch->id . PHP_EOL;
                $timeDa = Carbon::now()->addMinutes(20);
                $timeA = Carbon::now()->addMinutes(240);
                $events = Event::where('channel_id', $ch->id)->where('time', '>', $timeDa)->where('time', '<', $timeA)->whereNull('sent_on')->orderby('time')->get();


                Log::info("GET EVENTS " . $timeDa->toIso8601String() . " - " . $timeA->toIso8601String() . " totale: " . count($events) . PHP_EOL);
                echo ("GET EVENTS " . $timeDa->toIso8601String() . " - " . $timeA->toIso8601String() . " totale: " . count($events) . PHP_EOL);
                //Log::info($lastQuery);
                //print_r($lastQuery);
                //print_r($events);
                if (count($events) > 0) {
                    try {
                        $eventCounter = 0;
                        $eventCards = [];
                        foreach ($events as $event) {
                            $eventCards["virtualEvents"][] = IsibetLib::create_event_card2($event);
                            $eventCounter++;
                            if ($eventCounter > config('isibet.number_of_events_can_to_send') - 1) break;
                            //echo json_encode(["event_pal"=>$event['int_pal_id'],'event_id:' =>$event['int_event_id'], "eventCards"=>$eventCards]);

                        }

                        $date_sent_on = Carbon::now();
                        if (count($eventCards['virtualEvents']) > 0) {



                            $eventCardMessage = IsibetLib::sendEventsCard($eventCards, $ch->isibet_controller_id);
                            if ($eventCardMessage) {
                                foreach ($eventCards['virtualEvents'] as $e) {
                                    $event = Event::where('id', $e['eventId'])->first();
                                    $event->sent_on = $date_sent_on;
                                    $event->save();
                                    if ($isibetLog == "true") {
                                        EventExt::create(
                                            ['event_id' => $event->id, 'isibet_eventcard' => json_encode(array("transactionId" => $eventCardMessage['transactionId'], "messageDateTime" => $eventCardMessage['messageDateTime'], "eventcard" => $e))]
                                        );
                                    }
                                }
                            }
                        }
                    } catch (Exception $ex) {
                        echo "Errore invio eventcards" . PHP_EOL . $ex->getMessage() . PHP_EOL;
                        Log::info("ERRORE INVIO EVENTCARDS" . PHP_EOL . $ex->getMessage()) . PHP_EOL;
                    }
                }
                sleep(10);
                /* controllo i numeri di palinsesto degli invii effettuati 30 minuti prima*/
                try {
                    $sogeiChiusoDa = "01:10";
                    $sogeiChiusoA = "05:30";
                    $adesso = date("H:i");
                    if (!($adesso >= $sogeiChiusoDa && $adesso <= $sogeiChiusoA)) {
                        Log::info('QUERY PALINSESTO' . PHP_EOL);
                        $pInviatiDa = Carbon::now()->subMinutes(5);
                        $pTimeDa = Carbon::now();
                        $pTimeA = Carbon::now()->addMinutes(120);
                        $eventsSentOn30 = Event::where('sent_on', '<', $pInviatiDa)->whereNotNull('sent_on')
                            ->where('status', 0)->whereNull('ext_pal_id')->where('time', '>=', $pTimeDa)
                            ->where('time', '<=', $pTimeA)->orderBy('time')->get();


                        Log::info('QUERY PALINSESTO' . PHP_EOL);
                        Log::Info($eventsSentOn30);


                        /*   ApiLog::create([
                            'method_name' => "controllo palinsesto",
                            'operator_id' => '1',
                            'URL' =>'',
                            'request_data' => 'Send event',
                            'response_data' => json_encode($eventsSentOn30)
                            ]);
                        
                            //*/


                        echo "controllo palinsesto eventi: channel_id: " . $ch->id . ", sent_on: " . $pInviatiDa->toIso8601String() . ", inizio evento tra:" . $pTimeDa->toIso8601String() . " e " . $pTimeA->toIso8601String() . ", totale:" . count($eventsSentOn30) . PHP_EOL;
                        foreach ($eventsSentOn30 as $event) {
                            $channel = Channel::where('id', $event->channel_id)->first();
                            echo "controllo palinsesto Controller:" . $channel->isibet_controller_id . "  evento: " . $event->id . PHP_EOL;
                            IsibetLib::getPalinsesto($event->id, $channel->isibet_controller_id);
                            sleep(1);
                        }
                        sleep(6);
                    }
                } catch (Exception $ex) {
                    echo ("ERRORE CONTROLLO PALINSESTO" . PHP_EOL . $ex->getMessage() . PHP_EOL);
                    Log::info("ERRORE CONTROLLO PALINSESTO" . PHP_EOL . $ex->getMessage());
                }
            }
        }


        return 0;
    }
}
