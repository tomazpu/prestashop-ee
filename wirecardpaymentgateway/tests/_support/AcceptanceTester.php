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

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
 */

use Helper\Acceptance;
use Helper\PhpBrowserAPITest;
use Page\Base;
use Page\Cart as CartPage;
use Page\Checkout as CheckoutPage;
use Page\Product as ProductPage;
use Page\Shop as ShopPage;
use Page\OrderReceived as OrderReceivedPage;
use Page\Verified as VerifiedPage;

class AcceptanceTester extends \Codeception\Actor
{

    use _generated\AcceptanceTesterActions;

    /**
     * @var string
     * @since 1.3.4
     */
    private $currentPage;

    /**
     * @var array
     * @since 2.0.1
     */
    private $mappedPaymentActions = [
        'config' => [
            'reserve' => 'reserve',
            'pay' => 'pay',
        ],
        'tx_table' => [
            'authorization' => 'authorization',
            'purchase' => 'purchase'
        ]
    ];

    /**
     * Method selectPage
     *
     * @param string $name
     * @return Base
     *
     * @since   1.3.4
     */
    private function selectPage($name)
    {
        switch ($name) {
            case 'Checkout':
                $page = new CheckoutPage($this);
                break;
            case 'Product':
                $page = new ProductPage($this);
                break;
            case 'Shop':
                $page = new ShopPage($this);
                break;
            case 'Verified':
                $this->wait(15);
                $page = new VerifiedPage($this);
                break;
            case 'Order Received':
                $this->wait(7);
                $page = new OrderReceivedPage($this);
                break;
            default:
                $page = null;
        }
        return $page;
    }

    /**
     * Method getPageElement
     *
     * @param string $elementName
     * @return string
     *
     * @since   1.3.4
     */
    private function getPageElement($elementName)
    {
        //Takes the required element by it's name from required page
        return $this->currentPage->getElement($elementName);
    }

    /**
     * @Given I am on :page page
     * @since 1.3.4
     */
    public function iAmOnPage($page)
    {
        // Open the page and initialize required pageObject
        $this->currentPage = $this->selectPage($page);
        $this->amOnPage($this->currentPage->getURL());
    }

    /**
     * @When I click :object
     * @since 1.3.4
     */
    public function iClick($object)
    {
        $this->waitForElementVisible($this->getPageElement($object));
        $this->waitForElementClickable($this->getPageElement($object));
        $this->click($this->getPageElement($object));
    }

    /**
     * @When I am redirected to :page page
     * @since 1.3.4
     */
    public function iAmRedirectedToPage($page)
    {
        // Initialize required pageObject WITHOUT checking URL
        $this->currentPage = $this->selectPage($page);
        // Check only specific keyword that page URL should contain
        $this->seeInCurrentUrl($this->currentPage->getURL());
    }

    /**
     * @When I fill fields with :data
     * @since 1.3.4
     */
    public function iFillFieldsWith($data)
    {
        $this->fillFieldsWithData($data, $this->currentPage);
    }

    /**
     * @When I enter :fieldValue in field :fieldID
     * @since 1.3.4
     */
    public function iEnterInField($fieldValue, $fieldID)
    {
        $this->waitForElementVisible($this->getPageElement($fieldID));
        $this->fillField($this->getPageElement($fieldID), $fieldValue);
    }

    /**
     * @Then I see :text
     * @since 1.3.4
     */
    public function iSee($text)
    {
        $this->see($text);
    }

    /**
     * @Given I prepare checkout
     * @since 1.3.4
     */
    public function iPrepareCheckout()
    {
        $this->iAmOnPage('Product');
        //enter 5 in field quantity
        $this->fillField($this->currentPage->getElement('Quantity'), '5');
        $this->click($this->currentPage->getElement('Add to cart'));
        $this->waitForText('Product successfully added to your shopping cart');
    }

    /**
     * @When I check :box
     * @since 1.3.4
     */
    public function iCheck($box)
    {
        $this->currentPage->checkBox($box);
    }

    /**
     * @Given I activate payment action :paymentAction in configuration
     * @param string $paymentAction
     * @since 2.0.1
     */
    public function iActivatePaymentActionInConfiguration($paymentAction)
    {
        $this->updateInDatabase(
            'ps_configuration',
            ['value' => $this->mappedPaymentActions['config'][$paymentAction]],
            ['name' => 'WIRECARD_PAYMENT_GATEWAY_CREDITCARD_PAYMENT_ACTION']
        );
    }
    /**
     * @Then I see :paymentAction in transaction table
     * @param string $paymentAction
     * @since 2.0.1
     */
    public function iSeeInTransactionTable($paymentAction)
    {
        $this->seeInDatabase(
            'ps_wirecard_payment_gateway_tx',
            ['transaction_type' => $this->mappedPaymentActions['tx_table'][$paymentAction]]
        );
        //check that last transaction in the table is the one under test
        $transactionTypes = $this->getColumnFromDatabaseNoCriteria('ps_wirecard_payment_gateway_tx', 'transaction_type');
        $this->assertEquals(end($transactionTypes), $this->mappedPaymentActions['tx_table'][$paymentAction]);
    }
}
