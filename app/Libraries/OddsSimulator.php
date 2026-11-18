<?php

namespace App\Libraries;

use PGVirtual\Core\Libraries\Math;
use PGVirtual\Core\Models\Event;

/**
 * Simulatore quote per il pannello RTP.
 *
 * Usa esclusivamente l'OddsController della libreria del gioco (non modificata),
 * confrontando le quote con RTP di default e con RTP simulati.
 * Probabilità e RTP sono espressi in universo 1.000.000, come nella libreria.
 */
class OddsSimulator
{
    public const U = 1000000;

    /** Probabilità di vittoria (in ordine di numero) di un evento reale. */
    public static function probsFromEvent(Event $event): array
    {
        $opts = $event->getOpts();
        $racers = $opts['racers'] ?? [];
        usort($racers, function ($a, $b) {
            return $a['number'] <=> $b['number'];
        });
        return array_map(function ($x) {
            return (int) $x['chance'];
        }, $racers);
    }

    /**
     * Probabilità generate con l'RNG della libreria (stesso codice di generateEvents).
     * Ritorna null se il generatore non è disponibile (python/drs).
     */
    public static function probsFromRng(string $gameName, int $numRacers): ?array
    {
        $ns = RtpConfig::gameNamespace($gameName) . '\\Libraries\\';
        $configClass = $ns . 'ProbConfigs';
        $probClass = $ns . 'Probabilities';
        if (!class_exists($configClass) || !class_exists($probClass)) {
            return null;
        }
        // Probabilities::generate() in caso di errore fa echo: non deve finire nella pagina
        ob_start();
        try {
            $probs = (new $probClass(new $configClass($numRacers)))->generate();
        } catch (\Throwable) {
            $probs = null;
        } finally {
            ob_end_clean();
        }
        if (!is_array($probs) || count($probs) !== $numRacers) {
            return null;
        }
        return array_map('intval', $probs);
    }

    /**
     * Probabilità inserite a mano in percentuale ("30, 25, 18, ...").
     *
     * @return array|string probabilità oppure messaggio d'errore
     */
    public static function probsFromManual(string $raw, int $numRacers)
    {
        // separatori: spazio o ";". La virgola è decimale, salvo lista tutta attaccata "30,25,18,..."
        $parts = preg_split('/[;\s]+/', trim($raw), -1, PREG_SPLIT_NO_EMPTY);
        $parts = array_values(array_filter(array_map(fn ($t) => rtrim($t, ','), $parts), 'strlen'));
        if (count($parts) === 1 && substr_count($parts[0], ',') > 1) {
            $parts = array_map('trim', explode(',', $parts[0]));
        }
        if (count($parts) !== $numRacers) {
            return "Servono {$numRacers} probabilità, una per corridore (trovate " . count($parts) . ')';
        }
        $probs = [];
        foreach ($parts as $p) {
            $p = str_replace(',', '.', $p);
            if (!is_numeric($p) || (float) $p <= 0) {
                return "Valore non valido: {$p}";
            }
            $probs[] = (int) round(((float) $p) * self::U / 100);
        }
        $sum = array_sum($probs);
        if (abs($sum - self::U) > 1000) { // tolleranza 0,1%
            return sprintf('La somma deve essere 100%% (ora %s%%)', number_format($sum / 10000, 2, ',', ''));
        }
        // come la libreria: il resto va sull'ultimo corridore
        $probs[$numRacers - 1] += self::U - $sum;
        return $probs;
    }

    /**
     * @param array $winnerProbs probabilità di vittoria per corridore (indice 0 = n.1)
     * @param array $rtpOptions  opzioni rtp* simulate (vuoto = default)
     */
    public static function simulate(string $gameName, array $winnerProbs, array $rtpOptions): array
    {
        $class = RtpConfig::oddsControllerClass($gameName);
        $marketsClass = RtpConfig::marketsClass($gameName);
        $defaults = RtpConfig::defaults($gameName);
        $simRtp = array_merge($defaults, RtpConfig::fromOddsOptions($rtpOptions));

        $props = (new \ReflectionClass($class))->getDefaultProperties();
        $limits = ['min' => (float) ($props['minOdd'] ?? 1.01), 'max' => (float) ($props['maxOdd'] ?? 0)];

        $def = new $class(['winnerProbs' => $winnerProbs]);
        $sim = new $class(array_merge(['winnerProbs' => $winnerProbs], $rtpOptions));

        $markets = [];
        foreach ($marketsClass::MARKETS as $market) {
            $rows = self::rows($market, count($winnerProbs), $def, $sim, $winnerProbs);
            $markets[$market] = [
                'rtpDefault' => $defaults[$market] ?? null,
                'rtpSimulated' => $simRtp[$market] ?? null,
                'rows' => $rows,
                'summary' => self::summary($rows, $limits),
            ];
        }

        return ['markets' => $markets, 'limits' => $limits, 'winnerProbs' => $winnerProbs];
    }

    private static function row(string $label, $prob, $oddDef, $oddSim): array
    {
        $prob = (float) $prob;
        $oddDef = (float) $oddDef;
        $oddSim = (float) $oddSim;
        return [
            'label' => $label,
            'prob' => $prob,
            'oddDefault' => $oddDef,
            'oddSimulated' => $oddSim,
            'rtpDefault' => $prob * $oddDef, // universo 1.000.000
            'rtpSimulated' => $prob * $oddSim,
        ];
    }

    private static function rows(string $market, int $n, $def, $sim, array $winnerProbs): array
    {
        $rows = [];
        switch ($market) {
            case 'winner':
                $d = $def->getWinnerOdds();
                $s = $sim->getWinnerOdds();
                foreach (range(1, $n) as $r) {
                    $rows[] = self::row((string) $r, $winnerProbs[$r - 1], $d[$r], $s[$r]);
                }
                break;
            case 'placed':
                $d = $def->getPlacedOdds();
                $s = $sim->getPlacedOdds();
                foreach (range(1, $n) as $r) {
                    $rows[] = self::row((string) $r, $def->placedProbs[$r - 1], $d[$r], $s[$r]);
                }
                break;
            case 'show':
                $p = $def->getShowProbs();
                $d = $def->getShowOdds();
                $s = $sim->getShowOdds();
                foreach (range(1, $n) as $r) {
                    $rows[] = self::row((string) $r, $p[$r - 1], $d[$r], $s[$r]);
                }
                break;
            case 'exacta':
                $d = $def->getExactaOdds();
                $s = $sim->getExactaOdds();
                foreach (Math::getExactaPermutations($n) as [$a, $b]) {
                    $rows[] = self::row("{$a}-{$b}", $def->exactaProbs[$a][$b], $d[$a][$b], $s[$a][$b]);
                }
                break;
            case 'quinella':
                $d = $def->getQuinellaOdds();
                $s = $sim->getQuinellaOdds();
                foreach (Math::getQuinellaCombinations($n) as [$a, $b]) {
                    $rows[] = self::row("{$a}+{$b}", $def->quinellaProbs[$a][$b], $d[$a][$b], $s[$a][$b]);
                }
                break;
            case 'trifecta':
                $d = $def->getTrifectaOdds();
                $s = $sim->getTrifectaOdds();
                foreach (Math::getTrifectaPermutations($n) as [$a, $b, $c]) {
                    $rows[] = self::row("{$a}-{$b}-{$c}", $def->trifectaProbs[$a][$b][$c], $d[$a][$b][$c], $s[$a][$b][$c]);
                }
                break;
            case 'boxedtrifecta':
                $d = $def->getBoxedtrifectaOdds();
                $s = $sim->getBoxedtrifectaOdds();
                foreach (Math::getBoxedTrifectaCombinations($n) as [$a, $b, $c]) {
                    $rows[] = self::row("{$a}+{$b}+{$c}", $def->boxedtrifectaProbs[$a][$b][$c], $d[$a][$b][$c], $s[$a][$b][$c]);
                }
                break;
            case 'evenodd':
                // come IsibetLib: pari = corridori 2,4,6..., dispari = 1,3,5...
                $even = array_sum(array_map(fn ($i) => $winnerProbs[$i], range(1, $n - 1, 2)));
                $odd = array_sum(array_map(fn ($i) => $winnerProbs[$i], range(0, $n - 1, 2)));
                $d = $def->getEvenoddOdds();
                $s = $sim->getEvenoddOdds();
                $rows[] = self::row('Pari', $even, $d['even'], $s['even']);
                $rows[] = self::row('Dispari', $odd, $d['odd'], $s['odd']);
                break;
            case 'underover':
                $half = intdiv($n, 2);
                $under = array_sum(array_slice($winnerProbs, 0, $half));
                $over = array_sum(array_slice($winnerProbs, $half));
                $d = $def->getUnderOverOdds();
                $s = $sim->getUnderOverOdds();
                $rows[] = self::row('Under (1-' . $half . ')', $under, $d['under'], $s['under']);
                $rows[] = self::row('Over (' . ($half + 1) . '-' . $n . ')', $over, $d['over'], $s['over']);
                break;
        }
        return $rows;
    }

    private static function summary(array $rows, array $limits): array
    {
        if (!$rows) {
            return [];
        }
        $out = [];
        foreach (['Default', 'Simulated'] as $k) {
            $rtps = array_column($rows, 'rtp' . $k);
            $odds = array_column($rows, 'odd' . $k);
            $out[$k] = [
                'avg' => array_sum($rtps) / count($rtps),
                'min' => min($rtps),
                'max' => max($rtps),
                'cappedMin' => count(array_filter($odds, fn ($o) => $o <= $limits['min'])),
                'cappedMax' => $limits['max'] > 0 ? count(array_filter($odds, fn ($o) => $o >= $limits['max'])) : 0,
            ];
        }
        $out['probSum'] = array_sum(array_column($rows, 'prob'));
        $out['count'] = count($rows);
        return $out;
    }
}
