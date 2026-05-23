<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Helmich\JsonAssert\JsonAssertions;



class IdentificationTest extends TestCase
{
  use JsonAssertions, WithoutMiddleware;

    protected static $identificationResponse;

    protected function setUp(): void
    {
        $this->markTestSkipped('deprecated');
        parent::setUp();
        self::$identificationResponse = $this->identification();
    }

    private function identification() {
        return $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->postJson('/api/identification', ['name' => 'test','password' =>'test']);
    }

    public static function getResponse() {
      if (!self::$identificationResponse) {
        $mIdentificationTest = new IdentificationTest();
        $mIdentificationTest->setUp();
      }
      return self::$identificationResponse;
    }

    public function testIdentificationRequest()
    {
        $response = self::getResponse();
        $response->assertStatus(200);

        $this->assertJsonDocumentMatchesSchema(
          $response->content(),
          [
            'type' => 'object',
            'required' => ['token', 'description', 'ret_code'],
            'properties' => [
              'ret_code' => [
                  'type' => 'number'
                ],
              'description' => [
                  'type' => 'string'
                ],
              'token' => [
                  'type' => 'string'
                ]
            ]

          ]
        );
    }
}
