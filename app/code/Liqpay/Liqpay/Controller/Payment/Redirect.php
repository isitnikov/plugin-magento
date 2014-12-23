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

class Redirect extends \Liqpay\Liqpay\Controller\Payment
{
    /**
     * Redirect customer to Liqpay payment interface
     */
    public function execute()
    {
        $session = $this->getSession();

        $quote_id = $session->getQuoteId();
        $last_real_order_id = $session->getLastRealOrderId();

        if (is_null($quote_id) || is_null($last_real_order_id)) {
            $this->_redirect('checkout/cart/');
        } else {
            $session->setLiqpayQuoteId($quote_id);
            $session->setLiqpayLastRealOrderId($last_real_order_id);

            $order = $this->getOrder();
            $order->loadByIncrementId($last_real_order_id);

            $html = $this->_view->getLayout()
                ->createBlock('Liqpay\Liqpay\Block\Redirect')->toHtml();

            $this->getResponse()->setHeader('Content-type', 'text/html; charset=windows-1251')
                ->setBody($html);

            $order->addStatusToHistory(
                $order->getStatus(),
                __('Customer switch over to Liqpay payment interface.')
            )->save();

            $session->getQuote()->setIsActive(false)->save();

            $session->setQuoteId(null);
            $session->setLastRealOrderId(null);
        }
    }
}
