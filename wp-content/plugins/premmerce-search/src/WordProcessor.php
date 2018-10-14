<?php namespace Premmerce\Search;

use Behat\Transliterator\Transliterator;

class WordProcessor
{
    /**
     * @var int
     */
    private $minLetters = 2;

    /**
     * @var array
     */
    private $dictionary;

    /**
     * @var int
     */
    private $accuracy = 1;

    /**
     * @param array $strings
     *
     * @return array
     */
    public function prepareIndexes(array $strings)
    {
        $strings = implode(' ', $strings);

        $strings = $this->splitString($strings);

        $strings = array_filter($strings, function ($item) {
            return !is_numeric($item) && mb_strlen($item) >= $this->minLetters;
        });

        return $strings;
    }

    /**
     * @param string $string
     *
     * @return array
     */
    public function splitString($string)
    {
        $split = preg_split("/[^\p{L}\p{N}]+/u", mb_strtolower($string), null, PREG_SPLIT_NO_EMPTY);

        $split = array_unique($split);

        return $split;
    }

    /**
     * @param string $word
     *
     * @return string
     */
    public function transliterate($word)
    {
        return Transliterator::transliterate($word);
    }

    /**
     * @param array $dictionary
     */
    public function setDictionary(array $dictionary)
    {
        $this->dictionary = $dictionary;
    }

    /**
     * @param array $words
     *
     * @return array
     */
    public function matchWords(array $words)
    {
        $results = array();
        $numeric = array();

        foreach ($words as $actualWord) {
            if (mb_strlen($actualWord) > 2) {
                if (is_numeric($actualWord)) {
                    array_push($numeric, $actualWord);
                } else {
                    foreach ($this->dictionary as $suggestedWord) {
                        $distance = $this->compareWord($suggestedWord, $actualWord);

                        if ($distance <= $this->accuracy) {
                            $results[ $actualWord ][ $distance ][] = $suggestedWord;
                        }
                    }
                }
            }
        }


        $words = array();
        foreach ($results as $wordResults) {
            if (count($wordResults)) {
                $wordSuggestions = $wordResults[ min(array_keys($wordResults)) ];
                foreach ($wordSuggestions as $word) {
                    $words[] = $word;
                }
            }
        }

        $words = array_unique($words);

        return array_merge($words, $numeric);
    }

    /**
     * Returns words distance
     *
     * @param $suggested - word from dictionary
     * @param $actual - term
     *
     * @return int|mixed
     */
    public function compareWord($suggested, $actual)
    {
        $translitSuggested = $this->transliterate($suggested);
        $translitActual    = $this->transliterate($actual);

        $soundSuggested = metaphone($translitSuggested);
        $soundActual    = metaphone($translitActual);

        $suggestedLen = strlen($translitSuggested);
        $actualLen    = strlen($translitActual);

        $diff = 0;
        if ($actualLen < $suggestedLen) {
            $diff = $suggestedLen - $actualLen;
        }

        $translitDistance = levenshtein($translitActual, $translitSuggested) - $diff;

        $soundDistance = levenshtein($soundSuggested, $soundActual);

        $numberExists = preg_match('/[0-9]/', $actual . $suggested);

        if (!$numberExists && $soundDistance === 0) {
            $translitDistance = min($translitDistance, $soundDistance);
        }


        return $translitDistance;
    }
}
