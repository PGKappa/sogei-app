<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Helmich\JsonAssert\JsonAssertions;
use phpmock\MockBuilder;
use Session;

class InitializationTest extends TestCase
{
  use JsonAssertions, WithoutMiddleware;

    protected static $initializationResponse;

    protected function setUp(): void
    {
      parent::setUp();

      //$identificationResponse = IdentificationTest::getResponse()->content();
      // , $identificationResponse->token
      self::$initializationResponse = $this->initializeCashier('web');


    }

  private function initializeCashier($platform/*, $bearerToken*/) {
      $response =
      $this->withHeaders([
        'Operator' => 'tigergames',
        'Authorization' => 'Bearer b3448bc0-7a7d-11eb-a80c-2b53b2e12b25',
      ])->actingAs(\PGVirtual\Core\Models\User::find(1))
      ->postJson("/api/$platform/init/cashier", []);
      return $response;
    }

    public static function getResponse() {
      if (!self::$initializationResponse) {
        $mInitializationTest = new InitializationTest();
        $mInitializationTest->setUp();
      }
      return self::$initializationResponse;
    }


    public function testCashierInitRequest()
    {
      // TEST DRIVEN DEVELOPMENT
        $response = self::getResponse();
        $response->assertStatus(200);

        $enabledGames = ['dogs6'];
        $gameStatics = [];
        foreach ($enabledGames as $gameId) {
          $gameStatics[$gameId] = [
            'type' => 'object',
            'required' => [
              'dict',
              'min_bet',
              'min_stake',
              'max_win',
              'event_closes_in_seconds'
            ],
            'properties' => [
              'dict' => [
                'type' => 'object',
              ],
              'factory_url' => ['type' => 'string'],
              'integration_id' => ['type' => 'string'],
              'min_bet' => ['type' => 'string'],
              'min_stake' => ['type' => 'string'],
              'max_win' => ['type' => 'string'],
            ]
          ];
        }

//dd($response->content());
        $this->assertJsonDocumentMatchesSchema(
          $response->content(),
          [
            'type' => 'object',
            'required' => [
              'ret_code',
              'description',
              'cmd',
              //'user_level',
              //'user_level_name',
              'game_statics',
              'channels'
              //'event',
              //'operator',
            ],
            'properties' => [
              'ret_code' => [
                  'type' => 'number'
                ],
              'description' => [
                  'type' => 'string'
              ],
              'cmd' => [
                  'type' => 'string'
              ],
              'user_level' => [
                  'type' => 'number'
              ],
              'user_level_name' => [
                  'type' => 'string'
              ],
              /*'operator' => [
                  'type' => 'object',
                  'required' => ['id', 'name', 'viewer_id', 'status'],
                  'properties' => [
                    'id' => ['type' => 'number'],
                    'name' => ['type' => 'string'],
                    'viewer_id' => ['type' => 'string'],
                    'status' => ['type' => 'number']
                  ]
              ],
              'language' => [
                  'type' => 'object',
                  'required' => ['id', 'name'],
                  'properties' => [
                    'id' => ['type' => 'number'],
                    'name' => ['type' => 'string']
                  ]
              ],*/
              'channels' => [
                  'type' => 'array',
              ],
              'game_statics' => [
                  'type' => 'object',
                  'required' => array_map(fn($x) => $x , $enabledGames),
                  'properties' => $gameStatics
              ],

            ]

          ]
        );
    }

}
