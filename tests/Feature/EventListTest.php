<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Helmich\JsonAssert\JsonAssertions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;



class EventListTest extends TestCase
{
  use JsonAssertions, WithoutMiddleware;

    protected static $eventListResponse;

    protected function setUp(): void
    {
        parent::setUp();

        $initializationResponse = json_decode(InitializationTest::getResponse()->content());

        self::$eventListResponse = $this->eventList();
    }

    private function eventList() {
        /*return $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->getJson("/api/event/list");*/
        return $this->actingAs(\PGVirtual\Core\Models\User::find(1))->getJson("/api/event/list");
    }

    public static function getResponse() {
      return self::$eventListResponse;
    }

    public function testEventListRequest()
    {
        $response = self::getResponse();
        $response->assertStatus(200);

        $responseContent = $response->content();
        $this->assertJsonDocumentMatchesSchema(
          $responseContent,
          [
            'type' => 'object',
            'required' => [
              //'ret_code',
              //'description',
              'channels',
              //'timestamp'
            ],
            'properties' => [
              'ret_code' => [
                  'type' => 'number'
                ],
              'description' => [
                  'type' => 'string'
                ],
              'channels' => [
                  'type' => 'array'
              ]
            ]
          ]
        );

        $firstChannel = json_decode($responseContent)->channels[0];
        $this->assertJsonDocumentMatchesSchema(
          $firstChannel,
          [
            'required' => ['id','game_id','next_events','prev_events'],
            'properties' => [
             'id' => 'number',
             'game_id' => 'number',
             'next_events' => [
               'type' => 'array',
             ],
             'prev_events' => [
               'type' => 'array'
             ]
           ]
          ]
        );

        $firstChannelFirstNextEvent = $firstChannel->next_events[0];
        $this->assertJsonDocumentMatchesSchema(
          $firstChannelFirstNextEvent,
          [
            'required' => ['int_pal_id', 'int_event_id','time','since'],
            'properties' => [
              'int_pal_id' => ['type' => 'string'],
              'int_event_id' => ['type' => 'string'],
              'time' => [
                'type' => 'string',
              ],
              'since' => [
                'type' => 'number',
              ]
            ]
          ]
        );

        $firstChannelFirstPrevEvent = $firstChannel->prev_events[0];
        $this->assertJsonDocumentMatchesSchema(
          $firstChannelFirstPrevEvent,
          [
            'required' => ['int_pal_id', 'int_event_id','time','since','game_duration','race_duration','arrival'],
            'properties' => [
              'int_pal_id' => ['type' => 'string'],
              'int_event_id' => ['type' => 'string'],
              'status' => ['type' => 'number'],
              'time' => ['type' => 'string'],
              'since' => ['type' => 'number'],
              'game_duration' => ['type' => 'number'],
              'race_duration' => ['type' => 'number'],
              'arrival' => ['type' => 'array']
            ]
          ]
        );
    }
}
