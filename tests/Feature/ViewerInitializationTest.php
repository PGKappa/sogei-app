<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Helmich\JsonAssert\JsonAssertions;
use phpmock\MockBuilder;
use Session;

class ViewerInitializationTest extends TestCase
{
  use JsonAssertions, WithoutMiddleware;

    protected static $initializationResponse;

    protected function setUp(): void
    {
      parent::setUp();
      //$identificationResponse = IdentificationTest::getResponse()->content();
      // , $identificationResponse->token
      self::$initializationResponse = $this->initializeViewer('web','authcode','1');
    }

    private function initializeViewer($platform,$authcode,$channel/*, $bearerToken*/) {
      $response = $this->actingAs(\PGVirtual\Core\Models\User::find(1))->postJson("/api/$platform/init/viewer/$authcode/$channel", []);
      /*print_r($response);
      exit();*/
      return $response;
    }


    public static function getResponse() {
      if (!self::$initializationResponse) {
        $mInitializationTest = new ViewerInitializationTest();
        $mInitializationTest->setUp();
      }
      return self::$initializationResponse;
    }


    public function testViewerInitRequest()
    {
      // TEST DRIVEN DEVELOPMENT
        $response = self::getResponse();
        //print_r($response);
        $response->assertStatus(200);

        //dd($response->content());
        $this->assertJsonDocumentMatchesSchema(
          $response->content(),
          [
            'type' => 'object',
          'required' => ['ret_code','description','game_statics','next_event'/*,'timestamp'*/],
            'properties' => [
              'ret_code' => [
                'type' => 'integer',
              ],
              'description' => [
                'type' => 'string',
              ],
              'game_statics' => [
                'type' => 'object',
                'required' => ['dict','channel_id','game_id','game_total_duration','page_main_duration','page_trifecta_duration','page_results_duration'],
                'properties' => [
                  'dict' => [
                    'type' => 'object',
                  ],
                  'channel_id' => [
                    'type' => 'integer',
                  ],
                  'game_id' => [
                    'type' => 'integer',
                  ],
                  'game_total_duration' => [
                    'type' => 'number',
                  ],
                  'page_main_duration' => [
                    'type' => 'number',
                  ],
                  'page_trifecta_duration' => [
                    'type' => 'number',
                  ],
                  'page_results_duration' => [
                    'type' => 'number',
                  ],
                ],
              ],
              'next_event' => [
                'type' => 'object',
                'required' => ['int_pal_id','int_event_id'],
                'properties' => [
                  'int_pal_id' => [
                    'type' => 'string',
                  ],
                  'int_event_id' => [
                    'type' => 'string',
                  ],
                ],
              ],
              'timestamp' => [
                'type' => 'string',
              ],
            ],
          ]
        );
    }
}
