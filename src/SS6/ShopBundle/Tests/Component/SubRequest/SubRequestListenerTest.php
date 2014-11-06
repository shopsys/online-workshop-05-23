<?php

namespace SS6\ShopBundle\Tests\Component\Subrequest;

use PHPUnit_Framework_TestCase;
use SS6\ShopBundle\Component\SubRequest\SubRequestListener;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SubRequestListenerTest extends PHPUnit_Framework_TestCase {

	/**
	 * @param bool $redirect
	 * @param bool $send
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function getResponseMock($redirect = false, $send = false) {
		$responseMock = $this->getMockBuilder(Response::class)
			->setMethods(['isRedirection', 'send'])
			->getMock();
		$responseMock->expects($this->once())->method('isRedirection')->will($this->returnValue($redirect));
		$responseMock->expects($send ? $this->once() : $this->never())->method('send');

		return $responseMock;
	}

	public function testOnKernelResponseOneMasterResponse() {
		$eventMock = $this->getMockBuilder(FilterResponseEvent::class)
			->setMethods(['__construct', 'isMasterRequest'])
			->disableOriginalConstructor()
			->getMock();
		$eventMock->expects($this->once())->method('isMasterRequest')->will($this->returnValue(true));

		$subRequestListener = new SubRequestListener();
		$subRequestListener->onKernelResponse($eventMock);
	}

	public function testOnKernelResponseManyRedirectResponses() {
		$eventMock1 = $this->getMockBuilder(FilterResponseEvent::class)
			->setMethods(['__construct', 'isMasterRequest', 'getResponse'])
			->disableOriginalConstructor()
			->getMock();
		$eventMock1->expects($this->once())->method('isMasterRequest')->will($this->returnValue(false));
		$eventMock1->expects($this->once())->method('getResponse')->will($this->returnValue($this->getResponseMock(true)));

		$eventMock2 = $this->getMockBuilder(FilterResponseEvent::class)
			->setMethods(['__construct', 'isMasterRequest', 'getResponse'])
			->disableOriginalConstructor()
			->getMock();
		$eventMock2->expects($this->once())->method('isMasterRequest')->will($this->returnValue(false));
		$eventMock2->expects($this->once())->method('getResponse')->will($this->returnValue($this->getResponseMock()));

		$eventMock3 = $this->getMockBuilder(FilterResponseEvent::class)
			->setMethods(['__construct', 'isMasterRequest', 'getResponse'])
			->disableOriginalConstructor()
			->getMock();
		$eventMock3->expects($this->once())->method('isMasterRequest')->will($this->returnValue(false));
		$eventMock3->expects($this->once())->method('getResponse')->will($this->returnValue($this->getResponseMock(true)));

		$subRequestListener = new SubRequestListener();
		$subRequestListener->onKernelResponse($eventMock1);
		$subRequestListener->onKernelResponse($eventMock2);

		$this->setExpectedException(\SS6\ShopBundle\Component\SubRequest\Exception\TooManyRedirectResponsesException::class);
		$subRequestListener->onKernelResponse($eventMock3);
	}

	public function testOnKernelResponse() {
		$eventMock1 = $this->getMockBuilder(FilterResponseEvent::class)
			->setMethods(['__construct', 'isMasterRequest', 'getResponse'])
			->disableOriginalConstructor()
			->getMock();
		$eventMock1->expects($this->once())->method('isMasterRequest')->will($this->returnValue(false));
		$eventMock1->expects($this->once())->method('getResponse')->will($this->returnValue($this->getResponseMock(true, true)));

		$eventMock2 = $this->getMockBuilder(FilterResponseEvent::class)
			->setMethods(['__construct', 'isMasterRequest', 'getResponse'])
			->disableOriginalConstructor()
			->getMock();
		$eventMock2->expects($this->once())->method('isMasterRequest')->will($this->returnValue(false));
		$eventMock2->expects($this->once())->method('getResponse')->will($this->returnValue($this->getResponseMock()));

		$eventMock3 = $this->getMockBuilder(FilterResponseEvent::class)
			->setMethods(['__construct', 'isMasterRequest'])
			->disableOriginalConstructor()
			->getMock();
		$eventMock3->expects($this->once())->method('isMasterRequest')->will($this->returnValue(true));

		$subRequestListener = new SubRequestListener();
		$subRequestListener->onKernelResponse($eventMock1);
		$subRequestListener->onKernelResponse($eventMock2);
		$subRequestListener->onKernelResponse($eventMock3);
	}

	public function testOnKernelController() {
		$masterRequestMock = $this->getMockBuilder(Request::class)
			->setMethods(['getMethod'])
			->getMock();
		$masterRequestMock->expects($this->once())->method('getMethod')->will($this->returnValue('POST'));
		$masterRequestMock->query->replace([
			'key1' => 'value1',
			'key2' => 'value2',
		]);
		$masterRequestMock->request->replace(['post' => 'value']);

		$subRequestMock = $this->getMockBuilder(Request::class)
			->setMethods(['setMethod'])
			->getMock();
		$subRequestMock->expects($this->once())->method('setMethod')->with($this->equalTo('POST'));
		$subRequestMock->query->replace([
			'key2' => 'value2_2',
			'key3' => 'value3',
		]);

		$eventMock1 = $this->getMockBuilder(FilterControllerEvent::class)
			->setMethods(['__construct', 'isMasterRequest', 'getRequest'])
			->disableOriginalConstructor()
			->getMock();
		$eventMock1->expects($this->once())->method('isMasterRequest')->will($this->returnValue(true));
		$eventMock1->expects($this->atLeastOnce())->method('getRequest')->will($this->returnValue($masterRequestMock));

		$eventMock2 = $this->getMockBuilder(FilterControllerEvent::class)
			->setMethods(['__construct', 'isMasterRequest', 'getRequest'])
			->disableOriginalConstructor()
			->getMock();
		$eventMock2->expects($this->once())->method('isMasterRequest')->will($this->returnValue(false));
		$eventMock2->expects($this->atLeastOnce())->method('getRequest')->will($this->returnValue($subRequestMock));

		$subRequestListener = new SubRequestListener();
		$subRequestListener->onKernelController($eventMock1);
		$subRequestListener->onKernelController($eventMock2);

		$expected = [
			'key1' => 'value1',
			'key2' => 'value2_2',
			'key3' => 'value3',
		];
		$this->assertEquals($expected, $subRequestMock->query->all());
		$this->assertEquals($masterRequestMock->request, $subRequestMock->request);
	}

}