<?php

namespace Tests\Feature;

//use Illuminate\Foundation\Testing\WithoutMiddleware;
//use Illuminate\Foundation\Testing\RefreshDatabase;
//use Helmich\JsonAssert\JsonAssertions;
//use Illuminate\Testing\TestResponse;
use Tests\TestCase;

//use PGVirtual\Core\Libraries\OddsUtils;
use PGVirtual\GameDogs\Controllers\OddsController;
use PGVirtual\Core\Models\Event;


class DogsOddsTest extends TestCase
{
  protected $oddsController;
  protected function setUp(): void
  {
    parent::setUp();
    $palimpsest_id = '1000000000';
    $event_id = '2';
    $event = Event::where('int_pal_id', $palimpsest_id)->where('int_event_id', $event_id)->first();
    $currentEventOpts = $event->getOpts();
    $oddsConfig['winnerProbs'] = array_map(function ($x) {
      return $x['chance'];
    },$currentEventOpts['racers']);

    $this->oddsController = new OddsController($oddsConfig);
  }

  public function testWinnerProbsSumIsUniverseSize() {
    echo "WinnerProbs: ".json_encode($this->oddsController->winnerProbs, JSON_PRETTY_PRINT)."\n";
    $this->assertEquals(OddsController::UNIVERSE_SIZE, array_sum($this->oddsController->winnerProbs));
  }

  public function testPrintWinnerOdds() {
    $winnerOdds = $this->oddsController->getWinnerOdds();
    echo "WinnerOdds: ".json_encode($winnerOdds, JSON_PRETTY_PRINT)."\n";
    $sum = 0;
    foreach ($winnerOdds as $key => &$value) {
      $value = 100 / $value;
      $sum += $value;
    }
    echo "100/WinnerOdds: ".json_encode($winnerOdds, JSON_PRETTY_PRINT)."\n";
    echo "ProbsSum: ".$sum."\n";
    $this->assertTrue(true);
  }

  public function testPrintPlacedProbs() {
    echo "Placed Probs: ".json_encode($this->oddsController->getPlacedProbs(), JSON_PRETTY_PRINT)."\n";
    echo "Placed Sum: ".array_sum($this->oddsController->getPlacedProbs())."\n";
    $this->assertTrue(true);
  }

  public function testPrintPlacedOdds() {
    $placedOdds = $this->oddsController->getPlacedOdds();
    echo "Placed Odds: ".json_encode($placedOdds, JSON_PRETTY_PRINT)."\n";
    $sum = 0;
    foreach ($placedOdds as $key => &$value) {
      $value = 100 / $value;
      $sum += $value;
    }
    echo "100/placedOdds: ".json_encode($placedOdds, JSON_PRETTY_PRINT)."\n";
    echo "ProbsSum: ".$sum."\n";
    $this->assertTrue(true);
  }
  public function testPrintShowProbs() {
    echo "Show Probs: ".json_encode($this->oddsController->getShowProbs(), JSON_PRETTY_PRINT)."\n";
    echo "Show Sum: ".array_sum($this->oddsController->getShowProbs())."\n";
    $this->assertTrue(true);
  }
  public function testPrintShowOdds() {
    $showOdds = $this->oddsController->getShowOdds();
    echo "Show Odds: ".json_encode($showOdds, JSON_PRETTY_PRINT)."\n";
    $sum = 0;
    foreach ($showOdds as $key => &$value) {
      $value = 100 / $value;
      $sum += $value;
    }
    echo "100/showOdds: ".json_encode($showOdds, JSON_PRETTY_PRINT)."\n";
    echo "ProbsSum: ".$sum."\n";
    $this->assertTrue(true);
  }
  



}
?>
