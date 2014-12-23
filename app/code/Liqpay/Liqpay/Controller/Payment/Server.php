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

class Server extends \Liqpay\Liqpay\Controller\Payment
{
    /**
     * Validate data from Liqpay server and update the database
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost()) {
            $this->norouteAction();
            return;
        }

        $this->getLiqpay()->processNotification($this->getRequest()->getPost());
    }
}
