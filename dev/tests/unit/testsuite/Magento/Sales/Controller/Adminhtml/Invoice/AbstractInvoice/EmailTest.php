<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Sales\Controller\Adminhtml\Invoice\AbstractInvoice;

use Magento\Framework\App\Action\Context;
use Magento\TestFramework\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Class EmailTest
 *
 * @package Magento\Sales\Controller\Adminhtml\Invoice\AbstractInvoice
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Email
     */
    protected $invoiceEmail;

    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\ResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $response;

    /**
     * @var \Magento\Framework\Message\Manager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\ObjectManager\ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    /**
     * @var \Magento\Backend\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $session;

    /**
     * @var \Magento\Framework\App\ActionFlag|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $actionFlag;

    /**
     * @var \Magento\Backend\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $helper;

    /**
     * @var \Magento\Backend\Model\View\Result\Redirect|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultRedirect;

    /**
     * @var \Magento\Backend\Model\View\Result\RedirectFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultRedirectFactory;

    /**
     * @var \Magento\Backend\Model\View\Result\Forward|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultForward;

    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultForwardFactory;

    public function setUp()
    {
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->context = $this->getMock('Magento\Backend\App\Action\Context', [], [], '', false);
        $this->response = $this->getMock('Magento\Framework\App\ResponseInterface', [], [], '', false);
        $this->request = $this->getMock('Magento\Framework\App\RequestInterface', [], [], '', false);
        $this->objectManager = $this->getMock('Magento\Framework\ObjectManager\ObjectManager', [], [], '', false);
        $this->messageManager = $this->getMock('Magento\Framework\Message\Manager', [], [], '', false);
        $this->session = $this->getMock('Magento\Backend\Model\Session', ['setIsUrlNotice'], [], '', false);
        $this->actionFlag = $this->getMock('Magento\Framework\App\ActionFlag', [], [], '', false);
        $this->helper = $this->getMock('\Magento\Backend\Helper\Data', [], [], '', false);
        $this->context->expects($this->once())
            ->method('getMessageManager')
            ->willReturn($this->messageManager);
        $this->context->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($this->objectManager);
        $this->context->expects($this->once())
            ->method('getSession')
            ->willReturn($this->session);
        $this->context->expects($this->once())
            ->method('getActionFlag')
            ->willReturn($this->actionFlag);
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->context->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->response);
        $this->context->expects($this->once())
            ->method('getHelper')
            ->willReturn($this->helper);

        $this->resultRedirect = $this->getMockBuilder('Magento\Backend\Model\View\Result\Redirect')
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory = $this->getMockBuilder('Magento\Backend\Model\View\Result\RedirectFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->resultForward = $this->getMockBuilder('Magento\Backend\Model\View\Result\Forward')
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultForwardFactory = $this->getMockBuilder('Magento\Backend\Model\View\Result\ForwardFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->invoiceEmail = $objectManagerHelper->getObject(
            'Magento\Sales\Controller\Adminhtml\Order\Invoice\Email',
            [
                'context' => $this->context,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'resultForwardFactory' => $this->resultForwardFactory,
            ]
        );
    }

    public function testEmail()
    {
        $invoiceId = 10000031;
        $orderId = 100000030;
        $invoiceClassName = 'Magento\Sales\Model\Order\Invoice';
        $cmNotifierClassName = 'Magento\Sales\Model\Order\InvoiceNotifier';
        $invoice = $this->getMock($invoiceClassName, [], [], '', false);
        $notifier = $this->getMock($cmNotifierClassName, [], [], '', false);
        $order = $this->getMock('Magento\Sales\Model\Order', [], [], '', false);
        $order->expects($this->once())
            ->method('getId')
            ->willReturn($orderId);

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('invoice_id')
            ->willReturn($invoiceId);
        $this->objectManager->expects($this->at(0))
            ->method('create')
            ->with($invoiceClassName)
            ->willReturn($invoice);
        $invoice->expects($this->once())
            ->method('load')
            ->with($invoiceId)
            ->willReturnSelf();
        $invoice->expects($this->once())
            ->method('getOrder')
            ->willReturn($order);
        $this->objectManager->expects($this->at(1))
            ->method('create')
            ->with($cmNotifierClassName)
            ->willReturn($notifier);
        $notifier->expects($this->once())
            ->method('notify')
            ->willReturn(true);
        $this->messageManager->expects($this->once())
            ->method('addSuccess')
            ->with('We sent the message.');

        $this->resultRedirectFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->resultRedirect);
        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('sales/invoice/view', ['order_id' => $orderId, 'invoice_id' => $invoiceId])
            ->willReturnSelf();
        $this->assertInstanceOf('Magento\Backend\Model\View\Result\Redirect', $this->invoiceEmail->execute());
    }

    public function testEmailNoInvoiceId()
    {
        $this->request->expects($this->once())
            ->method('getParam')
            ->with('invoice_id')
            ->willReturn(null);
        $this->resultForwardFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->resultForward);
        $this->resultForward->expects($this->once())
            ->method('forward')
            ->with('noroute')
            ->willReturnSelf();

        $this->assertInstanceOf('Magento\Backend\Model\View\Result\Forward', $this->invoiceEmail->execute());
    }

    public function testEmailNoInvoice()
    {
        $invoiceId = 10000031;
        $invoiceClassName = 'Magento\Sales\Model\Order\Invoice';
        $invoice = $this->getMock($invoiceClassName, [], [], '', false);

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('invoice_id')
            ->willReturn($invoiceId);
        $this->objectManager->expects($this->at(0))
            ->method('create')
            ->with($invoiceClassName)
            ->willReturn($invoice);
        $invoice->expects($this->once())
            ->method('load')
            ->with($invoiceId)
            ->willReturn(null);
        $this->resultForwardFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->resultForward);
        $this->resultForward->expects($this->once())
            ->method('forward')
            ->with('noroute')
            ->willReturnSelf();

        $this->assertInstanceOf('Magento\Backend\Model\View\Result\Forward', $this->invoiceEmail->execute());
    }
}
