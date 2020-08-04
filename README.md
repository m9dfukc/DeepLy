# DeepLy

[DeepL.com](https://www.deepl.com/) is a great, new translation service. 
It provides better translations compared to other popular translation engines.
DeepLy is a PHP package that implements a client to interact with DeepL via their API, with optional Laravel integration.

## Installation

Through [Composer](https://getcomposer.org/):

```
composer require octfx/deeply
```

From then on you may run `composer update` to get the latest version of this library.

It is possible to use this library without using Composer but then it is necessary to register an 
[autoloader function](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md#example-implementation).

> This library requires PHP 7.1 or higher and the mbstring extension.

If you do not have an API key, now is the time to get one. Vist [DeepL-com](https://deepl.com/) to request an API key.

## Usage Example

```php
$deepLy = new Octfx\DeepLy\DeepLy('Your-API-Key');

$translatedText = $deepLy->translate('Hello world!', 'DE', 'EN');
    
echo $translatedText; // Prints "Hallo Welt!"
```

### Sophisticated Example

```php
use Octfx\DeepLy\DeepLy;

$apiKey = 'Your-API-Key';
$deepLy = new DeepLy($apiKey);

try {
    $translatedText = $deepLy->translate('Hello world!', DeepLy::LANG_EN, DeepLy::LANG_AUTO);
    
    echo $translatedText; // Prints "Hallo Welt!"
} catch (AuthenticationException $e) {
    // API Key invalid
    // Code 403
} catch (QuotaException $e) {
    // Quota exceeded
    // Code 456
} catch (RateLimitedException $e) {
    // Ratelimited
    // Code 429
} catch (TextLengthException $e) {
     // Textlength > 30000 chars
} catch (CallException $e) {
    // Other errors
    // See: https://www.deepl.com/docs-api.html?part=accessing
}
```

Always wrap calls of the `translate` method in a try-catch-block, because they might throw an exception if the
arguments are invalid or the API call fails. Instead of using hardcoded strings as language arguments 
better use the language code constants of the `DeepLy` class. The class also offers methods such as
`getLangCodes($withAuto = true)` and `supportsLangCode($langCode)`. 

You may use the `proposeTranslations` method if you want to get alternative translations for a text. 
This method cannot operate on more than one sentence at once. 

## Translation formality
The DeepL API allows to specify the formality of the translated text.  
This feature currently works for all target languages except "ES" (Spanish), "JA" (Japanese) and "ZH" (Chinese).  
Possible options are:
- "default" (default)
- "more" - for a more formal language
- "less" - for a more informal language
```php
$deepLy->formality('less');

// or
$translatedText = $deepLy->translate('Hello world!', DeepLy::LANG_EN, DeepLy::LANG_AUTO, 'more');
```

## Auto-Detect Language

DeepLy has a method that uses the DeepL API to detect the language of a text:

```php
$languageCode = $deepLy->detectLanguage('Hello world!');
```

This will return 'EN'. The language of the text has to be one of the supported languages or the result will be incorrect.
If you do not need the code of the language but its name, you may call the `$deepLy->getLangName($langCode)` method. 

The API in general can handle and completely translate texts that contain parts with different languages, 
if the language switch is not within a sentence. The `detectLanguage()` method will however 
only return the code of _one_ language. It will throw an exception if it is unable to auto-detect the language. 
This will rarely happen, it is more likely that the API will return a "false positive": It will rather detect the wrong
language than no language at all.

## Supported Languages

DeepL(y) supports these languages:

| Code | Language      |
|------|---------------|
| auto | _Auto detect_ |
| DE   | German        |
| EN   | English       |
| FR   | French        |
| ES   | Spanish       |
| IT   | Italian       |
| NL   | Dutch         |
| PL   | Polish        |
| PT   | Portuguese    |
| RU   | Russian       |

> Note that auto detection only is possible for the source language. 

DeepL says they will [add more languages](https://www.heise.de/newsticker/meldung/Maschinelles-Uebersetzen-Deutsches-Start-up-DeepL-will-230-Sprachkombinationen-unterstuetzen-3836533.html) 
in the future, such as Chinese and Russian.

## Text Length Limit

According to the DeepL.com website, the length of the text that has to be translated is limited to 30000 characters.
Per default DeepLy will throw an exception if the length limit is exceeded. 
You may call `$deepLy->setValidateTextLength(false)` to disable this validation.

## HTTP Client

Per default DeepLy uses a HTTP client based on Guzzle. If you want to use a different HTTP client, 
 create a class that implements the `HttpClient\HttpClientInterface`
 and makes use of the methods of the alternative HTTP client. Then use `$deepLy->setHttpClient($yourHttpClient)`
 to inject it.

## Framework Integration

DeepLy comes with support for Laravel 5.x and since it also supports 
[package auto-discovery](https://medium.com/@taylorotwell/package-auto-discovery-in-laravel-5-5-ea9e3ab20518) 
it will be auto-detected in Laravel 5.5. 

In Laravel 5.0-5.4 you manually have to register the service provider
 `Octfx\DeepLy\Integrations\Laravel\DeepLyServiceProvider` in the "providers" array and the facade 
 `Octfx\DeepLy\Integrations\Laravel\DeepLyFacade` as an alias in the "aliases" array 
 in your `config/app.php` config file.

DeepLy uses `config('services.deepl.auth_key')` to retrieve the API key so you have to set it in the `services.php` settings.

```php
return [
    'deepl' => [
        'auth_key' => env('DEEPL_AUTH_KEY'),
    ],
];
```

## Request Limit

There is a request limit. The threshold of this limit is specified in the quota documentation delivered to you by DeepL.

## Internals

The "core" of this library consists of these classes:
* `DeepLy` - main class
* `HttpClient\GuzzleHttpClient` - HTTP client class
* `Protocol\JsonProtocol` - JSON is the protocol used by the DeepL API
* `ResponseBag\AbstractBag` - base wrapper class for the responses of the DeepL API
* `ResponseBag\UsageBag` - concrete class for API responses to usage statistics requests
* `ResponseBag\TranslationBag` - concrete class for API responses to "translate" requests

There are also some exception classes, interfaces, an alternative HTTP client implementation that uses Guzzle and classes for the Laravel integration.


## Disclaimer

This is not an official package. It is 100% open source and non-commercial. 

DeepL is a product of DeepL GmbH. More info: [deepl.com/publisher.html](https://www.deepl.com/publisher.html)
