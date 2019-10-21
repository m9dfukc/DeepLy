<?php

declare(strict_types=1);

namespace Octfx\DeepLy\ResponseBag;

/**
 * Class UsageBag.
 */
class UsageBag extends AbstractBag
{
    /**
     * Check the Response.
     *
     * @param mixed|null $responseContent
     */
    public function verifyResponseContent($responseContent): void
    {
        if (!property_exists($responseContent, 'character_count')) {
            throw new BagException(
                'DeepLy API call resulted in a malformed result - character_count is missing',
                300
            );
        }

        if (!property_exists($responseContent, 'character_limit')) {
            throw new BagException(
                'DeepLy API call resulted in a malformed result - character_limit is missing',
                310
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
        return [
            'character_count' => $this->getCharacterCount(),
            'character_limit' => $this->getCharacterLimit(),
        ];
    }

    /**
     * Characters translated so far in the current billing period.
     *
     * @return int
     */
    public function getCharacterCount(): int
    {
        return $this->responseContent->character_count;
    }

    /**
     * Total maximum volume of characters that can be translated in the current billing period.
     *
     * @return int
     */
    public function getCharacterLimit(): int
    {
        return $this->responseContent->character_limit;
    }
}
