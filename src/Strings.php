<?php

declare(strict_types=1);

namespace Atomastic\Strings;

use InvalidArgumentException;

use function abs;
use function array_reverse;
use function array_shift;
use function array_walk;
use function base64_decode;
use function base64_encode;
use function ctype_lower;
use function explode;
use function filter_var;
use function floatval;
use function hash;
use function hash_algos;
use function implode;
use function in_array;
use function intval;
use function is_array;
use function is_numeric;
use function is_object;
use function json_decode;
use function json_last_error;
use function lcfirst;
use function ltrim;
use function mb_convert_case;
use function mb_ereg_match;
use function mb_internal_encoding;
use function mb_strimwidth;
use function mb_stripos;
use function mb_strlen;
use function mb_strpos;
use function mb_strripos;
use function mb_strrpos;
use function mb_strtolower;
use function mb_strtoupper;
use function mb_strwidth;
use function mb_substr;
use function mb_substr_count;
use function method_exists;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function random_int;
use function range;
use function rtrim;
use function shuffle;
use function similar_text;
use function str_pad;
use function str_replace;
use function str_word_count;
use function strncmp;
use function strpos;
use function strrpos;
use function strval;
use function substr_replace;
use function trim;
use function ucwords;
use function unserialize;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;
use const JSON_ERROR_NONE;
use const MB_CASE_TITLE;
use const STR_PAD_BOTH;
use const STR_PAD_LEFT;
use const STR_PAD_RIGHT;

class Strings
{
    /**
     * The underlying string value.
     *
     * @var string
     */
    protected $string;

    /**
     * The cache for words.
     *
     * @var array
     */
    protected $cache = [];

    /**
     * The string's encoding, which should be one of the mbstring module's
     * supported encodings.
     *
     * @var string
     */
     protected $encoding;

    /**
     * Initializes a Strings object and assigns both $string and $encoding properties
     * the supplied values. $string is cast to a string prior to assignment. Throws
     * an InvalidArgumentException if the first argument is an array or object
     * without a __toString method.
     *
     * @param mixed  $string   Value to modify, after being cast to string. Default: ''
     * @param string $encoding The character encoding. Default: UTF-8
     *
     * @return void
     */
    public function __construct($string = '', string $encoding = 'UTF-8')
    {
        if (is_array($string)) {
            throw new InvalidArgumentException(
                'Passed value cannot be an array'
            );
        }

        if (
            is_object($string)
            &&
            ! method_exists($string, '__toString')
        ) {
            throw new InvalidArgumentException(
                'Passed object must have a __toString method'
            );
        }

        if ($encoding === null) {
            $this->encoding = mb_internal_encoding();
        } else {
            $this->encoding = (string) $encoding;
        }

        $this->string = (string) $string;
    }

    /**
     * Returns the value in $string.
     */
    public function __toString()
    {
        return (string) $this->string;
    }

    /**
     * Create a new stringable object from the given string.
     *
     * Initializes a Strings object and assigns both $string and $encoding properties
     * the supplied values. $string is cast to a string prior to assignment. Throws
     * an InvalidArgumentException if the first argument is an array or object
     * without a __toString method.
     *
     * @param mixed  $string   Value to modify, after being cast to string. Default: ''
     * @param string $encoding The character encoding. Default: UTF-8
     */
    public static function create($string = '', string $encoding = 'UTF-8'): self
    {
        return new Strings($string, $encoding);
    }

    /**
     * Removes any leading and traling slashes from a string.
     */
    public function trimSlashes(): self
    {
        $this->string = (string) $this->trim('/');

        return $this;
    }

    /**
     * Reduces multiple slashes in a string to single slashes.
     */
    public function reduceSlashes(): self
    {
        $this->string = preg_replace('#(?<!:)//+#', '/', $this->string);

        return $this;
    }

    /**
     * Removes single and double quotes from a string.
     */
    public function stripQuotes(): self
    {
        $this->string = str_replace(['"', "'"], '', $this->string);

        return $this;
    }

    /**
     * Convert single and double quotes to entities.
     *
     * @param  string $string String with single and double quotes
     */
    public function quotesToEntities(): self
    {
        $this->string = str_replace(["\'", '"', "'", '"'], ['&#39;', '&quot;', '&#39;', '&quot;'], $this->string);

        return $this;
    }

    /**
     * Standardize line endings to unix-like.
     */
    public function normalizeNewLines(): self
    {
        $this->string = str_replace(["\r\n", "\r"], "\n", $this->string);

        return $this;
    }

    /**
     * Normalize white-spaces to a single space.
     */
    public function normalizeSpaces(): self
    {
        $this->string = preg_replace('/\s+/', ' ', $this->string);

        return $this;
    }

    /**
     * Creates a random string of characters.
     *
     * @param  int    $length   The number of characters. Default is 16
     * @param  string $keyspace The keyspace
     */
    public function random(int $length = 64, string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): self
    {
        if ($length <= 0) {
            $length = 1;
        }

        $pieces = [];
        $max    = static::create($keyspace, '8bit')->length() - 1;

        for ($i = 0; $i < $length; ++$i) {
            $pieces[] = $keyspace[random_int(0, $max)];
        }

        $this->string = implode('', $pieces);

        return $this;
    }

    /**
     * Add's _1 to a string or increment the ending number to allow _2, _3, etc.
     *
     * @param  int    $first     Start with
     * @param  string $separator Separator
     */
    public function increment(int $first = 1, string $separator = '_'): self
    {
        preg_match('/(.+)' . $separator . '([0-9]+)$/', $this->string, $match);

        $this->string = isset($match[2]) ? $match[1] . $separator . ($match[2] + 1) : $this->string . $separator . $first;

        return $this;
    }

    /**
     * Returns a repeated string given a multiplier.
     *
     * @param int $multiplier The number of times to repeat the string.
     */
    public function repeat(int $multiplier): self
    {
        $this->string = str_repeat($this->string, $multiplier);

        return $this;
    }

    /**
     * Limit the number of characters in a string.
     *
     * @param  int    $limit  Limit of characters
     * @param  string $append Text to append to the string IF it gets truncated
     */
    public function limit(int $limit = 100, string $append = '...'): self
    {
        if (mb_strwidth($this->string, 'UTF-8') <= $limit) {
            $this->string = $this->string;
        }

        $this->string = static::create(mb_strimwidth($this->string, 0, $limit, '', $this->encoding), $this->encoding)->trimRight() . $append;

        return $this;
    }

    /**
     * Convert the given string to lower-case.
     */
    public function lower(): self
    {
        $this->string = mb_strtolower($this->string, $this->encoding);

        return $this;
    }

    /**
     * Convert the given string to upper-case.
     */
    public function upper(): self
    {
        $this->string = mb_strtoupper($this->string, $this->encoding);

        return $this;
    }

    /**
     * Convert a string to studly caps case.
     */
    public function studly(): self
    {
        $key = $this->string;

        if (isset($this->cache['studly'][$key])) {
            return $this->cache['studly'][$key];
        }

        $string = ucwords(str_replace(['-', '_'], ' ', $this->string));

        $this->string = $this->cache['studly'][$key] = str_replace(' ', '', $string);

        return $this;
    }

    /**
     * Convert a string to snake case.
     *
     * @param  string $delimiter Delimeter
     */
    public function snake(string $delimiter = '_'): self
    {
        $key = $this->string;

        if (isset($this->cache['snake'][$key][$delimiter])) {
            return $this->cache['snake'][$key][$delimiter];
        }

        if (! ctype_lower($this->string)) {
            $string = preg_replace('/\s+/u', '', ucwords($this->string));
            $string = static::create(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $string), $this->encoding)->lower();
        }

        $this->string = $this->cache['snake'][$key][$delimiter] = $string;

        return $this;
    }

    /**
     * Convert a string to camel case.
     */
    public function camel(): self
    {
        if (isset($this->cache['camel'][$this->string])) {
            return $this->cache['camel'][$this->string];
        }

        $this->string = $this->cache['camel'][$this->string] = lcfirst((string) static::create($this->string, $this->encoding)->studly());

        return $this;
    }

    /**
     * Convert a string to kebab case.
     */
    public function kebab(): self
    {
        $this->string = static::create($this->string, $this->encoding)->snake('-');

        return $this;
    }

    /**
     * Limit the number of words in a string.
     *
     * @param  int    $words  Words limit
     * @param  string $append Text to append to the string IF it gets truncated
     */
    public function words(int $words = 100, string $append = '...'): self
    {
        preg_match('/^\s*+(?:\S++\s*+){1,' . $words . '}/u', $this->string, $matches);

        if (! isset($matches[0]) || static::create($this->string, $this->encoding)->length() === static::create($matches[0], $this->encoding)->length()) {
            $this->string = $this->string;
        }

        $this->string = static::create($matches[0], $this->encoding)->trimRight() . $append;

        return $this;
    }

    /**
     * Returns the length of the string, analog to length().
     */
    public function count(): int
    {
        return $this->length();
    }

    /**
     * Returns the number of occurrences of $substring in the given string.
     * By default, the comparison is case-sensitive, but can be made insensitive
     * by setting $caseSensitive to false.
     *
     * @param  string $substring     The substring to search.
     * @param  bool   $caseSensitive Whether or not to enforce case-sensitivity. Default is true.
     */
    public function countSubString(string $substring, bool $caseSensitive = true): int
    {
        if ($caseSensitive) {
            return mb_substr_count($this->string, $substring);
        }

        return mb_substr_count(
            (string) static::create($this->string, $this->encoding)->lower(),
            (string) static::create($substring, $this->encoding)->lower()
        );
    }

    /**
     * Return information about words used in a string
     *
     * @param  int    $format   Specify the return value of this function. The current supported values are:
     *                          0 - returns the number of words found
     *                          1 - returns an array containing all the words found inside the string
     *                          2 - returns an associative array, where the key is the numeric position of the word inside the string and the value is the actual word itself
     * @param  string $charlist A list of additional characters which will be considered as 'word'
     */
    public function countWords(int $format = 0, string $charlist = '')
    {
        return str_word_count($this->string, $format, $charlist);
    }

    /**
     * Determine if a given string contains a given substring.
     *
     * @param  string|string[] $needles       The string to find in haystack.
     * @param  bool            $caseSensitive Whether or not to enforce case-sensitivity. Default is true.
     */
    public function contains($needles, bool $caseSensitive = true): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && (bool) static::create($this->string, $this->encoding)->indexOf($needle, 0, $caseSensitive) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a given string contains all array values.
     *
     * @param  string[] $needles       The array of strings to find in haystack.
     * @param  bool     $caseSensitive Whether or not to enforce case-sensitivity. Default is true.
     */
    public function containsAll(array $needles, bool $caseSensitive = true): bool
    {
        foreach ($needles as $needle) {
            if (! static::create($this->string, $this->encoding)->contains($needle, $caseSensitive)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if a given string contains any of array values.
     *
     * @param  string   $haystack      The string being checked.
     * @param  string[] $needles       The array of strings to find in haystack.
     * @param  bool     $caseSensitive Whether or not to enforce case-sensitivity. Default is true.
     */
    public function containsAny(array $needles, bool $caseSensitive = true): bool
    {
        foreach ($needles as $needle) {
            if (static::create($this->string, $this->encoding)->contains($needle, $caseSensitive)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Converts the first character of a string to upper case
     * and leaves the other characters unchanged.
     */
    public function ucfirst(): self
    {
        $this->string = static::create(static::create($this->string, $this->encoding)->substr(0, 1))->upper() . static::create($this->string, $this->encoding)->substr(1);

        return $this;
    }

    /**
     * Converts the first character of every word of string to upper case and the others to lower case.
     */
    public function capitalize(): self
    {
        $this->string = mb_convert_case($this->string, MB_CASE_TITLE, $this->encoding);

        return $this;
    }

    /**
     * Return the length of the given string.
     */
    public function length(): int
    {
        if ($this->encoding) {
            $result = mb_strlen($this->string, $this->encoding);
        }

        $result = mb_strlen($this->string);

        return $result;
    }

    /**
     * Returns the portion of string specified by the start and length parameters.
     *
     * @param  int      $start  If start is non-negative, the returned string will
     *                          start at the start'th position in $string, counting from zero.
     *                          For instance, in the string 'abcdef', the character at position
     *                          0 is 'a', the character at position 2 is 'c', and so forth.
     * @param  int|null $length Maximum number of characters to use from string.
     *                          If omitted or NULL is passed, extract all characters to the end of the string.
     */
    public function substr(int $start, ?int $length = null): self
    {
        $this->string = mb_substr($this->string, $start, $length, $this->encoding);

        return $this;
    }

    /**
     * Returns the index of the first occurrence of $needle in the string,
     * and false if not found. Accepts an optional offset from which to begin
     * the search.
     *
     * @param int|string $needle        The string to find in haystack.
     * @param int        $offset        The search offset. If it is not specified, 0 is used.
     * @param bool       $caseSensitive Whether or not to enforce case-sensitivity. Default is true.
     */
    public function indexOf($needle, int $offset = 0, bool $caseSensitive = true)
    {
        if ($needle === '' || $this->string === '') {
            return false;
        }

        if ($caseSensitive) {
            return mb_strpos((string) $this->string, $needle, $offset, $this->encoding);
        }

        return mb_stripos((string) $this->string, $needle, $offset, $this->encoding);
    }

    /**
     * Returns the index of the last occurrence of $needle in the string, and false if not found.
     * Accepts an optional $offset from which to begin the search. Offsets may be negative to
     * count from the last character in the string.
     *
     * @param int|string $needle        The string to find in haystack.
     * @param int        $offset        The search offset. If it is not specified, 0 is used.
     * @param bool       $caseSensitive Whether or not to enforce case-sensitivity. Default is true.
     */
    public function indexOfLast(string $needle, int $offset = 0, bool $caseSensitive = true)
    {
        if ($needle === '' || $this->string === '') {
            return false;
        }

        $max_length = static::create($this->string, $this->encoding)->length();

        if ($offset < 0) {
            $offset = $max_length - (int) abs($offset);
        }

        if ($offset > $max_length || $offset < 0) {
            return false;
        }

        if ($caseSensitive) {
            return mb_strrpos((string) $this->string, $needle, $offset, $this->encoding);
        }

        return mb_strripos((string) $this->string, $needle, $offset, $this->encoding);
    }

    /**
     * Strip whitespace (or other characters) from the beginning and end of a string.
     *
     * @param string $character_mask Optionally, the stripped characters can also be
     *                               specified using the character_mask parameter..
     */
    public function trim(string $character_mask = " \t\n\r\0\x0B"): self
    {
        $this->string = trim($this->string, $character_mask);

        return $this;
    }

    /**
     * Strip whitespace (or other characters) from the beginning of a string.
     *
     * @param string $character_mask Optionally, the stripped characters can also be
     *                               specified using the character_mask parameter..
     */
    public function trimLeft(string $character_mask = " \t\n\r\0\x0B"): self
    {
        $this->string = ltrim($this->string, $character_mask);

        return $this;
    }

    /**
     * Strip whitespace (or other characters) from the end of a string.
     *
     * @param string $character_mask Optionally, the stripped characters can also be
     *                               specified using the character_mask parameter..
     */
    public function trimRight(string $character_mask = " \t\n\r\0\x0B"): self
    {
        $this->string = rtrim($this->string, $character_mask);

        return $this;
    }

    /**
     * Reverses string.
     */
    public function reverse(): self
    {
        $result = '';

        for ($i = static::create($this->string, $this->encoding)->length(); $i >= 0; $i--) {
            $result .= (string) static::create($this->string, $this->encoding)->substr($i, 1);
        }

        $this->string = $result;

        return $this;
    }

    /**
     * Get array of segments from a string based on a delimiter.
     *
     * @param string $delimiter Delimeter
     */
    public function segments(string $delimiter = ' '): array
    {
        return explode($delimiter, $this->string);
    }

    /**
     * Get a segment from a string based on a delimiter.
     * Returns an empty string when the offset doesn't exist.
     * Use a negative index to start counting from the last element.
     *
     * @param int    $index     Index
     * @param string $delimiter Delimeter
     */
    public function segment(int $index, string $delimiter = ' '): self
    {
        $segments = explode($delimiter, $this->string);

        if ($index < 0) {
            $segments = array_reverse($segments);
            $index    = abs($index) - 1;
        }

        $this->string = $segments[$index] ?? '';

        return $this;
    }

    /**
     * Get the first segment from a string based on a delimiter.
     *
     * @param string $delimiter Delimeter
     */
    public function firstSegment(string $delimiter = ' '): self
    {
        $this->string = (string) $this->segment(0, $delimiter);

        return $this;
    }

    /**
     * Get the last segment from a string based on a delimiter.
     *
     * @param string $string    String
     * @param string $delimiter Delimeter
     */
    public function lastSegment(string $delimiter = ' '): self
    {
        $this->string = (string) $this->segment(-1, $delimiter);

        return $this;
    }

    /**
     * Get the portion of a string between two given values.
     *
     * @param  string $from From
     * @param  string $to   To
     */
    public function between(string $from, string $to): self
    {
        if ($from === '' || $to === '') {
            $this->string = $this->string;
        } else {
            $this->string = static::create((string) static::create($this->string, $this->encoding)->after($from), $this->encoding)->beforeLast($to);
        }

        return $this;
    }

    /**
     * Get the portion of a string before the first occurrence of a given value.
     *
     * @param string $search Search
     */
    public function before(string $search): self
    {
        $this->string = $search === '' ? $this->string : explode($search, $this->string)[0];

        return $this;
    }

    /**
     * Get the portion of a string before the last occurrence of a given value.
     *
     * @param string $search Search
     */
    public function beforeLast(string $search): self
    {
        $position = mb_strrpos($this->string, $search);

        if ($position === false) {
            $this->string = $this->string;
        } else {
            $this->string = (string) static::create($this->string, $this->encoding)->substr(0, $position);
        }

        return $this;
    }

    /**
     * Return the remainder of a string after the first occurrence of a given value.
     *
     * @param string $search Search
     */
    public function after(string $search): self
    {
        $this->string = $search === '' ? $this->string : array_reverse(explode($search, $this->string, 2))[0];

        return $this;
    }

    /**
     * Return the remainder of a string after the last occurrence of a given value.
     *
     * @param string $search Search
     */
    public function afterLast(string $search): self
    {
        $position = mb_strrpos($this->string, (string) $search);

        if ($position === false) {
            $this->string = $this->string;
        } else {
            $this->string = (string) $this->substr($position + static::create($search, $this->encoding)->length());
        }

        return $this;
    }

    /**
     * Pad both sides of a string with another.
     *
     * @param  int    $length If the value of pad_length is negative, less than, or equal to the length of the input string, no padding takes place, and input will be returned.
     * @param  string $pad    The pad string may be truncated if the required number of padding characters can't be evenly divided by the pad_string's length.
     */
    public function padBoth(int $length, string $pad = ' '): self
    {
        $this->string = str_pad($this->string, $length, $pad, STR_PAD_BOTH);

        return $this;
    }

    /**
     * Pad the left side of a string with another.
     *
     * @param  int    $length If the value of pad_length is negative, less than, or equal to the length of the input string, no padding takes place, and input will be returned.
     * @param  string $pad    The pad string may be truncated if the required number of padding characters can't be evenly divided by the pad_string's length.
     */
    public function padLeft(int $length, string $pad = ' '): self
    {
        $this->string = str_pad($this->string, $length, $pad, STR_PAD_LEFT);

        return $this;
    }

    /**
     * Pad the right side of a string with another.
     *
     * @param  int    $length If the value of pad_length is negative, less than, or equal to the length of the input string, no padding takes place, and input will be returned.
     * @param  string $pad    The pad string may be truncated if the required number of padding characters can't be evenly divided by the pad_string's length.
     */
    public function padRight(int $length, string $pad = ' '): self
    {
        $this->string = str_pad($this->string, $length, $pad, STR_PAD_RIGHT);

        return $this;
    }

    /**
     * Strip all whitespaces from the given string.
     */
    public function stripSpaces(): self
    {
        $this->string = preg_replace('/\s+/', '', $this->string);

        return $this;
    }

    /**
     * Replace a given value in the string sequentially with an array.
     *
     * @param  string $search  Search
     * @param  array  $replace Replace
     */
    public function replaceArray(string $search, array $replace): self
    {
        $segments = explode($search, $this->string);

        $result = array_shift($segments);

        foreach ($segments as $segment) {
            $result .= (array_shift($replace) ?? $search) . $segment;
        }

        $this->string = $result;

        return $this;
    }

    /**
     * Replace the first occurrence of a given value in the string.
     *
     * @param  string $search  Search
     * @param  string $replace Replace
     */
    public function replaceFirst(string $search, string $replace): self
    {
        $position = strpos($this->string, $search);

        if ($position !== false) {
            $this->string = substr_replace($this->string, $replace, $position, static::create($search, $this->encoding)->length());
        } else {
            $this->string = $search;
        }

        return $this;
    }

    /**
     * Replace the last occurrence of a given value in the string.
     *
     * @param  string $search  Search
     * @param  string $replace Replace
     */
    public function replaceLast(string $search, string $replace): self
    {
        $position = strrpos($this->string, $search);

        if ($position !== false) {
            $this->string = substr_replace($this->string, $replace, $position, static::create($search, $this->encoding)->length());
        } else {
            $this->string = $search;
        }

        return $this;
    }

    /**
     * Begin a string with a single instance of a given value.
     *
     * @param  string $prefix Prefix
     */
    public function start(string $prefix): self
    {
        $quoted = preg_quote($prefix, '/');

        $this->string = $prefix . preg_replace('/^(?:' . $quoted . ')+/u', '', $this->string);

        return $this;
    }

    /**
     * Determine if a given string starts with a given substring.
     *
     * @param  string|string[] $needles Needles
     */
    public function startsWith($needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ((string) $needle !== '' && strncmp($this->string, (string) $needle, static::create($needle, $this->encoding)->length()) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a given string ends with a given substring.
     *
     * @param  string|string[] $needles needles
     */
    public function endsWith($needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && (string) static::create($this->string, $this->encoding)->substr(-static::create($needle, $this->encoding)->length()) === (string) $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cap a string with a single instance of a given value.
     *
     * @param  string $cap Cap
     */
    public function finish(string $cap): self
    {
        $quoted = preg_quote($cap, '/');

        $this->string = preg_replace('/(?:' . $quoted . ')+$/u', '', $this->string) . $cap;

        return $this;
    }

    /**
     * Prepend the given values to the string.
     *
     * @param  string[] $values
     */
    public function prepend(string ...$values): self
    {
        $this->string = implode('', $values) . $this->string;

        return $this;
    }

    /**
     * Append the given values to the string.
     *
     * @param  string[] $values
     */
    public function append(string ...$values): self
    {
        $this->string .= implode('', (array) $values);

        return $this;
    }

    /**
     * Generate a hash string from the input string.
     *
     * @param  string $string     String
     * @param  string $algorithm  Name of selected hashing algorithm (i.e. "md5", "sha256", "haval160,4", etc..).
     *                            For a list of supported algorithms see hash_algos(). Default is md5.
     * @param  string $raw_output When set to TRUE, outputs raw binary data. FALSE outputs lowercase hexits. Default is FALSE
     */
    public function hash(string $algorithm = 'md5', bool $raw_output = false): self
    {
        if (in_array($algorithm, hash_algos())) {
            $this->string = hash($algorithm, $this->string, $raw_output);
        } else {
            $this->string = $this->string;
        }

        return $this;
    }

    /**
     * Randomly shuffles a string.
     */
    public function shuffle(): self
    {
        $indexes = range(0, static::create($this->string, $this->encoding)->length()  - 1);

        shuffle($indexes);

        $shuffled_string = '';

        foreach ($indexes as $i) {
            $shuffled_string .= static::create($this->string, $this->encoding)->substr($i, 1);
        }

        $this->string = $shuffled_string;

        return $this;
    }

    /**
     * Calculate the similarity between two strings.
     *
     * @param string $string The delimiting string.
     */
    public function similarity(string $string): float
    {
        similar_text($this->string, $string, $percent);

        return $percent;
    }

    /**
     * Returns the character at $index, with indexes starting at 0.
     *
     * @param int $index Position of the character
     */
    public function at(int $index): self
    {
        $this->string = (string) $this->substr($index, 1);

        return $this;
    }

    /**
     * Move substring of desired $length to $destination index of the original string.
     * In case $destination is less than $length returns the string untouched.
     *
     * @param int $start       Start
     * @param int $length      Length
     * @param int $destination Destination
     */
    public function move(int $start, int $length, int $destination): self
    {
        if ($destination <= $length) {
            return $this;
        }

        $substr       = mb_substr($this->string, $start, $length);
        $this->string = mb_substr($this->string, 0, $destination) . $substr . mb_substr($this->string, $destination);

        $pos          = mb_strpos($this->string, $substr, 0);
        $this->string = mb_substr($this->string, 0, $pos) . mb_substr($this->string, $pos + mb_strlen($substr));

        return $this;
    }

    /**
     * Inserts $substring into the string at the $index provided.
     *
     * @param string $substring Substring
     * @param int    $index     Index
     */
    public function insert(string $substring, int $index): self
    {
        $this->string = static::create($this->string)->substr(0, $index)->toString() .
                        $substring .
                        static::create($this->string)->substr($index)->toString();

        return $this;
    }

    /**
     * Returns true if the string is not empty, false otherwise.
     */
    public function isEmpty(): bool
    {
        return empty($this->string);
    }

    /**
     * Returns true if the string contains ASCII, false otherwise.
     */
    public function isAscii(): bool
    {
        return mb_ereg_match('^[[:ascii:]]*$', $this->string);
    }

    /**
     * Returns true if the string contains only alphabetic and numeric chars, false otherwise.
     */
    public function isAlphanumeric(): bool
    {
        return mb_ereg_match('^[[:alnum:]]*$', $this->string);
    }

    /**
     * Returns true if the string contains only alphabetic chars, false otherwise.
     */
    public function isAlpha(): bool
    {
        return mb_ereg_match('^[[:alpha:]]*$', $this->string);
    }

    /**
     * Returns true if the string contains only whitespace chars, false otherwise.
     */
    public function isBlank(): bool
    {
        return mb_ereg_match('^[[:space:]]*$', $this->string);
    }

    /**
     * Returns true if the string is a number or a numeric strings, false otherwise.
     */
    public function isNumeric(): bool
    {
        return is_numeric($this->string);
    }

    /**
     * Returns true if the string contains only digit chars, false otherwise.
     */
    public function isDigit(): bool
    {
        return mb_ereg_match('^[[:digit:]]*$', $this->string);
    }

    /**
     * Returns true if the string contains only lower case chars, false otherwise.
     */
    public function isLower(): bool
    {
        return mb_ereg_match('^[[:lower:]]*$', $this->string);
    }

    /**
     * Returns true if the string contains only upper case chars, false otherwise.
     */
    public function isUpper(): bool
    {
        return mb_ereg_match('^[[:upper:]]*$', $this->string);
    }

    /**
     * Returns true if the string contains only hexadecimal chars, false otherwise.
     */
    public function isHexadecimal(): bool
    {
        return mb_ereg_match('^[[:xdigit:]]*$', $this->string);
    }

    /**
     * Returns true if the string contains only printable (non-invisible) chars, false otherwise.
     */
    public function isPrintable(): bool
    {
        return mb_ereg_match('^[[:print:]]*$', $this->string);
    }

    /**
     * Returns true if the string contains only punctuation chars, false otherwise.
     */
    public function isPunctuation(): bool
    {
        return mb_ereg_match('^[[:punct:]]*$', $this->string);
    }

    /**
     * Returns true if the string is serialized, false otherwise.
     */
    public function isSerialized(): bool
    {
        if ($this->string === '') {
            return false;
        }

        return $this->string === 'b:0;' || @unserialize($this->string) !== false;
    }

    /**
     * Returns true if the string is JSON, false otherwise.
     */
    public function isJson(): bool
    {
        json_decode($this->string);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Returns true if the string is base64 encoded, false otherwise.
     */
    public function isBase64(): bool
    {
        if ($this->length() === 0) {
            return false;
        }

        $decoded = base64_decode($this->string, true);

        if ($decoded === false) {
            return false;
        }

        return base64_encode($decoded) === $this->string;
    }

    /**
     * Check if two strings are similar.
     *
     * @param string $string                  The string to compare against.
     * @param float  $minPercentForSimilarity The percentage of needed similarity. Default is 80%
     *
     * @return bool
     */
    public function isSimilar(string $string, float $minPercentForSimilarity = 80.0): bool
    {
        return $this->similarity($string) >= $minPercentForSimilarity;
    }

    /**
     * Return Strings object as string.
     */
    public function toString(): string
    {
        return strval($this->string);
    }

    /**
     * Return Strings object as integer.
     */
    public function toInteger(): int
    {
        return intval($this->string);
    }

    /**
     * Return Strings object as float.
     */
    public function toFloat(): float
    {
        return floatval($this->string);
    }

    /**
     * Returns a boolean representation of the given logical string value.
     *
     * For example:
     * 'true', '1', 'on' and 'yes' will return true.
     * 'false', '0', 'off', and 'no' will return false.
     *
     * In all instances, case is ignored.
     *
     * For other numeric strings, their sign will determine the return value.
     * In addition, blank strings consisting of only whitespace will return
     * false. For all other strings, the return value is a result of a
     * boolean cast.
     */
    public function toBoolean(): bool
    {
        $result = filter_var($this->string, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $result ?? true;
    }

    /**
     * Return Strings object as array based on a delimiter.
     *
     * @param string $delimiter Delimeter. Default is null.
     */
    public function toArray(?string $delimiter = null): array
    {
        $encoding = $this->encoding;
        $string   = static::create($this->string, $encoding)->trim()->toString();

        if ($delimiter !== null) {
            $array = explode($delimiter, $string);
        } else {
            $array = [$string];
        }

        array_walk(
            $array,
            static function (&$value) use ($encoding): void {
                if ((string) $value !== $value) {
                    return;
                }

                $value = static::create($value, $encoding)->trim()->toString();
            }
        );

        return $array;
    }

    /**
     * Determine whether the string is equals to $string.
     *
     * @param $string String to compare.
     */
    public function isEqual(string $string): bool
    {
        return ($string === $this->toString()) ? true : false;
    }
}
