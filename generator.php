<?php
declare(strict_types=1);
ini_set("error_log", "exception.log");
define("VOWELS", ["a", "e", "i", "o", "u", "y"]);
define("CONSONANTS", ["b", "c", "d", "f", "g", "h", "j", "k", "l", "m", "n", "p", "q", "r", "s", "t", "v", "w", "x", "z"]);


function getNextLetter(bool $forceVowel = false, bool $forceConsonant = false)
{
    if ($forceVowel && !$forceConsonant) {
        $letters = VOWELS;
    } else if (!$forceVowel && $forceConsonant) {
        $letters = CONSONANTS;
    } else {
        $letters = array_merge(CONSONANTS, VOWELS);
    }

    return $letters[rand(0, count($letters) - 1)];
}

function generateWord(array $generatedWords, int $minLetters, int $maxLetters): string
{
    $word = "";
    $letters = rand($minLetters, $maxLetters);
    for ($i = 0; $i < $letters; $i++) {
        if (strlen($word) === 0) {
            $word .= getNextLetter(false, true);
            continue;
        }
        if ((strlen($word) === 1) || (strlen($word) >= 2 && !in_array($word[strlen($word) - 1], VOWELS) && !in_array($word[strlen($word) - 2], VOWELS))) {
            $word .= getNextLetter(true, false);
        } else {
            $word .= getNextLetter();
        }
    }
    if (in_array($word, $generatedWords)) {
        return generateWord($generatedWords, $minLetters, $maxLetters);
    } else {
        return $word;
    }
}

function generateWords(int $numWords, int $minLetters, int $maxLetters): array
{
    $words = [];
    for ($i = 0; $i < $numWords; $i++) {
        $words[] = generateWord($words, $minLetters, $maxLetters);
    }
    return $words;
}

function writeOutput(DateTime $date, int $numWords, int $minLetters, int $maxLetters): void
{
    $generatorLogFile = fopen("generator.log", 'a+');
    $wordsLogFile = fopen("words.txt", 'w+');
    $logEntry = sprintf("[%s] Total words: %d, words length range (min,max): (%d,%d)\n", $date->format("Y-m-d H:i:s"), $numWords, $minLetters, $maxLetters);
    fwrite($generatorLogFile, $logEntry);
    fclose($generatorLogFile);
    $words = generateWords($numWords, $minLetters, $maxLetters);
    foreach ($words as $word) {
        fwrite($wordsLogFile, "$word\n");
    }
    fclose($wordsLogFile);
}

$shorthandsOptions = "f::t::w::";
$longOptions = [
    "min::",
    "max::",
    "words::",
    "force"
];
$params = getopt($shorthandsOptions, $longOptions);

if ($argc === 1) {
    echo <<<HELP
No valid params. Set [int -w|--words] [int -f|--min] [int -t|--max].
Params:
 * int -w|--words number of word to generate
 * int -f|--min minimal number of letters in word
 * int -t|--max maximal number of letters in word
 --force generate words outside of working hours (Friday 15:00 - Monday 10:00)
 
 * params are required
Example.:
php generator.php -w=10 --min=20 --max=30 --force
HELP.PHP_EOL;
    exit;
}

$words = (int)($params['w'] ?? $params['words']);
if ($words < 1) {
    echo "Words need to be integer higher than 0\n";
    throw new InvalidArgumentException("Words need to be integer higher than 0");
}

$minLetters = (int)($params['f'] ?? $params['min']);
if ($minLetters < 1) {
    echo "Minimal number of letter should be integer higher than 0\n";
    throw new InvalidArgumentException("Minimal number of letter should be integer higher than 0");
}
$maxLetters = (int)($params['t'] ?? $params['max']);
if ($maxLetters < 1) {
    echo "Maximal number of letters should be integer higher than 0\n";
    throw new InvalidArgumentException("Maximal number of letters should be integer higher than 0");
}

if ($maxLetters < $minLetters) {
    echo "Maximal number of letters should be higher or equal minimal number of letters.\n";
    throw new InvalidArgumentException("Maximal number of letters should be higher or equal minimal number of letters.");
}

$force = isset($params['force']);

$date = new DateTime();
$weekDay = (int)$date->format("N");
$hour = (int)$date->format("G");

if (!$force) {
    if (($weekDay === 6 || $weekDay === 7)
        || ($weekDay === 5 && $hour > 15)
        || ($weekDay === 1 && $hour < 10)) {
        echo "You can't run script between Friday 15.00 and Monday 10:00\n";
        throw new RuntimeException("You can't run script between Friday 15.00 and Monday 10:00");
    }
}


writeOutput($date, $words, $minLetters, $maxLetters);



