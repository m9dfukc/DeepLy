<?php

use PHPUnit\Framework\TestCase;

/**
 * Class MainClassTest for tests with PHPUnit.
 * The focus is here on the main class of this library, Octfx\DeepLy.
 */
class MainClassTest extends TestCase
{

    /**
     * @covers \Octfx\DeepLy\DeepLy::getTranslationBag
     */
    public function testGetTranslationBag(): void
    {
        $deepLy = $this->getInstance();

        $translationBag = $deepLy->getTranslationBag();
        $this->assertNull($translationBag);
    }


    /**
     * @covers \Octfx\DeepLy\DeepLy::getLangName
     */
    public function testGetLangName(): void
    {
        $deepLy = $this->getInstance();

        $langName = $deepLy->getLangName('EN');
        $this->assertEquals('English', $langName);

        $langName = $deepLy->getLangName('DE');
        $this->assertEquals('German', $langName);
    }

    /**
     * @covers \Octfx\DeepLy\DeepLy::getLangCodeByName
     */
    public function testGetLangCodeByName(): void
    {
        $deepLy = $this->getInstance();

        $langCode = $deepLy->getLangCodeByName('English');
        $this->assertSame('EN', $langCode);
        $langCode = $deepLy->getLangCodeByName('German');
        $this->assertSame('DE', $langCode);
    }

    /**
     * Creates and returns an instance of the main class.
     *
     * @return \Octfx\DeepLy\DeepLy
     */
    protected function getInstance(): \Octfx\DeepLy\DeepLy
    {
        return new Octfx\DeepLy\DeepLy('');
    }
}
