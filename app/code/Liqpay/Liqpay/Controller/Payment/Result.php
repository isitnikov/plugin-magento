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
namespace Liqpay\Liqpay\Controller\Payment;

class Result extends \Liqpay\Liqpay\Controller\Payment
{
    /**
     * Customer successfully got back from LiqPay payment interface
     */
    public function execute()
    {
        $order_id = $this->_checkoutSession->getLiqpayLastRealOrderId();
        $quote_id = $this->_checkoutSession->getLiqpayQuoteId(true);

        $order = $this->getOrder();
        $order->loadByIncrementId($order_id);

        if ($order->isEmpty()) {
            return false;
        }

        $order->addStatusToHistory(
            $order->getStatus(),
            __('Customer successfully got back from Liqpay payment interface.')
        )->save();

        $session->setQuoteId($quote_id);
        $session->getQuote()->setIsActive(false)->save();
        $session->setLastRealOrderId($order_id);

        $this->_redirect('checkout/onepage/success', array('_secure' => true));
    }
}
