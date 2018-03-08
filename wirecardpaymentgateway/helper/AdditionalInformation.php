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
 */

namespace WirecardEE\Prestashop\Helper;

use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Address;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\Item;
use Wirecard\PaymentSdk\Transaction\Transaction;
use PrestaShop\PrestaShop\Adapter\Entity\Configuration;
use PrestaShop\PrestaShop\Adapter\Entity\Tools;
use PrestaShop\PrestaShop\Adapter\Country\CountryDataProvider;
use PrestaShop\PrestaShop\Adapter\Customer\CustomerDataProvider;
use PrestaShop\PrestaShop\Adapter\AddressFactory;

/**
 * Class AdditionalInformation
 *
 * @since 1.0.0
 */
class AdditionalInformation
{
    /**
     * Create basket items for transaction
     *
     * @param Cart $cart
     * @param Transaction $transaction
     * @param string $currency
     * @return Basket
     * @since 1.0.0
     */
    public function createBasket($cart, $transaction, $currency)
    {
        $basket = new Basket();
        $basket->setVersion($transaction);

        foreach ($cart->getProducts() as $product) {
            $quantity = $product['cart_quantity'];
            $name = Tools::substr($product['name'], 0, 127);
            $grossAmount = $product['total_wt'] / $quantity;

            //Check for rounding issues
            if (Tools::strlen(Tools::substr(strrchr((string)$grossAmount, '.'), 1)) > 2) {
                $grossAmount = $product['total_wt'];
                $name .= ' x' . $quantity;
                $quantity = 1;
            }

            $netAmount = $product['total'] / $quantity;
            $taxAmount = $grossAmount - $netAmount;
            $taxRate = number_format($taxAmount / $grossAmount * 100, 2);
            $amount = new Amount(number_format($grossAmount, 2, '.', ''), $currency);

            $item = new Item($name, $amount, $quantity);
            $item->setDescription(Tools::substr(strip_tags($product['description_short']), 0, 127));
            $item->setArticleNumber($product['reference']);
            $item->setTaxRate($taxRate);

            $basket->add($item);
        }

        if ($cart->getTotalShippingCost(null, true) > 0) {
            $grossAmount = $cart->getTotalShippingCost(null, true);
            $netAmount = $cart->getTotalShippingCost(null, false);
            $taxRate = ( $grossAmount / $netAmount -1 ) * 100;

            $item = new Item('Shipping', new Amount($grossAmount, $currency), 1);
            $item->setDescription('Shipping');
            $item->setArticleNumber('Shipping');
            $item->setTaxRate($taxRate);

            $basket->add($item);
        }

        return $basket;
    }

    /**
     * Create shop descriptor
     *
     * @param string $id
     * @return string
     * @since 1.0.0
     */
    public function createDescriptor($id)
    {
        return sprintf(
            '%s %s',
            substr(Configuration::get('PS_SHOP_NAME'), 0, 9),
            $id
        );
    }

    /**
     * Create additional information for fps
     *
     * @param Cart $cart
     * @param string $id
     * @param Transaction $transaction
     * @param string $currency
     * @return Transaction
     * @since 1.0.0
     */
    public function createAdditionalInformation($cart, $id, $transaction, $currency)
    {
        $transaction->setDescriptor($this->createDescriptor($id));
        $transaction->setAccountHolder($this->createAccountHolder($cart, 'billing'));
        $transaction->setShipping($this->createAccountHolder($cart, 'shipping'));
        $transaction->setOrderNumber($id);
        $transaction->setBasket($this->createBasket($cart, $transaction, $currency));
        $transaction->setIpAddress($this->getConsumerIpAddress());
        $transaction->setConsumerId($cart->id_customer);

        return $transaction;
    }

    /**
     * Create accountholder for shipping or billing
     *
     * @param Cart $cart
     * @param string $type
     * @return AccountHolder
     * @since 1.0.0
     */
    public function createAccountHolder($cart, $type)
    {
        $customerProvider = new CustomerDataProvider();
        $customer = $customerProvider->getCustomer($cart->id_customer);
        $addressFactory = new AddressFactory();
        $billing = $addressFactory->findOrCreate($cart->id_address_invoice);
        $shipping = $addressFactory->findOrCreate($cart->id_address_delivery);

        $accountHolder = new AccountHolder();
        if ('shipping' == $type) {
            $accountHolder->setAddress($this->createAddressData($shipping, $type));
            $accountHolder->setFirstName($shipping->firstname);
            $accountHolder->setLastName($shipping->lastname);
        } else {
            $accountHolder->setAddress($this->createAddressData($billing, $type));
            $accountHolder->setEmail($customer->email);
            $accountHolder->setFirstName($billing->firstname);
            $accountHolder->setLastName($billing->lastname);
            $accountHolder->setPhone($billing->phone);
        }

        return $accountHolder;
    }

    /**
     * Create addressdata for shipping or billing
     *
     * @param PrestaShop\Address $source
     * @param string $type
     * @return Address
     * @since 1.0.0
     */
    public function createAddressData($source, $type)
    {
        $countryProvider = new CountryDataProvider();
        $country = $countryProvider->getIsoCodeById($source->id_country);
        if ('shipping' == $type) {
            $address = new Address($country, $source->city, $source->address1);
            $address->setPostalCode($source->postcode);
        } else {
            $address = new Address($country, $source->city, $source->address1);
            $address->setPostalCode($source->postcode);
            if (strlen($source->address2)) {
                $address->setStreet2($source->address2);
            }
        }

        return $address;
    }

    /**
     * Create consumer ip address
     *
     * @return string
     * @since 1.0.0
     */
    public function getConsumerIpAddress()
    {
        if (!method_exists('Tools', 'getRemoteAddr')) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) and $_SERVER['HTTP_X_FORWARDED_FOR']) {
                if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',')) {
                    $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

                    return $ips[0];
                } else {
                    return $_SERVER['HTTP_X_FORWARDED_FOR'];
                }
            }

            return $_SERVER['REMOTE_ADDR'];
        } else {
            return Tools::getRemoteAddr();
        }
    }
}
