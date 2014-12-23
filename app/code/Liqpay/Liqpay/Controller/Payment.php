<?php
/**
* Liqpay Payment Module
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
*
* @category        Liqpay
* @package         Liqpay_Liqpay
* @version         0.0.1
* @author          Liqpay
* @copyright       Copyright (c) 2014 Liqpay
* @license         http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*
* EXTENSION INFORMATION
*
* Magento          Community Edition 1.8.1.0
* LiqPay API       Click&Buy 1.2 (https://www.liqpay.com/ru/doc)
* Way of payment   Visa / MasterCard, or LiqPay
*
*/

/**
* Payment method liqpay controller
*
* @author      Liqpay <support@liqpay.com>
*/
namespace Liqpay\Liqpay\Controller;

class Payment extends \Magento\Framework\App\Action\Action
{
    /** @var \Magento\Checkout\Model\Session */
    protected $_checkoutSession;

    /** @var \Magento\Sales\Model\Order */
    protected $_order;

    /** @var \Magento\Sales\Model\OrderFactory */
    protected $_orderFactory;

    /** @var \Liqpay\Liqpay\Method\Payment */
    protected $_payment;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Liqpay\Liqpay\Model\Payment $payment
    ) {
        parent::__construct($context);

        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_payment = $payment;
    }

    /**
     * Get Checkout Session
     *
     * @return \Magento\Checkout\Model\Session
     */
    public function getSession()
    {
        return $this->_checkoutSession;
    }

    /**
     * Get Order
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        if ($this->_order == null) {
            $session = $this->getSession();
            $this->_order = $this->_orderFactory->create();
            $this->_order->loadByIncrementId($session->getLastRealOrderId());
        }
        return $this->_order;
    }

    /**
     * Get Payment Method
     *
     * @return \Liqpay\Liqpay\Model\Payment
     */
    public function getLiqpay()
    {
        return $this->_payment;
    }
}
