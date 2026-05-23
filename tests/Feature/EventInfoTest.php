<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Helmich\JsonAssert\JsonAssertions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;



class EventInfoTest extends TestCase
{
  use JsonAssertions, WithoutMiddleware;

    protected static $eventInfoResponse;

    protected function setUp(): void
    {
        parent::setUp();

        $initializationResponse = json_decode(InitializationTest::getResponse()->content());
        //$initializationResponse = $this->actingAs(\PGVirtual\Core\Models\User::find(1))->postJson("/api/$platform/init/cashier", []);
        //echo($initializationResponse->channels[0]->next_events[0]->int_pal_id." ".$initializationResponse->channels[0]->next_events[0]->int_event_id);
        //exit();
        self::$eventInfoResponse = $this->eventInfo(
          $initializationResponse->channels[0]->next_events[0]->int_pal_id,
          $initializationResponse->channels[0]->next_events[0]->int_event_id
        );
    }

    private function eventInfo($palimpsestId, $eventId) {
        /*return $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->getJson("/api/event/info/$palimpsestId/$eventId");*/
        return $this->actingAs(\PGVirtual\Core\Models\User::find(1))->getJson("/api/event/info/$palimpsestId/$eventId");
    }

    public static function getResponse() {
      return self::$eventInfoResponse;
    }

    public function testEventInfoRequest()
    {
        $response = self::getResponse();
        //dd($response);
        $response->assertStatus(200);

        $responseContent = $response->content();
        //dd($responseContent);
        $this->assertJsonDocumentMatchesSchema(
          $response->content(),
          [
            'type' => 'object',
            'required' => [/*'ret_code', 'description',*/ 'previous', 'current', 'next'],
            'properties' => [
              'ret_code' => [
                  'type' => 'number'
                ],
              'description' => [
                  'type' => 'string'
                ],
              'previous' => [
                  'type' => 'object',
                  'required' => ['results', 'video', 'odds', /*'multi',*/ 'int_pal_id', 'int_event_id'],
                  'properties' => [
                    'results' => [],
                    'video' => [
                      'type' => 'object',
                      'required' => [
                        'src',
                        'duration',
                        // 'metadata'
                      ],
                      'properties' => [
                        'src' => [
                            'type' => 'string'
                          ],
                          'duration' => [
                            'type' => 'number'
                          ],
                          'metadata' => [
                            'type' => 'object'
                          ]
                      ]
                    ],
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
                ],
              'current' => [
                  'type' => 'object',
                  'required' => ['racers', 'odds', 'latecomers', 'int_pal_id', 'int_event_id', 'start_time','since','game_duration'],
                  'properties' => [
                    'int_pal_id' => ['type' => 'string'],
                    'int_event_id' => ['type' => 'string'],
                    'racers' => [],
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
                        ],
                        'placed' => [
                          'type' => 'object'
                        ],
                        'show' => [
                          'type' => 'object'
                        ],
                        'exacta' => [
                          'type' => 'object'
                        ],
                        'quinella' => [
                          'type' => 'object'
                        ], 'underover' => [
                          'type' => 'object'
                        ],
                        'evenodd' => [
                          'type' => 'object'
                        ],
                        'trifecta' => [
                          'type' => 'object'
                        ],
                        'boxedTrifecta' => [
                          'type' => 'object'
                        ]
                      ]
                    ],
                    'latecomers' => [
                      'type' => 'object',
                      'required' => [
                        'winner',
                        'exacta',
                        'trifecta'
                      ],
                      'properties' => [
                        'winner' => [
                            'type' => 'object',
                            'required' => [
                            /*  'racers',
                              'delay'*/
                            ],
                            'properties' => [
                              'racers' => [
                                'type' => 'array',
                                'minLength' => 1
                              ],
                              'delay' => [
                                'type' => 'number'
                              ]
                            ]
                        ],
                        'exacta' => [
                            'type' => 'object',
                            'required' => [
                            /*  'racers',
                              'delay'*/
                            ],
                            'properties' => [
                              'racers' => [
                                'type' => 'array',
                                'minLength' => 2,
                              ],
                              'delay' => [
                                'type' => 'number'
                              ]
                            ]
                        ],
                        'trifecta' => [
                            'type' => 'object',
                            'required' => [
                          /*    'racers',
                              'delay'*/
                            ],
                            'properties' => [
                              'racers' => [
                                'type' => 'array',
                                'minLength' => 3,
                              ],
                              'delay' => [
                                'type' => 'number'
                              ]
                            ]
                        ]
                      ]
                    ]
                  ]
                ],
              'next' => [
                'type' => 'object',
                'required' => ['int_pal_id', 'int_event_id'],
                'properties' => [
                  'int_pal_id' => [
                    'type' => 'string'
                  ],
                  'int_event_id' => [
                    'type' => 'string'
                  ],
                ],
              ],
            ]

          ]
        );
    }
}
