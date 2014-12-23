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
 * Payment method liqpay model
 *
 * @author      Liqpay <support@liqpay.com>
 */

namespace Liqpay\Liqpay\Model;

class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const STATUS_SUCCESS     = 'success';
    const STATUS_FAILURE     = 'failure';
    const STATUS_WAIT_SECURE = 'wait_secure';

    /**
     * Payment Method features
     * @var bool
     */
    protected $_canCapture              = true;
    protected $_canVoid                 = true;
    protected $_canUseForMultishipping  = false;
    protected $_canUseInternal          = false;
    protected $_isInitializeNeeded      = true;
    protected $_isGateway               = false;
    protected $_canAuthorize            = false;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canRefundInvoicePartial = false;
    protected $_canUseCheckout          = true;

    protected $_code = 'liqpay';
    protected $_formBlockType = '\Liqpay\Liqpay\Block\PaymentInformation';
    protected $_allowCurrencyCode = array('EUR','UAH','USD','RUB','RUR');
    protected $_order;

    /** @var \Magento\Sales\Model\OrderFactory */
    protected $_orderFactory;

    /** @var \Magento\Sales\Model\Order\Config */
    protected $_orderConfig;

    /** @var \Magento\Checkout\Model\Session */
    protected $_checkoutSession;

    /** @var \Magento\Framework\UrlInterface */
    protected $_frontendUrlBuilder;

    protected $_transactionFactory;

    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Logger\AdapterFactory $logAdapterFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\UrlInterface $frontendUrlBuilder,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        array $data = []
    ) {
        parent::__construct(
            $eventManager,
            $paymentData,
            $scopeConfig,
            $logAdapterFactory,
            $data
        );

        $this->_orderFactory = $orderFactory;
        $this->_orderConfig = $orderConfig;
        $this->_checkoutSession = $checkoutSession;
        $this->_frontendUrlBuilder = $frontendUrlBuilder;
        $this->_transactionFactory = $transactionFactory;
    }

    /**
    * Возвращает набор полей необходимых для передачи
    *
    * @return array
    */
    public function getRedirectFormFields()
    {
        /** @var \Magento\Checkout\Model\Session $session */
        $session = $this->_checkoutSession;
        $order = $this->_orderFactory->create()
            ->loadByIncrementId($session->getLastRealOrderId());

        if (!$order->getId()) {
            return array();
        }

        $private_key = $this->getConfigData('liqpay_private_key');
        $public_key  = $this->getConfigData('liqpay_public_key');
        $amount      = $order->getBaseGrandTotal();
        $currency    = $order->getOrderCurrencyCode();

        if ($currency == 'RUR') {
            $currency = 'RUB';
        }

        $order_id    = $order->getIncrementId();
        $description = __('Заказ №') . $order_id;
        $result_url  = $this->_frontendUrlBuilder->getUrl('liqpay/payment/result');
        $server_url  = $this->_frontendUrlBuilder->getUrl('liqpay/payment/server');

        $type = 'buy';

        $signature = base64_encode(sha1(join('',compact(
            'private_key',
            'amount',
            'currency',
            'public_key',
            'order_id',
            'type',
            'description',
            'result_url',
            'server_url'
        )),1));

        $language = 'ru';

        return compact(
            'public_key','amount','currency','description','order_id',
           'result_url','server_url','type','signature','language'
        );
    }


    /**
    * Get redirect url.
    * Return Order place redirect url.
    *
    * @return string
    */
    public function getOrderPlaceRedirectUrl()
    {
        return $this->_frontendUrlBuilder->getUrl('liqpay/payment/redirect', array('_secure' => true));
    }

    /**
     * Return Liqpay place URL
     *
     * @return string
     */
    public function getLiqpayPlaceUrl()
    {
        return $this->getConfigData('liqpay_action');
    }


    /**
     * Method that will be executed instead of authorize or capture
     * if flag isInitializeNeeded set to true
     *
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return Payment
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = \Magento\Sales\Model\Order::STATE_NEW;
        $stateObject->setState($state);
        $stateObject->setStatus($this->_orderConfig->getStateDefaultStatus($state));
        $stateObject->setIsNotified(false);
        return $this;
    }

    /**
     * Validate data from LiqPay server and update the database
     */
    public function processNotification($post)
    {
        $success =
            isset($post['signature']) &&
            isset($post['sender_phone']) &&
            isset($post['transaction_id']) &&
            isset($post['status']) &&
            isset($post['order_id']) &&
            isset($post['type']) &&
            isset($post['description']) &&
            isset($post['currency']) &&
            isset($post['amount']) &&
            isset($post['public_key']);

        if (!$success) {
            throw new \Magento\Framework\Model\Exception(__('Needed fields are not set'));
        }

        $signature      = $post['signature'];
        $sender_phone   = $post['sender_phone'];
        $transaction_id = $post['transaction_id'];
        $status         = $post['status'];
        $order_id       = $post['order_id'];
        $type           = $post['type'];
        $description    = $post['description'];
        $currency       = $post['currency'];
        $amount         = $post['amount'];
        $public_key     = $post['public_key'];

        if ($order_id <= 0) {
            throw new \Magento\Framework\Model\Exception(__('Wrong order id'));
        }

        $order = $this->_orderFactory->create();
        $order->loadByIncrementId($order_id);

        if (!$order->getId()) {
            throw new \Magento\Framework\Model\Exception(__('Cannot get order id'));
        }

        $private_key = $this->getConfigData('liqpay_private_key');

        $gensig = base64_encode(sha1(join('',compact(
            'private_key',
            'amount',
            'currency',
            'public_key',
            'order_id',
            'type',
            'description',
            'status',
            'transaction_id',
            'sender_phone'
        )),1));

        if ($signature != $gensig) {
            $order->addStatusToHistory(
                $order->getStatus(),
                __('Security check failed!')
            )->save();
            return;
        }

        $newOrderStatus = $this->getConfigData('order_status', $order->getStoreId());
        if (empty($newOrderStatus)) {
            $newOrderStatus = $order->getStatus();
        }

        switch ($status) {
            case self::STATUS_SUCCESS:
                if ($order->canInvoice()) {
                    $order->getPayment()->setTransactionId($transaction_id);
                    $invoice = $order->prepareInvoice();
                    $invoice->register()->pay();
                    $this->_transactionFactory->create()
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder())
                        ->save();

                    $order->setState(
                        \Magento\Sales\Model\Order::STATE_PROCESSING, true,
                        __('Invoice #%s created.', $invoice->getIncrementId()),
                        $notified = true
                    );

                    $sDescription = '';
                    $sDescription .= 'sender phone: '.$sender_phone.'; ';
                    $sDescription .= 'amount: '.$amount.'; ';
                    $sDescription .= 'currency: '.$currency.'; ';

                    $order->addStatusToHistory(
                        $order->getStatus(),
                        $sDescription
                    )->save();
                } else {
                    $order->addStatusToHistory(
                        $order->getStatus(),
                        __('Error during creation of invoice.', true),
                        $notified = true
                    );
                }
                break;

            case self::STATUS_FAILURE:
                $order->setState(
                    \Magento\Sales\Model\Order::STATE_CANCELED, $newOrderStatus,
                    __('Liqpay error.'),
                    $notified = true
                );
                break;

            case self::STATUS_WAIT_SECURE:
                $order->setState(
                    \Magento\Sales\Model\Order::STATE_PROCESSING, $newOrderStatus,
                    __('Waiting for verification from the Liqpay side.'),
                    $notified = true
                );
                break;

        }

        $order->save();
    }
}
