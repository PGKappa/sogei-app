<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use PGVirtual\Core\Models\Ticket;

class TicketModelTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {
        $ticket = Ticket::find(196);
        
        echo "\n";
        echo $ticket->getType()."\n";

        //$response = $this->get('/');

        //$response->assertTrue(true);
        $this->assertTrue(true);
    }
}
