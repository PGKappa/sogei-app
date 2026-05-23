<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;
use PGVirtual\Core\Models\Video;
use PGVirtual\Core\Models\Game;

class VideosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $SUPPORTED_GAMES = ["dogs", "horses"];
        $SUPPORTED_NUM_RACERS = [
            "dogs" => [6],
            "horses" => [6]
        ];
        $VIDEOSET_FILES = ["videosets/videos_dogs6.txt", "videosets/videos_horses6.txt"];

        foreach ($VIDEOSET_FILES as $videosetFilePath) {
            $handle = fopen(__DIR__ . "/" . $videosetFilePath, "r");
            if ($handle) {
                // read game
                if (($line = fgets($handle)) !== false) {
                    $line = trim($line);
                    [$gameConfKey, $gameName] = explode(":", $line);
                    $gameName = trim($gameName);

                    echo "Found key: $gameConfKey\n";
                    echo "Found value: $gameName\n";

                    if (!in_array($gameName, $SUPPORTED_GAMES))
                        throw new Exception('Game not supported.');
                }

                // read racers
                if (($line = fgets($handle)) !== false) {
                    $line = trim($line);
                    [$racersConfKey, $numRacers] = explode(":", $line);
                    $numRacers = trim($numRacers);

                    echo "Found racers: $racersConfKey\n";
                    echo "Found value: $numRacers\n";

                    if (!in_array($numRacers, $SUPPORTED_NUM_RACERS[$gameName]))
                        throw new Exception("Num of racers ($numRacers) not supported for game \"$gameName\".");
                }

                $gameQuery = Game::where('name', $gameName)
                    ->where('racers', $numRacers);
                if ($gameQuery->doesntExist()) {
                    throw new Exception('Game not found in database.');
                }
                $gameModel = $gameQuery->first();

                // read videos
                while (($line = fgets($handle)) !== false) {
                    $fileName = trim($line);
                    echo "Found video: $fileName\n";

                    // 123a.mp4 50
                    $fileName = explode(" ", $fileName);
                    if (count($fileName) > 1) {
                        $duration = trim($fileName[1]);
                    } else {
                        $duration = 50;
                    }
                    echo "Found video duration: $duration\n";

                    $fileName = trim($fileName[0]);
                    echo "Found video name: $fileName\n";

                    // 123a.mp4
                    $arrivalOrder = explode(".", $fileName);
                    if (count($arrivalOrder) != 2) {
                        throw new Exception("Found invalid filename: $fileName");
                    }
                    // 123a
                    $arrivalOrder = str_split($arrivalOrder[0]);
                    if (count($arrivalOrder) <= 2) {
                        throw new Exception("Found invalid filename: $fileName");
                    }
                    // 123
                    $arrivalOrder = array_splice($arrivalOrder, 0, 3);
                    $arrivalOrder = implode("_", $arrivalOrder);
                    echo "Found combination: $arrivalOrder\n";

                    Video::firstOrCreate([
                        'gameid' => $gameModel->id,
                        'combination' => $arrivalOrder,
                        'filename' => $fileName,
                        'duration' => $duration,
                    ]);

                }

                fclose($handle);
            }
        }
    }
}
