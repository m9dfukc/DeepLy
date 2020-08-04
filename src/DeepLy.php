<?php

declare(strict_types=1);

namespace Octfx\DeepLy;

use Exception;
use InvalidArgumentException;
use Octfx\DeepLy\Exceptions\AuthenticationException;
use Octfx\DeepLy\Exceptions\QuotaException;
use Octfx\DeepLy\Exceptions\RateLimitedException;
use Octfx\DeepLy\Exceptions\TextLengthException;
use Octfx\DeepLy\HttpClient\CallException;
use Octfx\DeepLy\HttpClient\GuzzleHttpClient;
use Octfx\DeepLy\HttpClient\HttpClientInterface;
use Octfx\DeepLy\Protocol\JsonProtocol;
use Octfx\DeepLy\Protocol\ProtocolInterface;
use Octfx\DeepLy\ResponseBag\TranslationBag;
use Octfx\DeepLy\ResponseBag\UsageBag;
use RuntimeException;

/**
 * This is the main class. Call its translate() method to translate text.
 */
class DeepLy
{
    /**
     * All supported language code constants.
     *
     * @see https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
     */
    public const LANG_AUTO = 'auto'; // Let DeepL decide which language it is (only works for the source language)
    public const LANG_DE = 'DE'; // German
    public const LANG_EN = 'EN'; // English
    public const LANG_FR = 'FR'; // French
    public const LANG_ES = 'ES'; // Spanish
    public const LANG_IT = 'IT'; // Italian
    public const LANG_NL = 'NL'; // Dutch
    public const LANG_PL = 'PL'; // Polish
    public const LANG_PT = 'PT'; // Portuguese
    public const LANG_RU = 'RU'; // Russian

    /**
     * Array with all supported language codes.
     *
     * @see https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
     */
    public const LANG_CODES = [
        self::LANG_AUTO,
        self::LANG_DE,
        self::LANG_EN,
        self::LANG_FR,
        self::LANG_ES,
        self::LANG_IT,
        self::LANG_NL,
        self::LANG_PL,
        self::LANG_PT,
        self::LANG_RU,
    ];

    /**
     * Array with language codes as keys and the matching language names in English as values.
     *
     * @see https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
     */
    public const LANG_NAMES = [
        self::LANG_AUTO => 'Auto',
        self::LANG_DE => 'German',
        self::LANG_EN => 'English',
        self::LANG_FR => 'French',
        self::LANG_ES => 'Spanish',
        self::LANG_IT => 'Italian',
        self::LANG_NL => 'Dutch',
        self::LANG_PL => 'Polish',
        self::LANG_PT => 'Portuguese',
        self::LANG_RU => 'Russian',
    ];

    /**
     * The length of the text for translations is limited by the API.
     */
    public const MAX_TRANSLATION_TEXT_LEN = 30000;

    /**
     * The base URL of the API endpoint.
     */
    public const API_BASE_URL = 'https://api.deepl.com/v2/';

    /**
     * Array with all versions of the DeepL API that are supported
     * by the current version of DeepLy.
     */
    public const API_SUPPORT = [2];

    /**
     * Current version number.
     */
    public const VERSION = '3.0';

    /**
     * @var ProtocolInterface
     */
    protected $protocol;

    /**
     * The API key that we need to authenticate.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * The HTTP client used for communication.
     *
     * @var HttpClientInterface
     */
    protected $httpClient;

    /**
     * This property stores the result (object) of the last translation.
     *
     * @var TranslationBag|null
     */
    protected $translationBag;

    /**
     * @see DeepLy::splitSentences()
     *
     * @var string
     */
    private $splitSentences = '1';

    /**
     * @see DeepLy::preserveFormatting()
     *
     * @var string
     */
    private $preserveFormatting = '0';

    /**
     * @see DeepLy::setValidateTextLength()
     *
     * @var bool false to ignore 30kb limit
     */
    private $checkTextLength = true;

    /**
     * @see DeepLy::formality()
     *
     * @var string The translation formality
     */
    private $formality = 'default';

    /**
     * DeepLy object constructor.
     *
     * @param string $apiKey The API key for the DeepL API
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;

        $this->protocol = new JsonProtocol();
        // Create the default HTTP client. You may call setHttpClient() to set another HTTP client.
        $this->httpClient = new GuzzleHttpClient();
    }

    /**
     * Set a custom Http Client Implementation.
     *
     * @param HttpClientInterface $client
     */
    public function setHttpClient(HttpClientInterface $client): void
    {
        $this->httpClient = $client;
    }

    /**
     * Tries to detect the language of a text and returns its language code.
     * The language of the text has to be one of the supported languages or the result will be incorrect.
     * This method might throw an exception so you should wrap it in a try-catch-block.
     * Especially it will throw an exception if the API was not able to auto-detected the language.
     *
     * @param string $text The text you want to analyze
     *
     * @return string|null Returns a language code from the self::LANG_CODES array or null
     *
     * @throws Exception
     */
    public function detectLanguage(string $text): ?string
    {
        // Note: We always use English as the target language. if the source language is English as well,
        // DeepL automatically seems to set the target language to French so this is not a problem.
        return $this->requestTranslation($text, self::LANG_EN, self::LANG_AUTO)->getSourceLanguage();
    }

    /**
     * Get the current Usage.
     *
     * @return UsageBag
     */
    public function getUsage(): UsageBag
    {
        $rawResponseData = $this->httpClient->callApi(
            'usage',
            'GET',
            [
                'auth_key' => $this->apiKey,
            ]
        );

        $responseContent = $this->protocol->processResponseData($rawResponseData);

        return new UsageBag($responseContent);
    }

    /**
     * Translates a text.
     * ATTENTION: The target language parameter is followed by the source language parameter!
     * This method might throw an exception so you should wrap it in a try-catch-block.
     *
     * @param string      $text The text you want to translate
     * @param string      $to   Optional: The target language, a self::LANG_<code> constant
     * @param string|null $from Optional: The source language, a self::LANG_<code> constant
     * @param string      $formality Optional: The target formality
     *
     * @return string|null Returns the translated text or null if there is no translation
     *
     * @throws AuthenticationException
     * @throws QuotaException
     * @throws RateLimitedException
     * @throws TextLengthException
     */
    public function translate(string $text, string $to = self::LANG_EN, string $from = self::LANG_AUTO, string $formality = 'default'): ?string
    {
        $this->formality($formality);

        return $this->requestTranslation($text, $to, $from)->getTranslation();
    }

    /**
     * Translates a text file. The $from argument is optional.
     * ATTENTION: The target language parameter is followed by the source language parameter!
     * This method will throw an exception if reading the file or translating fails
     * so you should wrap it in a try-catch-block.
     *
     * @param string      $filename  The name of the file you want to translate
     * @param string      $to        Optional: The target language, a self::LANG_<code> constant
     * @param string|null $from      Optional: The source language, a self::LANG_<code> constant
     * @param string      $formality Optional: The target formality
     *
     * @return string|null Returns the translated text or null if there is no translation
     *
     * @throws AuthenticationException
     * @throws QuotaException
     * @throws RateLimitedException
     * @throws TextLengthException
     */
    public function translateFile(string $filename, string $to = self::LANG_EN, string $from = self::LANG_AUTO, string $formality = 'default'): ?string
    {
        if (!is_readable($filename)) {
            throw new InvalidArgumentException('Could not read file with the given filename');
        }

        $text = file_get_contents($filename);

        if (false === $text) {
            throw new RuntimeException(
                'Could not read file with the given filename. Does this file exist and do we have read permission?'
            );
        }

        return $this->translate($text, $to, $from, $formality);
    }

    /**
     * Decides if a language (code) is supported by DeepL(y).
     * Note that 'auto' is not a valid value in this context
     * except you explicitly set the $allowAuto param to true.
     *
     * @param string $langCode  The language code, for example 'EN'
     * @param bool   $allowAuto Optional: If false, 'auto' is not a valid language
     *
     * @return bool
     */
    public function supportsLangCode(string $langCode, bool $allowAuto = false): bool
    {
        return in_array($langCode, $this->getLangCodes($allowAuto), true);
    }

    /**
     * Getter for the array with all supported language codes.
     *
     * @param bool $withAuto Optional: If true, the 'auto' code will be in the returned array
     *
     * @return string[]
     */
    public function getLangCodes(bool $withAuto = true): array
    {
        if ($withAuto) {
            return self::LANG_CODES;
        }

        // ATTENTION! This only works as long as self::LANG_AUTO is the first item!
        return array_slice(self::LANG_CODES, 1);
    }

    /**
     * Returns the English name of a language for a given language code.
     * The language code must be on of these: self::LANG_CODES.
     *
     * @param string $langCode The code of the language
     *
     * @return string
     */
    public function getLangName(string $langCode): string
    {
        return self::LANG_NAMES[$langCode];
    }

    /**
     * Returns the language code of a language for a given language name.
     * The language name must be one of these: self::LANG_NAMES.
     *
     * @param string $langName The name of the language
     *
     * @return string
     */
    public function getLangCodeByName(string $langName): string
    {
        return array_search($langName, self::LANG_NAMES, true);
    }

    /**
     * Getter for the TranslationBag object. Might return null!
     * The translation bag contains the result of the last API call.
     *
     * @return TranslationBag|null
     */
    public function getTranslationBag(): ?TranslationBag
    {
        return $this->translationBag;
    }

    /**
     * Sets whether the translation engine should first split the input into sentences. This is enabled by default.
     * - "0" - no splitting at all, whole input is treated as one sentence
     * - "1" (default) - splits on interpunction and on newlines
     * - "nonewlines" - splits on interpunction only, ignoring newlines.
     *
     * @param bool $flag
     *
     * @return $this
     */
    public function splitSentences(bool $flag = true): DeepLy
    {
        $this->splitSentences = $flag;

        return $this;
    }

    /**
     * Sets whether the translation engine should respect the original formatting, even if it would usually correct some aspects.
     * - "0" (default)
     * - "1".
     *
     * @param string $flag
     *
     * @return DeepLy
     */
    public function preserveFormatting(string $flag = '0'): DeepLy
    {
        $this->preserveFormatting = $flag;

        return $this;
    }

    /**
     * Sets whether the translated text should lean towards formal or informal language. This feature currently works
     * for all target languages except "ES" (Spanish), "JA" (Japanese) and "ZH" (Chinese). Possible options are:
     * "default" (default)
     * "more" - for a more formal language
     * "less" - for a more informal language
     * @see https://www.deepl.com/docs-api/translating-text/    Request Parameters -> formality
     *
     * @param string $formality
     *
     * @return DeepLy
     */
    public function formality(string $formality): DeepLy
    {
        $this->formality = strtolower($formality);

        return $this;
    }

    /**
     * True to ignore 30kb Limit
     * Default: FALSE.
     *
     * @param bool $flag
     *
     * @return DeepLy
     */
    public function setValidateTextLength(bool $flag = false): DeepLy
    {
        $this->checkTextLength = $flag;

        return $this;
    }

    /**
     * Requests a translation from the API. Returns a TranslationBag object.
     * ATTENTION: The target language parameter is followed by the source language parameter!
     * This method might throw an exception so you should wrap it in a try-catch-block.
     * You may use the translate() method if you want to get the result as a string.
     *
     * @param string      $text the text to translate
     * @param string      $to   Optional: The target language, a self::LANG_<code> constant
     * @param string|null $from Optional: The source language, a self::LANG_<code> constant
     *
     * @return TranslationBag
     *
     * @throws AuthenticationException
     * @throws QuotaException
     * @throws RateLimitedException
     * @throws TextLengthException
     * @throws CallException
     */
    protected function requestTranslation(string $text, string $to = self::LANG_EN, string $from = self::LANG_AUTO): TranslationBag
    {
        $this->validateTextParameter($text);
        $this->validateToParameter($to);
        $this->validateFromParameter($from);

        $params = $this->buildBaseParams();
        $params = array_merge(
            $params,
            [
                'text' => $text,
                'source_lang' => $from,
                'target_lang' => $to,
            ]
        );

        if (self::LANG_AUTO === $from) {
            unset($params['source_lang']);
        }

        try {
            $rawResponseData = $this->httpClient->callApi('translate', 'POST', $params);
        } catch (CallException $exception) {
            $this->handleCallExceptions($exception);
        }

        $responseContent = $this->protocol->processResponseData($rawResponseData);

        $this->translationBag = new TranslationBag($responseContent);

        return $this->translationBag;
    }

    /**
     * Build the base params.
     *
     * @return array
     */
    private function buildBaseParams(): array
    {
        return [
            'auth_key' => $this->apiKey,
            'split_sentences' => $this->splitSentences,
            'preserve_formatting' => $this->preserveFormatting,
            'formality' => $this->formality,
        ];
    }

    /**
     * @param string $text
     *
     * @throws InvalidArgumentException
     * @throws TextLengthException
     */
    private function validateTextParameter(string $text): void
    {
        if (!is_string($text)) {
            throw new InvalidArgumentException('The $text argument has to be a string');
        }

        if ($this->checkTextLength && mb_strlen($text) > self::MAX_TRANSLATION_TEXT_LEN) {
            throw new TextLengthException(
                sprintf(
                    '%s %d %s',
                    'The sentence exceeds the maximum of',
                    self::MAX_TRANSLATION_TEXT_LEN,
                    'chars'
                )
            );
        }
    }

    /**
     * To Language Code.
     *
     * @param string $to
     *
     * @throws InvalidArgumentException
     */
    private function validateToParameter(string $to): void
    {
        if (!is_string($to)) {
            throw new InvalidArgumentException('The $to argument has to be a string');
        }

        if (!in_array($to, self::LANG_CODES, true)) {
            throw new InvalidArgumentException('The $to argument has to be a valid language code');
        }

        if (self::LANG_AUTO === $to) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s "%s"',
                    'The $to argument cannot be',
                    self::LANG_AUTO
                )
            );
        }
    }

    /**
     * From Language Code.
     *
     * @param string $from
     *
     * @throws InvalidArgumentException
     */
    private function validateFromParameter(string $from): void
    {
        if (!is_string($from)) {
            throw new InvalidArgumentException('The $from argument has to be a string');
        }

        if (!in_array($from, self::LANG_CODES, true)) {
            throw new InvalidArgumentException('The $from argument has to a valid language code');
        }
    }

    /**
     * @param CallException $exception
     *
     * @throws AuthenticationException authorization failed
     * @throws QuotaException          too many requests
     * @throws RateLimitedException    quota exceeded
     * @throws CallException           rethrown if nothing matches
     */
    private function handleCallExceptions(CallException $exception): void
    {
        switch ($exception->getCode()) {
            case 403:
                throw new AuthenticationException('Authorization failed. Please supply a valid auth_key parameter.', 403, $exception);
            case 429:
                throw new RateLimitedException('Too many requests. Please wait and resend your request.', 426, $exception);
            case 456:
                throw new QuotaException('Quota exceeded. The character limit has been reached.', 456, $exception);
            default:
                throw $exception;
        }
    }
}
