<?php
/**
 * Created by PhpStorm.
 * User: Peter Vayda
 * Date: 21.11.18
 * Time: 23:38
 */

namespace Coinpayments\CoinPayments\Model\Api;


use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Coinpayments\CoinPayments\Logger\Logger;
use Coinpayments\CoinPayments\Model\Api\Rate as ApiRate;
use Magento\Checkout\Model\Session;

class Transaction extends Base
{
    /* @var UrlInterface */
    protected $_urlBuilder;
    /* @var Logger */
    protected $_logger;
    /* @var ApiRate */
    protected $_rate;

    /* @var \Magento\Checkout\Model\Session */
    protected $_checkoutSession;

    /**
     * Transaction constructor.
     * @param Curl $curl
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $urlBuilder
     * @param Logger $logger
     * @param Rate $rate
     */
    public function __construct(
        Curl $curl,
        ScopeConfigInterface $scopeConfig,
        UrlInterface $urlBuilder,
        Logger $logger,
        ApiRate $rate,
        Session $checkoutSession
    )
    {
        $this->_urlBuilder = $urlBuilder;
        $this->_logger = $logger;
        $this->_rate = $rate;
        parent::__construct($curl, $scopeConfig);
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * @param Order $order
     * @param string $customerCurrency
     * @param array $headers
     * @return array|mixed
     */
    public function create($order, $customerCurrency, $headers = [])
    {
        $data = [
            'amount' => $this->_rate->getConverted($customerCurrency, $order->getBaseGrandTotal()),
            'currency1' => $customerCurrency,
            'currency2' => $this->getTransactionCurrency(),//$this->getPaymentConfig('receive_currency'),
            'buyer_email' => $order->getCustomerEmail(),
            'buyer_name' => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname(),
            'invoice' => $order->getIncrementId(),
            'ipn_url' => $this->_urlBuilder->getUrl('coinpayments/ipn/handle')
        ];

        $this->_logger->info('ORDER ' . $order->getId() . ' transaction request: ' . print_r($data, true));
        $data = $this->sendRequest($data, 'create_transaction', $headers, 'post', false);
        $this->_logger->info('ORDER ' . $order->getId() . ' transaction response: ' . print_r($data, true));
        return $data;
    }
    
    public function getTransactionCurrency()
    {
        return $this->_checkoutSession->getCoinpaymentsCurrency();
    }
}
