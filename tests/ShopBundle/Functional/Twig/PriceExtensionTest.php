<?php

namespace Tests\ShopBundle\Functional\Twig;

use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Localization\IntlCurrencyRepository;
use Shopsys\FrameworkBundle\Model\Localization\Localization;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Twig\PriceExtension;
use Tests\ShopBundle\Test\FunctionalTestCase;

class PriceExtensionTest extends FunctionalTestCase
{
    protected const NBSP = "\xc2\xa0";

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Localization\Localization
     */
    private $localization;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Localization\IntlCurrencyRepository
     */
    private $intlCurrencyRepository;

    /**
     * @var \CommerceGuys\Intl\NumberFormat\NumberFormatRepositoryInterface
     */
    private $numberFormatRepository;

    protected function setUp()
    {
        $this->currencyFacade = $this->getContainer()->get(CurrencyFacade::class);
        $this->domain = $this->getContainer()->get(Domain::class);
        $this->localization = $this->getContainer()->get(Localization::class);
        $this->intlCurrencyRepository = $this->getContainer()->get(IntlCurrencyRepository::class);
        $this->numberFormatRepository = $this->getContainer()->get(NumberFormatRepository::class);

        parent::setUp();
    }

    public function priceFilterDataProviderSingleDomain()
    {
        return [
            ['input' => Money::create(12), 'domainId' => 1, 'result' => 'CZK12.00'],
            ['input' => Money::create('12.00'), 'domainId' => 1, 'result' => 'CZK12.00'],
            ['input' => Money::create('12.600'), 'domainId' => 1, 'result' => 'CZK12.60'],
            ['input' => Money::create('12.630000'), 'domainId' => 1, 'result' => 'CZK12.63'],
            ['input' => Money::create('12.638000'), 'domainId' => 1, 'result' => 'CZK12.638'],
            ['input' => Money::create('12.630000'), 'domainId' => 1, 'result' => 'CZK12.63'],
            [
                'input' => Money::create('123456789.123456789'),
                'domainId' => 1,
                'result' => 'CZK123,456,789.123456789',
            ],
            [
                'input' => Money::create('123456789.123456789123456789'),
                'domainId' => 1,
                'result' => 'CZK123,456,789.1234567891',
            ],
        ];
    }

    public function priceFilterDataProviderMultiDomain()
    {
        $filterDataSingleDomain = $this->priceFilterDataProviderSingleDomain();

        $filterDataMultiDomain = [
            ['input' => Money::create(12), 'domainId' => 2, 'result' => '12,00' . self::NBSP . '€'],
            ['input' => Money::create('12.00'), 'domainId' => 2, 'result' => '12,00' . self::NBSP . '€'],
            ['input' => Money::create('12.600'), 'domainId' => 2, 'result' => '12,60' . self::NBSP . '€'],
            ['input' => Money::create('12.630000'), 'domainId' => 2, 'result' => '12,63' . self::NBSP . '€'],
            ['input' => Money::create('12.638000'), 'domainId' => 2, 'result' => '12,638' . self::NBSP . '€'],
            ['input' => Money::create('12.630000'), 'domainId' => 2, 'result' => '12,63' . self::NBSP . '€'],
            [
                'input' => Money::create('123456789.123456789'),
                'domainId' => 2,
                'result' => '123' . self::NBSP . '456' . self::NBSP . '789,123456789' . self::NBSP . '€',
            ],
            [
                'input' => Money::create('123456789.123456789123456789'),
                'domainId' => 2,
                'result' => '123' . self::NBSP . '456' . self::NBSP . '789,1234567891' . self::NBSP . '€',
            ],
        ];

        return array_merge($filterDataSingleDomain, $filterDataMultiDomain);
    }

    /**
     * @group singledomain
     * @dataProvider priceFilterDataProviderSingleDomain
     * @param mixed $input
     * @param mixed $domainId
     * @param mixed $result
     */
    public function testPriceFilterForSingleDomain($input, $domainId, $result)
    {
        $this->checkPriceFilter($input, $domainId, $result);
    }

    /**
     * @group multidomain
     * @dataProvider priceFilterDataProviderMultiDomain
     * @param mixed $input
     * @param mixed $domainId
     * @param mixed $result
     */
    public function testPriceFilterForMultiDomain($input, $domainId, $result)
    {
        $this->checkPriceFilter($input, $domainId, $result);
    }

    /**
     * @param mixed $input
     * @param mixed $domainId
     * @param mixed $result
     */
    private function checkPriceFilter($input, $domainId, $result)
    {
        $this->domain->switchDomainById($domainId);

        $priceExtension = new PriceExtension(
            $this->currencyFacade,
            $this->domain,
            $this->localization,
            $this->numberFormatRepository,
            $this->intlCurrencyRepository
        );

        $this->assertSame($result, $priceExtension->priceFilter($input));
    }
}
