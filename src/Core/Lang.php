<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Fetches texts from resources/lang/<code>.php.
 *
 *   Lang::get('task.created')                         → 'Taak toegevoegd.'
 *   Lang::get('admin.demo_loaded', ['tasks' => 5])    → ':tasks' is replaced
 *   Lang::choice('logs.lines', 1)                     → logs.lines.one, otherwise .other
 *   Lang::has('activity.actions.task.moved')          → bool
 *   Lang::date($timestamp) / Lang::dateShort(...)     → date in the chosen language
 *
 * Which language is active (first one that is valid):
 *   1. the visitor's choice (cookie "lang", set by the language button)
 *   2. APP_LANG in .env
 *   3. 'nl'
 * Available languages = all files resources/lang/xx.php (two letters).
 *
 * Keys that are missing in another language fall back to Dutch (the base
 * language). A key that exists nowhere returns the key itself (and logs a
 * warning), so a page never crashes on a missing text.
 *
 * Note: the values are not escaped. Use t() or htmlspecialchars() in views.
 */
final class Lang
{
    private const DEFAULT_LOCALE = 'nl';

    /** @var array<string,mixed>|null */
    private static ?array $messages = null;

    /** @var array<string,true> keys for which a warning has already been logged */
    private static array $warned = [];

    public const COOKIE = 'lang';

    /** @var string[]|null */
    private static ?array $available = null;

    private static ?string $locale = null;

    /**
     * Active language code (see the class description for the order).
     */
    public static function locale(): string
    {
        if (self::$locale !== null) {
            return self::$locale;
        }

        $candidates = [
            (string) ($_COOKIE[self::COOKIE] ?? ''),
            function_exists('env') ? (string) env('APP_LANG', '') : '',
        ];

        foreach ($candidates as $code) {
            if (self::isAvailable($code)) {
                return self::$locale = $code;
            }
        }

        return self::$locale = self::DEFAULT_LOCALE;
    }

    /** Is this an existing language? (Also guards against free input in file paths.) */
    public static function isAvailable(string $code): bool
    {
        return preg_match('/^[a-z]{2}$/', $code) === 1 && in_array($code, self::available(), true);
    }

    /**
     * All languages that have a file, sorted by language code (a-z).
     *
     * @return string[]
     */
    public static function available(): array
    {
        if (self::$available !== null) {
            return self::$available;
        }

        $codes = [];
        foreach (glob(dirname(__DIR__, 2) . '/resources/lang/??.php') ?: [] as $file) {
            $code = basename($file, '.php');
            if (preg_match('/^[a-z]{2}$/', $code)) {
                $codes[] = $code;
            }
        }
        sort($codes);

        return self::$available = $codes ?: [self::DEFAULT_LOCALE];
    }

    /**
     * For the language picker: code => ['name' => 'Nederlands', 'short' => 'NL'],
     * alphabetical by displayed name (Deutsch, English, Español, Français, Nederlands ...).
     *
     * @return array<string,array{name:string,short:string}>
     */
    public static function languages(): array
    {
        $dir       = dirname(__DIR__, 2) . '/resources/lang/';
        $languages = [];

        foreach (self::available() as $code) {
            $meta = self::load($dir . $code . '.php')['meta'] ?? [];
            $languages[$code] = [
                'name'  => (string) ($meta['name'] ?? strtoupper($code)),
                'short' => (string) ($meta['short'] ?? strtoupper($code)),
            ];
        }

        uasort($languages, static fn(array $a, array $b): int
            => strcmp(self::sortKey($a['name']), self::sortKey($b['name'])));

        return $languages;
    }

    /** Sort key: lowercase and without accents, so "Español" sorts among the E's. */
    private static function sortKey(string $name): string
    {
        return strtolower(strtr($name, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a', 'å' => 'a', 'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ä' => 'A', 'Å' => 'A',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o', 'ø' => 'o', 'Ó' => 'O', 'Ô' => 'O', 'Ö' => 'O', 'Ø' => 'O',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'ç' => 'c', 'Ç' => 'C', 'ñ' => 'n', 'Ñ' => 'N', 'ß' => 'ss', 'ł' => 'l', 'Ł' => 'L', 'ş' => 's', 'ğ' => 'g',
        ]));
    }

    /** Value for <html lang="...">. */
    public static function htmlLang(): string
    {
        return self::get('meta.html');
    }

    /** "9 oktober 2026" / "October 9, 2026" ... (per meta.date_long and months). */
    public static function date(int|string|null $time): string
    {
        $ts = is_int($time) ? $time : strtotime((string) $time);
        if (!$ts) {
            return '';
        }

        return self::get('meta.date_long', [
            'day'   => date('j', $ts),
            'month' => self::get('months.' . (int) date('n', $ts)),
            'year'  => date('Y', $ts),
        ]);
    }

    /** Short date per meta.date_short (e.g. 31-12-2026 or 31/12/2026). */
    public static function dateShort(int|string|null $time): string
    {
        $ts = is_int($time) ? $time : strtotime((string) $time);

        return $ts ? date(self::get('meta.date_short'), $ts) : '';
    }

    /** Number with the separators of the chosen language. */
    public static function number(float|int $value, int $decimals = 0): string
    {
        return number_format($value, $decimals, self::get('meta.decimal'), self::get('meta.thousands'));
    }

    /**
     * Texts for JavaScript (group "js"), as an array ready for json_encode.
     *
     * @return array<string,string>
     */
    public static function forJs(): array
    {
        $js = self::find('js');

        return is_array($js) ? array_map('strval', $js) : [];
    }

    /**
     * @param array<string,scalar> $replace  ['name' => 'x'] replaces ':name'
     */
    public static function get(string $key, array $replace = []): string
    {
        $value = self::find($key);

        if (!is_string($value)) {
            if (!isset(self::$warned[$key])) {
                self::$warned[$key] = true;
                Logger::warning('Missing text in resources/lang: ' . $key);
            }
            return $key;
        }

        return self::replace($value, $replace);
    }

    public static function has(string $key): bool
    {
        return is_string(self::find($key));
    }

    /**
     * Singular/plural: looks up "<key>.one" for 1, otherwise "<key>.other".
     * ':count' is filled in automatically.
     */
    public static function choice(string $key, int $count, array $replace = []): string
    {
        return self::get($key . ($count === 1 ? '.one' : '.other'), $replace + ['count' => $count]);
    }

    // -----------------------------------------------------------------------

    private static function find(string $key): mixed
    {
        $node = self::messages();

        foreach (explode('.', $key) as $part) {
            if (!is_array($node) || !array_key_exists($part, $node)) {
                return null;
            }
            $node = $node[$part];
        }

        return $node;
    }

    /** @return array<string,mixed> */
    private static function messages(): array
    {
        if (self::$messages !== null) {
            return self::$messages;
        }

        $dir      = dirname(__DIR__, 2) . '/resources/lang/';
        $messages = self::load($dir . self::DEFAULT_LOCALE . '.php');

        $locale = self::locale();
        if ($locale !== self::DEFAULT_LOCALE && self::isAvailable($locale)) {
            $messages = array_replace_recursive($messages, self::load($dir . $locale . '.php'));
        }

        return self::$messages = $messages;
    }

    /** @return array<string,mixed> */
    private static function load(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }

        $data = require $file;
        return is_array($data) ? $data : [];
    }

    private static function replace(string $text, array $replace): string
    {
        if (!$replace) {
            return $text;
        }

        $pairs = [];
        foreach ($replace as $name => $value) {
            $pairs[':' . $name] = (string) $value;
        }

        return strtr($text, $pairs);
    }
}
