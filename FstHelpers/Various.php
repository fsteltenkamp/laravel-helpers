<?php

namespace App\Helpers\FstHelpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class Various
{
    /**
     * Calculates the maximum depth of a multidimensional array.
     *
     * @param array $array The array to analyze.
     * @return int The maximum depth found in the array.
     */
    public static function array_depth($array)
    {
        $max_depth = 1;

        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = self::array_depth($value) + 1;

                if ($depth > $max_depth) {
                    $max_depth = $depth;
                }
            }
        }

        return $max_depth;
    }

    /**
     * Recursively converts an object or array of objects to an array.
     *
     * @param mixed $data The object or array of objects to convert.
     * @return array The converted array.
     */
    public static function recursiveToArray($data)
    {
        // Filter for Laravel Collections and Models which have a toArray method
        if ($data instanceof Collection) {
            $data = $data->toArray();
        }
        if ($data instanceof Model) {
            $data = $data->toArray();
        }
        // Convert objects to arrays
        if (is_object($data)) {
            $data = (array)$data;
        }
        // Recursively convert arrays
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::recursiveToArray($value);
            }
        }
        return $data;
    }

    /**
     * Add a value to a collection by key.
     * Also supports dot notation due to using Arr::set.
     *
     * @param Collection $collection
     * @param string $key
     * @param mixed $value
     *
     * @return Collection
     */
    public static function addToCollection($collection, $key, $value) : Collection
    {
        // Collection to array:
        $array = $collection->toArray();
        // Add value to array:
        Arr::set($array, $key, $value);
        // Array to collection:
        return collect($array);
    }

    /**
     * Sanitize a string by removing leading/trailing whitespace, removing all whitespace, and stripping high ASCII characters.
     * Optionally convert the string to lowercase or uppercase.
     *
     * @param string $string The string to sanitize.
     * @param bool $lower Whether to convert the string to lowercase. Default is false.
     * @param bool $upper Whether to convert the string to uppercase. Default is false.
     * @return string The sanitized string.
     */
    public static function sanitizeString($string, $lower = false, $upper = false)
    {
        $string = trim($string);
        $string = preg_replace('/\s/u', '', $string);
        $string = filter_var($string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
        if ($lower && !$upper) {
            $string = strtolower($string);
        } else if ($upper && !$lower) {
            $string = strtoupper($string);
        }
        return $string;
    }

    /**
     * Check if any of the needles are in the haystack.
     *
     * @param array $needles The array of needles to check.
     * @param array $haystack The array to check against.
     * @return bool True if any of the needles are found in the haystack, false otherwise.
     */
    public static function in_array(array $needles, array $haystack)
    {
        foreach ($needles as $needle) {
            if (in_array($needle, $haystack)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Prüft ob ein array von keys in einem Array von Werten enthalten ist.
     *
     * @param array $keys     Keys die in den Werten gesucht werden.
     * @param mixed $vars     Werte die nach den Keys durchsucht werden.
     * @param bool $any       Definiert ob nur ein Key gefunden werden muss, oder alle.
     * @param bool $anyPrefix Definiert ob Keys ein TEIL eines wertes sein dürfen.
     * @return bool
     */
    public static function in_arrayExtended(array $keys, mixed $vars, bool $any = false, bool $anyPrefix = true)
    {
        if (!$any && !$anyPrefix) {
            Log::debug('Checking if all keys are literal matches', ['keys' => $keys, 'vars' => $vars]);
            foreach ($keys as $key) {
                if (!in_array($key, $vars)) {
                    // Break at the first non-match
                    Log::debug('NOT FOUND');
                    return false;
                }
            }
        }

        if (!$any && $anyPrefix) {
            Log::debug('Checking if all keys are at least a part of any value', ['keys' => $keys, 'vars' => $vars]);
            foreach ($keys as $keyIndex => $key) {
                foreach ($vars as $var) {
                    if (strpos($var, $key) !== false) {
                        unset($keys[$keyIndex]);
                    }
                }
            }
            if (count($keys) == 0) {
                Log::debug('FOUND');
                return true;
            }
        }

        if ($any && !$anyPrefix) {
            Log::debug('Checking if any key is a literal match', ['keys' => $keys, 'vars' => $vars]);
            foreach ($keys as $key) {
                if (in_array($key, $vars)) {
                    Log::debug('FOUND');
                    return true;
                }
            }
        }

        if ($any && $anyPrefix) {
            Log::debug('Checking if any key is at least a part of any value', ['keys' => $keys, 'vars' => $vars]);
            foreach ($keys as $key) {
                foreach ($vars as $var) {
                    if (strpos($var, $key) !== false) {
                        Log::debug('FOUND');
                        return true;
                    }
                }
            }
        }

        Log::debug('NOT FOUND');
        return false;
    }

    /**
     * Convert a number to its written German representation.
     *
     * Notes / Verhalten:
     * - Bei zusammengesetzten Zahlen (z.B. 21) muss "ein" statt "eins" verwendet werden: "einundzwanzig".
     * - Unterstützt negative Zahlen (Vorlauf "minus").
     * - Für große Zahlen werden Stufen wie "tausend", "Million" und "Milliarde" verwendet.
     *
     * Optionen:
     * @param mixed $num Zahl (ganze Zahl erwartet). Wenn nicht-numerisch, wird eine Fehlermeldung zurückgegeben.
     * @param bool $spaceBetween Wenn true, werden Verbindungswörter mit Leerzeichen geschrieben (z.B. "ein und zwanzig").
     * @param string|null $capitalize Optional: null oder 'none' = keine Änderung, 'first' = erstes Zeichen groß, 'all' = gesamte Zeichenkette groß.
     *
     * Rückgabe: String mit der ausgeschriebenen Zahl.
     * Beispiele:
     * - numberToWordsGerman(21) => "einundzwanzig"
     * - numberToWordsGerman(21, false, 'first') => "Einundzwanzig"
     */
    public static function numberToWordsGerman($num, $spaceBetween = false, $capitalize = null)
    {
        if (!is_numeric($num)) {
            return "Eingabe ist keine Zahl.";
        }

        // Basismapping für Zahlen (Singular/Stand-alone)
        $zahlen = [
            0 => "null", 1 => "eins", 2 => "zwei", 3 => "drei", 4 => "vier",
            5 => "fünf", 6 => "sechs", 7 => "sieben", 8 => "acht", 9 => "neun",
            10 => "zehn", 11 => "elf", 12 => "zwölf", 13 => "dreizehn", 14 => "vierzehn",
            15 => "fünfzehn", 16 => "sechzehn", 17 => "siebzehn", 18 => "achtzehn", 19 => "neunzehn",
            20 => "zwanzig", 30 => "dreißig", 40 => "vierzig", 50 => "fünfzig",
            60 => "sechzig", 70 => "siebzig", 80 => "achtzig", 90 => "neunzig"
        ];

        // Stufen größer 1000
        $stufen = [
            1000 => "tausend", 1000000 => "Million", 1000000000 => "Milliarde"
        ];

        $space = $spaceBetween ? ' ' : '';

        // Negative Zahlen
        if ($num < 0) {
            $result = "minus" . $space . self::numberToWordsGerman(abs($num), $spaceBetween, $capitalize);
            // Kapitalisierung anwenden falls gewünscht
            if ($capitalize === 'first') {
                return ucfirst($result);
            } elseif ($capitalize === 'all') {
                return strtoupper($result);
            }
            return $result;
        }

        // Convenience-Funktion: liefert die Form für Einheiten in zusammengesetzten Zahlen
        $unitForCompound = function ($n) use ($zahlen) {
            // Bei zusammengesetzten Zahlen (z.B. 21 => "einundzwanzig") wird "ein" statt "eins" benutzt.
            if ($n === 1) {
                return 'ein';
            }
            return $zahlen[$n];
        };

        // < 20: direkte Rückgabe
        if ($num < 20) {
            $result = $zahlen[$num];
            if ($capitalize === 'first') {
                return ucfirst($result);
            } elseif ($capitalize === 'all') {
                return strtoupper($result);
            }
            return $result;
        }

        // < 100: Zusammensetzung wie "einundzwanzig"
        if ($num < 100) {
            $zehner = intval($num / 10) * 10;
            $rest = $num % 10;
            if ($rest) {
                $unitWord = $unitForCompound($rest);
                $connector = $spaceBetween ? ' und ' : 'und';
                $result = $unitWord . $connector . $zahlen[$zehner];
                if ($capitalize === 'first') {
                    return ucfirst($result);
                } elseif ($capitalize === 'all') {
                    return strtoupper($result);
                }
                return $result;
            } else {
                $result = $zahlen[$zehner];
                if ($capitalize === 'first') {
                    return ucfirst($result);
                } elseif ($capitalize === 'all') {
                    return strtoupper($result);
                }
                return $result;
            }
        }

        // < 1000: Hunderter
        if ($num < 1000) {
            $hunderter = intval($num / 100);
            $rest = $num % 100;

            // "einhundert" statt "eins hundert"
            $prefix = $hunderter === 1 ? "ein" : $zahlen[$hunderter];
            $result = $prefix . "hundert" . ($rest ? $space . self::numberToWordsGerman($rest, $spaceBetween, null) : "");
            if ($capitalize === 'first') {
                return ucfirst($result);
            } elseif ($capitalize === 'all') {
                return strtoupper($result);
            }
            return $result;
        }

        // Größere Stufen (tausend, Million, Milliarde)
        foreach ($stufen as $schwelle => $name) {
            if ($num < $schwelle * 1000) {
                $basis = intval($num / $schwelle);
                $rest = $num % $schwelle;

                // Für tausend: "eintausend" statt "eine tausend". Für Millionen/Milliarden: "eine Million" (Singular)
                if ($schwelle === 1000) {
                    $prefix = $basis === 1 ? "ein" : self::numberToWordsGerman($basis, $spaceBetween, null);
                } else {
                    $prefix = $basis === 1 ? "eine" : self::numberToWordsGerman($basis, $spaceBetween, null);
                }

                $plural = ($schwelle >= 1000000 && $basis > 1) ? "en" : "";
                $result = $prefix . $space . $name . $plural . ($rest ? $space . self::numberToWordsGerman($rest, $spaceBetween, null) : "");
                if ($capitalize === 'first') {
                    return ucfirst($result);
                } elseif ($capitalize === 'all') {
                    return strtoupper($result);
                }
                return $result;
            }
        }

        return "Zahl zu groß.";
    }
}
