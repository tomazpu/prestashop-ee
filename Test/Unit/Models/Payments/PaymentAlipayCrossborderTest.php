<?php
/**
 * Shop System Extensions:
 * - Terms of Use can be found at:
 * https://github.com/wirecard/prestashop-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/prestashop-ee/blob/master/LICENSE
 */

use WirecardEE\Prestashop\Models\PaymentAlipayCrossborder;

class PaymentAlipayCrossborderTest extends PHPUnit_Framework_TestCase
{
    private $payment;

    private $paymentModule;

    private $config;

    public function setUp()
    {
        $this->config = array(
            'base_url',
            'http_user',
            'http_pass',
            'merchant_account_id',
            'secret'
        );
        $this->paymentModule = $this->getMockBuilder(\WirecardPaymentGateway::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentModule->version = \WirecardPaymentGateway::VERSION;

        $this->payment = new PaymentAlipayCrossborder($this->paymentModule);

        $this->transactionData = new stdClass();
        $this->transactionData->transaction_id = 'my_secret_id';
        $this->transactionData->order_id = 'my_secret_order_id';
        $this->transactionData->cart_id = new stdClass();
        $this->transactionData->cart_id->id_customer = 11;
        $this->transactionData->cart_id->id_address_invoice = 12;
        $this->transactionData->cart_id->id_address_delivery = 13;
        $this->transactionData->amount = 20;
        $this->transactionData->currency = 'EUR';
    }

    public function testName()
    {
        $actual = $this->payment->getName();

        $expected = 'Wirecard Alipay Crossborder';

        $this->assertEquals($expected, $actual);
    }

    public function testFormFields()
    {
        $actual = $this->payment->getFormFields();
        $this->assertTrue(is_array($actual));
    }

    public function testCreateTransaction()
    {
        /** @var Wirecard\PaymentSdk\Transaction\Transaction $actual */
        $actual = $this->payment->createTransaction(new PaymentModule(), new Cart(), array(), 'ADB123');

        $expected = 'alipay-xborder';

        $this->assertEquals($expected, $actual::NAME);
    }


    public function testCreateCancelTransaction()
    {
        $expected = new \Wirecard\PaymentSdk\Transaction\AlipayCrossborderTransaction();

        $expected->setParentTransactionId('my_secret_id');

        $actual = $this->payment->createCancelTransaction(
            $this->transactionData,
            $this->paymentModule
        );

        $this->assertEquals($expected, $actual);
    }
}
