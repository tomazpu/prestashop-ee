<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 *
 * @author Wirecard AG
 * @copyright Wirecard AG
 * @license GPLv3
 */

use WirecardEE\Prestashop\Models\Payment;
use Wirecard\PaymentSdk\Config\Config;
use WirecardEE\Prestashop\Models\PaymentPaypal;

class PaymentTest extends \PHPUnit_Framework_TestCase
{
    private $payment;
    private $config;
    private $paypalPayment;
    private $paymentModule;

    public function setUp()
    {
        $this->paymentModule = $this->getMockBuilder(\WirecardPaymentGateway::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentModule->version = EXPECTED_PLUGIN_VERSION;

        $this->payment = new Payment($this->paymentModule);
        $this->payment->context = new \Context();
        $this->config = new Config('baseUrl', 'httpUser', 'httpPass');
        $this->config->setShopInfo(EXPECTED_SHOP_NAME, _PS_VERSION_);
        $this->config->setPluginInfo(EXPECTED_PLUGIN_NAME, $this->paymentModule->version);
        $this->paypalPayment = new PaymentPaypal($this->paymentModule);
    }

    public function testName()
    {
        $actual = $this->payment->getName();

        $expected = 'Wirecard Payment Processing Gateway';

        $this->assertEquals($expected, $actual);
    }

    public function testConfig()
    {
        $this->payment->createConfig('baseUrl', 'httpUser', 'httpPass');
        $actual = $this->payment->getConfig();

        $expected = $this->config;

        $this->assertEquals($expected, $actual);
    }

    public function testTransactionTypes()
    {
        $actual = $this->payment->getTransactionTypes();

        $expected =  array('authorization','capture');

        $this->assertEquals($expected, $actual);
    }

    public function testFormFields()
    {
        $actual = $this->payment->getFormFields();

        $expected = null;

        $this->assertEquals($expected, $actual);
    }

    public function testType()
    {
        $actual = $this->paypalPayment->getType();

        $expected = 'paypal';

        $this->assertEquals($expected, $actual);
    }

    public function testCreateTransactionIsNull()
    {
        $actual = $this->payment->createTransaction(new PaymentModule(), new Cart(), array(), 'ADB123');

        $this->assertNull($actual);
    }

    public function testCanCancel()
    {
        $this->assertEquals(false, $this->payment->canCancel('test'));
    }

    public function testCanCapture()
    {
        $this->assertEquals(false, $this->payment->canCapture('test'));
    }

    public function testCanRefund()
    {
        $this->assertEquals(true, $this->payment->canRefund('capture-authorization'));
    }
}
