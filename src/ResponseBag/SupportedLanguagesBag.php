<?php

declare(strict_types=1);

namespace Octfx\DeepLy\ResponseBag;

/**
 * Class UsageBag.
 */
class SupportedLanguagesBag extends AbstractBag
{
    /**
     * Check the Response.
     *
     * @param mixed|null $responseContent
     */
    public function verifyResponseContent($responseContent): void
    {
        if (count($responseContent) === 0) {
            throw new BagException(
                'DeepLy API call resulted in a malformed result - languages array is empty',
                300
            );
        }
    }

    /**
     * The whole Response.
     *
     * @return array
     */
    public function getResponse(): array
    {
        return json_decode(json_encode($this->responseContent), true);
    }

    /**
     * Checks if a language name / code is supported by deepl
     *
     * @param string $language
     *
     * @return bool
     */
    public function getLanguageSupported(string $language): bool
    {
        foreach ($this->responseContent as $supportedLanguage) {
            if ($language === $supportedLanguage->language || $language === $supportedLanguage->name) {
                return true;
            }
        }

        return false;
    }
}
