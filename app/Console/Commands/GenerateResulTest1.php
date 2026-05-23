<?php                          
                                                     
namespace App\Console\Commands;                      
                               
use Illuminate\Console\Command; 
use PGVirtual\Core\Models\Event;                     
                                                    
                  
                                                     
class GenerateResultTest1 extends Command           
{                 
    /**                                              
     * The name and signature of the console command.
     *                                             
     * @var string
     */                                             
    protected $signature = 'pg:generateResultTest1';
                   
    /**                                
     * The console command description.            
     *                           
     * @var string            
     */                                            
    protected $description = 'Command description';
                   
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
                                                  
        $event = Event::where('id',1428)->first();
            $opts = $event->getOpts();



        $s = \PGVirtual\GameDogs\Controllers\EventController::scaleNumbersAndGetArrivalOrder($opts["rns"], $opts["racers"]);
        $scaledRNs = $s[0];
        $arrivalOrder = $s[1];


        $opts["arrivalOrder"] = $arrivalOrder;
        $event->opts = json_encode($opts);
        $event->status = Event::STATUS_GENERATED_ARRIVAL;
        echo json_encode($event);


        return 0;
    }
}
