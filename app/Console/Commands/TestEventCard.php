<?php

namespace App\Console\Commands;

use App\Libraries\IsibetLib;
use Illuminate\Console\Command;
use PGVirtual\Core\Models\Event;

class TestEventCard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pg:testEventCard';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test EventCard';

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
        $event = Event::where('id','=',107535)->orderby('time')->first();
         
        $eventCards["virtualEvents"][]=IsibetLib::create_event_card2($event);
        print_r($eventCards);
        echo(json_encode($eventCards));
        return 0;
    }
}
