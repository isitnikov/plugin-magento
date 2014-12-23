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
 * Payment method liqpay redirect
 *
 * @author      Liqpay <support@liqpay.com>
 */

namespace Liqpay\Liqpay\Block;

class Redirect extends \Magento\Framework\View\Element\Template
{
    /** @var \Liqpay\Liqpay\Model\Payment */
    protected $_payment;

    /** @var \Magento\Framework\Data\FormFactory */
    protected $_formFactory;

    /**
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Liqpay\Liqpay\Model\Payment $payment
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Data\FormFactory $formFactory,
        \Liqpay\Liqpay\Model\Payment $payment,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_formFactory = $formFactory;
        $this->_payment = $payment;
    }

    /**
     * Set template with message
     */
    protected function _construct()
    {
        $this->setTemplate('redirect.phtml');
        parent::_construct();
    }

    /**
     * Return redirect form
     *
     * @return \Magento\Framework\Data\Form
     */
    public function getForm()
    {
        $form = $this->_formFactory->create();
        $form->setAction($this->_payment->getLiqpayPlaceUrl())
             ->setId('liqpay_redirect')
             ->setName('liqpay_redirect')
             ->setData('accept-charset', 'utf-8')
             ->setUseContainer(true)
             ->setMethod('POST');

        foreach ($this->_payment->getRedirectFormFields() as $field => $value) {
            $form->addField($field, 'hidden', array(
                'name' => $field,
                'value' => $value
            ));
        }

        return $form;
    }
}
