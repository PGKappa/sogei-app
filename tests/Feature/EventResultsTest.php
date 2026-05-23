<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Helmich\JsonAssert\JsonAssertions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;



class EventResultsTest extends TestCase
{
  use JsonAssertions, WithoutMiddleware;

    protected static $eventResultsResponse;

    protected function setUp(): void
    {
        parent::setUp();

        $initializationResponse = json_decode(InitializationTest::getResponse()->content());

        self::$eventResultsResponse = $this->eventResults(
          $initializationResponse->channels[0]->prev_events[0]->int_pal_id,
          $initializationResponse->channels[0]->prev_events[0]->int_event_id
        );
    }

    private function eventResults($palimpsestId, $eventId) {
        /*return $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->getJson("/api/event/results/$palimpsestId/$eventId");*/
        return $this->actingAs(\PGVirtual\Core\Models\User::find(1))->getJson("/api/event/results/$palimpsestId/$eventId");
    }

    public static function getResponse() {
      return self::$eventResultsResponse;
    }

    public function testEventResultsRequest()
    {
        $response = self::getResponse();
        //dd($response);
        $response->assertStatus(200);


        $responseContent = $response->content();
        //dd($responseContent);
        $this->assertJsonDocumentMatchesSchema(
          $responseContent,
          [
            'type' => 'object',
            'required' => [
              //'ret_code',
              //'description',
              'arrival',
              //'multi',
              'odds',
              'start_time',
              //'timestamp',
              'int_pal_id',
              'int_event_id'
            ],
            'properties' => [
              'ret_code' => [
                  'type' => 'number'
                ],
              'description' => [
                  'type' => 'string'
                ],

              'start_time' => [
                  'type' => 'string'
                ],

              'int_pal_id' => [
                  'type' => 'string'
                ],

              'int_event_id' => [
                  'type' => 'string'
                ],

              'arrival' => ['type' => 'array'],
              'odds' => [
                'required' => [
                  'winner',
                  'placed',
                  'show',
                  'exacta',
                  'quinella',
                  'underover',
                  'evenodd',
                  'trifecta',
                  'boxedTrifecta'
                ],
                'properties' => [
                  'winner' => [
                  'type' => 'object'
                ], 'placed' => [
                  'type' => 'object'
                ], 'show' => [
                  'type' => 'object'
                ], 'exacta' => [
                  'type' => 'object'
                ], 'quinella' => [
                  'type' => 'object'
                ], 'underover' => [
                  'type' => 'object'
                ], 'evenodd' => [
                  'type' => 'object'
                ], 'trifecta' => [
                  'type' => 'object'
                ],
                'boxedTrifecta' => [
                  'type' => 'object'
                ]
              ]
            ],
            'multi' => ['type' => 'number']
            ]
          ]
        );

        $arrivalElement = json_decode($responseContent)->arrival[0];

        $this->assertJsonDocumentMatchesSchema(
          $arrivalElement,
          [
            'number' => [
              'type' => 'string'
            ],
            'name'=> [
                'type' => 'string'
            ]
          ]
        );


    }
}
