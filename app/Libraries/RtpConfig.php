<?php

namespace App\Libraries;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PGVirtual\Core\Models\Configuration;
use PGVirtual\Core\Models\Event;
use PGVirtual\Core\Models\Game;
use App\Models\EventRtp;

/**
 * Gestione RTP per gioco, lato applicazione.
 *
 * Le librerie certificate (sogei-core / sogei-dogs / sogei-horses / sogei-harness)
 * NON vengono modificate: il loro OddsController accetta già le opzioni rtp*
 * nel costruttore, qui ci limitiamo a calcolarle e passarle.
 *
 * Valori espressi in "universo" 1.000.000 (es. 892000 = 89,20%), come nella libreria.
 * Salvati nella tabella `configurations` con key RTP_<MARKET>, game_id valorizzato,
 * operator_id e channel_id NULL.
 */
class RtpConfig
{
    public const KEY_PREFIX = 'RTP_';
    public const UNIVERSE_SIZE = 1000000;

    /* Limiti imposti dal costruttore di OddsController nelle librerie */
    public const MAX = 900000;
    public const MIN_MAIN = 800000;
    public const MIN_EXOTIC = 600000;

    /* market (Markets::MARKET_*) => proprietà di OddsController */
    public const MARKET_PROPERTIES = [
        'winner' => 'rtpWinner',
        'placed' => 'rtpPlaced',
        'show' => 'rtpShow',
        'exacta' => 'rtpExacta',
        'quinella' => 'rtpQuinella',
        'trifecta' => 'rtpTrifecta',
        'boxedtrifecta' => 'rtpBoxedtrifecta',
        'evenodd' => 'rtpEvenOdd',
        'underover' => 'rtpUnderOver',
    ];

    public const EXOTIC_MARKETS = ['exacta', 'quinella', 'trifecta', 'boxedtrifecta'];

    /** Nomi dei mercati mostrati nel pannello RTP e nel simulatore: i nomi originali dei mercati. */
    public const MARKET_LABELS = [
        'winner' => 'Winner',
        'placed' => 'Placed',
        'show' => 'Show',
        'exacta' => 'Exacta',
        'quinella' => 'Quinella',
        'trifecta' => 'Trifecta',
        'boxedtrifecta' => 'Boxedtrifecta',
        'evenodd' => 'Evenodd',
        'underover' => 'Underover',
    ];

    public static function gameNamespace(string $gameName): string
    {
        return 'PGVirtual\\Game' . ucfirst(strtolower($gameName));
    }

    public static function oddsControllerClass(string $gameName): string
    {
        return self::gameNamespace($gameName) . '\\Controllers\\OddsController';
    }

    public static function marketsClass(string $gameName): string
    {
        return self::gameNamespace($gameName) . '\\Libraries\\Markets';
    }

    /**
     * Valori di default letti direttamente dalla libreria (reflection), così restano
     * sempre allineati alla versione depositata.
     *
     * @return array<string,int> market => rtp
     */
    public static function defaults(string $gameName): array
    {
        $class = self::oddsControllerClass($gameName);
        if (!class_exists($class)) {
            return [];
        }
        $props = (new \ReflectionClass($class))->getDefaultProperties();
        $out = [];
        foreach (self::MARKET_PROPERTIES as $market => $prop) {
            if (array_key_exists($prop, $props)) {
                $out[$market] = (int) $props[$prop];
            }
        }
        return $out;
    }

    /** @return array{0:int,1:int} [min, max] */
    public static function bounds(string $market): array
    {
        return [in_array($market, self::EXOTIC_MARKETS, true) ? self::MIN_EXOTIC : self::MIN_MAIN, self::MAX];
    }

    public static function isValid(string $market, int $value): bool
    {
        if (!array_key_exists($market, self::MARKET_PROPERTIES)) {
            return false;
        }
        [$min, $max] = self::bounds($market);
        return $value >= $min && $value <= $max;
    }

    /**
     * Interpreta un RTP in percentuale ("89,2" / "89.20"). Stringa vuota = default.
     *
     * @return array{0:?int,1:?string} [valore in universo 1.000.000 | null, errore | null]
     */
    public static function parsePercent(string $market, $raw): array
    {
        $raw = trim(str_replace(',', '.', (string) $raw));
        if ($raw === '') {
            return [null, null];
        }
        if (!preg_match('/^\d{1,2}(\.\d{1,2})?$/', $raw)) {
            return [null, 'Formato non valido (es. 89.20)'];
        }
        $units = (int) round(((float) $raw) * self::UNIVERSE_SIZE / 100);
        if (!self::isValid($market, $units)) {
            [$min, $max] = self::bounds($market);
            return [null, 'Deve essere tra ' . self::formatPercent($min) . ' e ' . self::formatPercent($max)];
        }
        return [$units, null];
    }

    public static function formatPercent($units, int $decimals = 2): string
    {
        return number_format($units / 10000, $decimals, ',', '') . '%';
    }

    public static function configKey(string $market): string
    {
        return self::KEY_PREFIX . strtoupper($market);
    }

    private static function baseQuery(int $gameId)
    {
        return Configuration::where('game_id', $gameId)
            ->whereNull('channel_id')
            ->whereNull('operator_id')
            ->whereIn('key', array_map([self::class, 'configKey'], array_keys(self::MARKET_PROPERTIES)));
    }

    /**
     * Override impostati dal pannello (solo quelli validi).
     *
     * @return array<string,int> market => rtp
     */
    public static function overrides(int $gameId): array
    {
        $byKey = [];
        foreach (array_keys(self::MARKET_PROPERTIES) as $market) {
            $byKey[self::configKey($market)] = $market;
        }

        $out = [];
        foreach (self::baseQuery($gameId)->get() as $row) {
            $market = $byKey[$row->key];
            $value = filter_var($row->value, FILTER_VALIDATE_INT);
            if ($value === false || !self::isValid($market, $value)) {
                // Un valore fuori range farebbe esplodere il costruttore della libreria:
                // lo ignoriamo e si usa il default.
                Log::warning("RTP non valido ignorato: game_id={$gameId} {$row->key}={$row->value}");
                continue;
            }
            $out[$market] = $value;
        }
        return $out;
    }

    /**
     * Salva gli override di un gioco. $values: market => int|null (null = torna al default).
     */
    public const VERSION_CACHE_KEY = 'rtp:version';

    /** Cambia a ogni salvataggio: invalida la cache delle quote del viewer. */
    public static function version(): string
    {
        return (string) Cache::get(self::VERSION_CACHE_KEY, '0');
    }

    public static function save(int $gameId, array $values): void
    {
        foreach ($values as $market => $value) {
            $key = self::configKey($market);
            $query = Configuration::where('game_id', $gameId)->whereNull('channel_id')->whereNull('operator_id')->where('key', $key);
            if ($value === null) {
                $query->delete();
                continue;
            }
            if (!self::isValid($market, (int) $value)) {
                throw new \InvalidArgumentException("RTP fuori range per {$market}: {$value}");
            }
            $row = $query->first() ?? new Configuration();
            $row->key = $key;
            $row->value = (string) (int) $value;
            $row->game_id = $gameId;
            $row->channel_id = null;
            $row->operator_id = null;
            $row->save();
        }
        Cache::forever(self::VERSION_CACHE_KEY, str_replace('.', '', (string) microtime(true)));
    }

    /**
     * Converte market => rtp in opzioni per il costruttore di OddsController.
     *
     * @return array<string,int>
     */
    public static function toOddsOptions(array $marketValues): array
    {
        $out = [];
        foreach ($marketValues as $market => $value) {
            if (isset(self::MARKET_PROPERTIES[$market]) && self::isValid($market, (int) $value)) {
                $out[self::MARKET_PROPERTIES[$market]] = (int) $value;
            }
        }
        return $out;
    }

    /** Opzioni rtp* correnti per un gioco (vuoto = default della libreria). */
    public static function oddsOptions(int $gameId): array
    {
        return self::toOddsOptions(self::overrides($gameId));
    }

    /**
     * Opzioni rtp* con cui un evento è (o sarà) quotato:
     *  - evento già inviato a Isibet con snapshot  -> RTP dello snapshot
     *  - evento già inviato senza snapshot         -> default (inviato prima del pannello)
     *  - evento non ancora inviato                 -> RTP correnti del pannello
     */
    public static function oddsOptionsForEvent(Event $event, int $gameId): array
    {
        $snapshot = EventRtp::where('event_id', $event->id)->first();
        if ($snapshot) {
            return self::toOddsOptions($snapshot->getRtp());
        }
        if (!is_null($event->sent_on)) {
            return [];
        }
        return self::oddsOptions($gameId);
    }

    /** Inverso di toOddsOptions: rtpWinner => x  diventa  winner => x */
    public static function fromOddsOptions(array $options): array
    {
        $flip = array_flip(self::MARKET_PROPERTIES);
        $out = [];
        foreach ($options as $prop => $value) {
            if (isset($flip[$prop])) {
                $out[$flip[$prop]] = (int) $value;
            }
        }
        return $out;
    }

    public static function games()
    {
        return Game::orderBy('id')->get();
    }
}
