<?php

namespace SS6\ShopBundle\DataFixtures\DemoMultidomain;

use Doctrine\Common\Persistence\ObjectManager;
use SS6\ShopBundle\Component\DataFixture\AbstractReferenceFixture;
use SS6\ShopBundle\DataFixtures\Base\CurrencyDataFixture;
use SS6\ShopBundle\DataFixtures\Base\OrderStatusDataFixture;
use SS6\ShopBundle\DataFixtures\Demo\CountryDataFixture;
use SS6\ShopBundle\DataFixtures\Demo\OrderDataFixture as DemoOrderDataFixture;
use SS6\ShopBundle\DataFixtures\Demo\PaymentDataFixture;
use SS6\ShopBundle\DataFixtures\Demo\ProductDataFixture;
use SS6\ShopBundle\DataFixtures\Demo\TransportDataFixture;
use SS6\ShopBundle\Model\Customer\User;
use SS6\ShopBundle\Model\Customer\UserRepository;
use SS6\ShopBundle\Model\Order\Item\QuantifiedProduct;
use SS6\ShopBundle\Model\Order\OrderData;
use SS6\ShopBundle\Model\Order\OrderFacade;
use SS6\ShopBundle\Model\Order\Preview\OrderPreviewFactory;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OrderDataFixture extends AbstractReferenceFixture {

	/**
	 * @param \Doctrine\Common\Persistence\ObjectManager $manager
	 *
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	public function load(ObjectManager $manager) {
		$userRepository = $this->get(UserRepository::class);
		/* @var $userRepository \SS6\ShopBundle\Model\Customer\UserRepository */

		$orderData = new OrderData();
		$orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_CZECH_POST);
		$orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CASH_ON_DELIVERY);
		$orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_IN_PROGRESS);
		$orderData->firstName = 'Václav';
		$orderData->lastName = 'Svěrkoš';
		$orderData->email = 'no-reply@netdevelo.cz';
		$orderData->telephone = '+420725711368';
		$orderData->street = 'Devátá 25';
		$orderData->city = 'Ostrava';
		$orderData->postcode = '71200';
		$orderData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC_2);
		$orderData->deliveryAddressSameAsBillingAddress = true;
		$orderData->domainId = 2;
		$orderData->currency = $this->getReference(CurrencyDataFixture::CURRENCY_EUR);
		$this->createOrder(
			$orderData,
			[
				ProductDataFixture::PRODUCT_PREFIX . '14' => 1,
			]
		);

		$user = $userRepository->findUserByEmailAndDomain('no-reply.2@netdevelo.cz', 2);
		$orderData = new OrderData();
		$orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_PERSONAL);
		$orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CASH);
		$orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_NEW);
		$orderData->firstName = 'Jan';
		$orderData->lastName = 'Novák';
		$orderData->email = 'no-reply@netdevelo.cz';
		$orderData->telephone = '+420123456789';
		$orderData->street = 'Pouliční 11';
		$orderData->city = 'Městník';
		$orderData->postcode = '12345';
		$orderData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC_2);
		$orderData->companyName = 'netdevelo s.r.o.';
		$orderData->companyNumber = '123456789';
		$orderData->companyTaxNumber = '987654321';
		$orderData->deliveryAddressSameAsBillingAddress = false;
		$orderData->deliveryContactPerson = 'Karel Vesela';
		$orderData->deliveryCompanyName = 'Bestcompany';
		$orderData->deliveryTelephone = '+420987654321';
		$orderData->deliveryStreet = 'Zakopaná 42';
		$orderData->deliveryCity = 'Zemín';
		$orderData->deliveryPostcode = '54321';
		$orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA_2);
		$orderData->note = 'Prosím o dodání do pátku. Děkuji.';
		$orderData->domainId = 2;
		$orderData->currency = $this->getReference(CurrencyDataFixture::CURRENCY_CZK);
		$this->createOrder(
			$orderData,
			[
				ProductDataFixture::PRODUCT_PREFIX . '1' => 2,
				ProductDataFixture::PRODUCT_PREFIX . '3' => 1,
			],
			$user
		);

		$user = $userRepository->findUserByEmailAndDomain('no-reply.7@netdevelo.cz', 2);
		$orderData = new OrderData();
		$orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_CZECH_POST);
		$orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CASH_ON_DELIVERY);
		$orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_NEW);
		$orderData->firstName = 'Jindřich';
		$orderData->lastName = 'Němec';
		$orderData->email = 'no-reply@netdevelo.cz';
		$orderData->telephone = '+420123456789';
		$orderData->street = 'Sídlištní 3259';
		$orderData->city = 'Orlová';
		$orderData->postcode = '65421';
		$orderData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC_2);
		$orderData->deliveryAddressSameAsBillingAddress = true;
		$orderData->domainId = 2;
		$orderData->currency = $this->getReference(CurrencyDataFixture::CURRENCY_EUR);
		$this->createOrder(
			$orderData,
			[
				ProductDataFixture::PRODUCT_PREFIX . '2' => 2,
				ProductDataFixture::PRODUCT_PREFIX . '4' => 4,
			],
			$user
		);

		$orderData = new OrderData();
		$orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_PERSONAL);
		$orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CASH);
		$orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_CANCELED);
		$orderData->firstName = 'Viktor';
		$orderData->lastName = 'Pátek';
		$orderData->email = 'no-reply@netdevelo.cz';
		$orderData->telephone = '+420888777111';
		$orderData->street = 'Vyhlídková 88';
		$orderData->city = 'Ostrava';
		$orderData->postcode = '71201';
		$orderData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC_2);
		$orderData->deliveryAddressSameAsBillingAddress = true;
		$orderData->domainId = 2;
		$orderData->currency = $this->getReference(CurrencyDataFixture::CURRENCY_EUR);
		$this->createOrder(
			$orderData,
			[
				ProductDataFixture::PRODUCT_PREFIX . '3' => 10,
			]
		);
	}

	/**
	 * @param \SS6\ShopBundle\Model\Order\OrderData $orderData
	 * @param array $products
	 * @param \SS6\ShopBundle\Model\Customer\User $user
	 */
	private function createOrder(
		OrderData $orderData,
		array $products,
		User $user = null
	) {
		$orderFacade = $this->get(OrderFacade::class);
		/* @var $orderFacade \SS6\ShopBundle\Model\Order\OrderFacade */
		$orderPreviewFactory = $this->get(OrderPreviewFactory::class);
		/* @var $orderPreviewFactory \SS6\ShopBundle\Model\Order\Preview\OrderPreviewFactory */

		$quantifiedProducts = [];
		foreach ($products as $productReferenceName => $quantity) {
			$product = $this->getReference($productReferenceName);
			$quantifiedProducts[] = new QuantifiedProduct($product, $quantity);
		}
		$orderPreview = $orderPreviewFactory->create(
			$orderData->currency,
			$orderData->domainId,
			$quantifiedProducts,
			$orderData->transport,
			$orderData->payment,
			$user,
			null
		);

		$order = $orderFacade->createOrder($orderData, $orderPreview, $user);
		/* @var $order \SS6\ShopBundle\Model\Order\Order */

		$referenceName = DemoOrderDataFixture::ORDER_PREFIX . $order->getId();
		$this->addReference($referenceName, $order);
	}

}
