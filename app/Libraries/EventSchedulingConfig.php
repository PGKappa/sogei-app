<?php

namespace App\Libraries;

use PGVirtual\Core\Libraries\DayConfig;
use PGVirtual\Core\Models\Channel;
use PGVirtual\Core\Models\Game;
use PGVirtual\Core\Models\Configuration;
use \Exception;
use Error;

class EventSchedulingConfig
{
    public static $config;

    const DOGS6 = 1;
    const HORSE6 = 2;
    const HARNESS6 = 3;
    const DOGS8 = 4;
    const HORSE8 = 5;
    const HARNESS8 = 6;

    const DOGS62 = 7;
    const HORSE62 = 8;
    const HARNESS62 = 9;
    const DOGS82 = 10;
    const HORSE82 = 11;
    const HARNESS82 = 12;


    public static function init()
    {
        $enabledChannelsQuery = Configuration::where('key', 'ENABLED_CHANNELS_FOR_OPERATOR')
            ->where('value', "true");

        $enabledChannels = $enabledChannelsQuery->get();

        self::$config = array();
        foreach ($enabledChannels as $enabledChannel) {
            $channelId = $enabledChannel->channel_id;
            $channelQuery = Channel::where('id', $channelId);
            if ($channelQuery->doesntExist()) {
                continue;
            }
            $channel = $channelQuery->first();
            $gameId = $channel->game_id;
            $operatorId = $enabledChannel->operator_id;
            self::$config[$gameId] = self::$config[$gameId] ?? array();

            $gameQuery = Game::where('id', $gameId);
            if ($gameQuery->doesntExist()) {
                continue;
            }
            $game = $gameQuery->first();

            $numRacers = $game->racers;
            $dayConfigQuery = Configuration::select('value')
                ->where('key', 'DAY_CONFIG')
                ->where('operator_id', $operatorId)
                ->where('channel_id', $channelId);
            if ($dayConfigQuery->doesntExist()) {
                continue;
            }
            $dayConfig = $dayConfigQuery->first();
            $rawDayConfigValue = $dayConfig->value;
            $dayConfigValue = json_decode($rawDayConfigValue, 1);

            self::$config[$gameId][$channelId] = array();
            for ($i = 1; $i <= 13; $i++) {
                self::$config[$gameId][$channelId][] = new DayConfig(
                    $dayConfigValue['firstEventTime'],
                    $dayConfigValue['eventDuration'],
                    $dayConfigValue['eventsCount'],
                    new \PGVirtual\GameDogs\Libraries\ProbConfigs($numRacers)
                );
            }
        }
    }

    public static function get($channel, $dayNumber)
    {
        try {
            return self::$config[$channel->game_id][$channel->id][$dayNumber - 1];
        } catch (Exception | Error $e) {
        }
        $gameId = $channel->game_id;
        $gameQuery = Game::where('id', $gameId);
        if ($gameQuery->doesntExist()) {
            throw new GameDoesNotExistsException("Requested game doesn't exist");
        }
        $game = $gameQuery->first();
        $numRacers = $game->racers;
        switch ($gameId) {
            case self::DOGS6:
                $channelId = $channel->id;
                if ($channel->id == 2) {
                    return new DayConfig(0, 240, 360, new \PGVirtual\GameDogs\Libraries\ProbConfigs($numRacers));
                }
                return new DayConfig(60, 240, 360, new \PGVirtual\GameDogs\Libraries\ProbConfigs($numRacers));

                break;
            case self::DOGS62:
                $channelId = $channel->id;
                if ($channel->id == 2) {
                    return new DayConfig(0, 240, 360, new \PGVirtual\GameDogs\Libraries\ProbConfigs($numRacers));
                }
                return new DayConfig(60, 240, 360, new \PGVirtual\GameDogs\Libraries\ProbConfigs($numRacers));

                break;
            case self::HORSE8:
                $channelId = $channel->id;
                $timeOffset = 0;
                switch ($channelId) {
                    case 1:
                    case 2:
                        $timeOffset = 0;
                        break;
                    case 3:
                        $timeOffset = 120;
                        break;
                    default:
                        $timeOffset = 180;
                        break;
                }
                return new DayConfig($timeOffset, 240, 360, new \PGVirtual\GameHorses\Libraries\ProbConfigs($numRacers));

                break;
            case self::HORSE6:
                $channelId = $channel->id;
                $timeOffset = 0;
                switch ($channelId) {
                    case 1:
                    case 2:
                        $timeOffset = 0;
                        break;
                    case 3:
                        $timeOffset = 120;
                        break;
                    default:
                        $timeOffset = 180;
                        break;
                }
                return new DayConfig($timeOffset, 240, 360, new \PGVirtual\GameHorses\Libraries\ProbConfigs($numRacers));

                break;
            case self::HORSE62:
                $channelId = $channel->id;
                $timeOffset = 0;
                switch ($channelId) {
                    case 1:
                    case 2:
                        $timeOffset = 0;
                        break;
                    case 3:
                        $timeOffset = 120;
                        break;
                    default:
                        $timeOffset = 180;
                        break;
                }
                return new DayConfig($timeOffset, 240, 360, new \PGVirtual\GameHorses\Libraries\ProbConfigs($numRacers));

                break;
            case self::HARNESS62:
                $channelId = $channel->id;
                $timeOffset = 0;
                switch ($channelId) {
                    case 1:
                    case 2:
                        $timeOffset = 0;
                        break;
                    case 3:
                        $timeOffset = 120;
                        break;
                    default:
                        $timeOffset = 180;
                        break;
                }
                return new DayConfig($timeOffset, 240, 360, new \PGVirtual\GameHorses\Libraries\ProbConfigs($numRacers));

                break;
            case self::DOGS8:
                return new DayConfig(180, 240, 360, new \PGVirtual\GameDogs\Libraries\ProbConfigs($numRacers));
                break;
            case self::DOGS82:
                return new DayConfig(180, 240, 360, new \PGVirtual\GameDogs\Libraries\ProbConfigs($numRacers));
                break;
            case self::HARNESS8:
                $channelId = $channel->id;
                $timeOffset = 0;
                switch ($channelId) {
                    case 1:
                    case 2:
                        $timeOffset = 0;
                        break;
                    case 3:
                        $timeOffset = 120;
                        break;

                    default:
                        $timeOffset = 180;
                        break;
                }

                return new DayConfig($timeOffset, 240, 360, new \PGVirtual\GameHarness\Libraries\ProbConfigs($numRacers));
                break;
            case self::HARNESS82:
                $channelId = $channel->id;
                $timeOffset = 0;
                switch ($channelId) {
                    case 1:
                    case 2:
                        $timeOffset = 0;
                        break;
                    case 3:
                        $timeOffset = 120;
                        break;

                    default:
                        $timeOffset = 180;
                        break;
                }

                return new DayConfig($timeOffset, 240, 360, new \PGVirtual\GameHarness\Libraries\ProbConfigs($numRacers));
                break;
            default:
                break;
        }
    }
}
