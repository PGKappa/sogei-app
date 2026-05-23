<?php

namespace App\Libraries;

class ADMConfigs {
    public static $CONFIG_FILE_PATH = "";

    private static $data = null;
    private static $marketMap = null;
    const UNIVERSE_SIZE = 1000000;

    public static function load() {

       /*  echo self::$CONFIG_FILE_PATH;
        echo dirname(__FILE__); */


        self::$CONFIG_FILE_PATH = dirname(__FILE__) ."/data.json";
        $dataString = file_get_contents(self::$CONFIG_FILE_PATH);
        if (!($dataString === false || $dataString === null)) {
            self::$data = json_decode($dataString, true);
            if (self::$data === null) {
                throw new UnableToLoadConfigsException();
            }
        } else {
            throw new UnableToLoadConfigsException();
        }

        self::buildMarketMap();
        return self::$data;
    }

    private static function buildMarketMap() {
        self::$marketMap = ['' => ''];
        foreach (self::$data['markets'] as $market) {
            switch ($market['marketId']) {
                case 'underover':
                    self::$marketMap[$market['marketId'].$market['availableForRacers']] = $market;
                break;
                default:
                    self::$marketMap[$market['marketId']] = $market;
                break;
            }
        }


    }

    public static function getMarket($marketId) {
        return self::$marketMap[$marketId];
    }

    public static function getAvailableBetCodes() {
        return array_map(function($market) {
            return $market['code'];
        }, self::$data['markets']);
    }

    public static function getBetCode($marketId) {
        return self::getMarket($marketId)['code'];
    }

    public static function getAvailableGameCodes() {
        return array_map(function($g) {
            return $g['code'];
        }, self::$data['games']);
    }

    public static function getGameByCode($gameCode) {
        $gameData = null;
        for ($i = 0; $i < count(self::$data['games']); $i++) {
            $g = self::$data['games'][$i];
            if ($g['code'] == $gameCode) {
                $gameData = $g;
            }
        }
        return $gameData;
    }
}
