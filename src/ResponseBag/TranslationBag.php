<?php

declare(strict_types=1);

namespace Octfx\DeepLy\ResponseBag;

/**
 * This class handles the response content of a successful API translation call.
 * It checks its validity and offers methods to access the contents of the response content.
 * It implements an abstraction layer above the original response of the API call.
 */
class TranslationBag extends AbstractBag
{
    /**
     * Verifies that the given response content (usually a \stdClass built by json_decode())
     * is a valid result from an API call to the DeepL API.
     * This method will not return true/false but throw an exception if something is invalid.
     * Especially it will throw an exception if the API was not able to auto-detected the source language
     * (if no language code was given).
     *
     * @param mixed|null $responseContent The response content (payload) of a translation API call
     *
     * @throws BagException
     *
     * @return void
     */
    public function verifyResponseContent($responseContent): void
    {
        // Let the original method of the abstract base class do some basic checks
        parent::verifyResponseContent($responseContent);

        $this->checkResponseProperties($responseContent);
    }

    /**
     * Returns a translation from the response content of the API call.
     * Tries to return the "best" translation (which is the first).
     * Returns null if there is no translation.
     *
     * @return string|null
     */
    public function getTranslation(): ?string
    {
        return $this->responseContent->translations[0]->text;
    }

    /**
     * Return all Translations.
     *
     * @return array
     */
    public function getTranslations(): array
    {
        $translations = [];
        foreach ($this->responseContent->translations as $translation) {
            $translations[] = $translation;
        }

        return $translations;
    }

    /**
     * Returns the language code of the source ("from") language. Might have been auto-detected by DeepL.
     * Attention: DeepLy does not check if the result is in the Deeply::LANG_CODES array.
     * Therefore DeepLy also will work if DeepL adds support for new languages.
     *
     * @return string The language code, one of these: Deeply::LANG_CODES
     */
    public function getSourceLanguage(): string
    {
        return $this->responseContent->translations[0]->detected_source_language;
    }

    /**
     * Checks the Response Content for needed Properties.
     *
     * @param mixed $responseContent
     */
    private function checkResponseProperties($responseContent): void
    {
        if (!property_exists($responseContent, 'translations')) {
            throw new BagException(
                'DeepLy API call resulted in a malformed result - translations are missing',
                230
            );
        }

        if (!is_array($responseContent->translations)) {
            throw new BagException(
                'DeepLy API call resulted in a malformed result - translations are not an array',
                231
            );
        }
    }
}
