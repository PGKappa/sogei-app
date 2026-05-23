<?php

namespace App\Console\Commands;

use App\Libraries\IsibetLib;
use Illuminate\Console\Command;
use PGVirtual\Core\Models\Channel;
use PGVirtual\Core\Models\Event;

class UpdatePalinsestIsibet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pg:updatePalinsestIsibet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recupera il palinsesto e il numero di evento rilasciato da ams per isibet';

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

        $channels = Channel::all();
            

            foreach($channels as $ch){
                $events = Event::whereNotNull('sent_on')->whereNull('ext_pal_id')->get();

                foreach ($events as $event) {
                    IsibetLib::getPalinsesto($event->id, $ch);
                }
            }

        
        

        return 0;
    }
}
