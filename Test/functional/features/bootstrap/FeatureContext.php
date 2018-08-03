<?php

/*
 * This file is part of the Behat.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Bex\Behat\Magento2InitExtension\Fixtures\BaseMinkFixture;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Bex\Behat\Magento2InitExtension\Fixtures\MagentoConfigManager;

/**
 * Behat test suite context.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class FeatureContext
    extends MercadoPagoFixture
    implements Context, SnippetAcceptingContext
{

    protected $_configManager;

    protected function getConfigManager()
    {
        if (empty($this->_configManager)) {
            $this->_configManager = new MagentoConfigManager();
        }

        return $this->_configManager;
    }

    /**
     * @param $cssClass
     *
     * @return \Behat\Mink\Element\NodeElement|mixed|null
     * @throws ElementNotFoundException
     */
    public function findElement($cssClass)
    {
        $page = $this->getSession()->getPage();
        $element = $page->find('css', $cssClass);
        if (null === $element) {
            throw new ElementNotFoundException($this->getSession()->getDriver(), 'Element', 'css', $cssClass);
        }

        return $element;
    }

    /**
     * @Then i revert configs
     */
    public function iRevertConfigs()
    {
        $this->getConfigManager()->revertAllConfig();
    }

    /*
     *  Search for particular string in text
     *
     */
    protected function _stringMatch($content, $string)
    {
        $actual = preg_replace('/\s+/u', ' ', $content);
        $regex = '/' . preg_quote($string, '/') . '/ui';

        return ((bool)preg_match($regex, $actual));
    }


    /*********************************************************FEATURE FUNCTIONS**************************************/

    /**
     * @Given User :arg1 :arg2 exists
     */
    public function userExists($arg1, $arg2)
    {
        $customer = $this->getMagentoObject('Magento\Customer\Model\Customer');
        $storeManager = $this->getMagentoObject(Magento\Store\Model\StoreManagerInterface::class);
        $store = $storeManager->getWebsite()->getDefaultStore();
        $websiteId = $store->getWebsiteId();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail($arg1);

        if (!$customer->getId()) {
            $customer->setWebsiteId($websiteId)
                ->setStore($store)
                ->setFirstname('John')
                ->setLastname('Doe')
                ->setEmail($arg1)
                ->setPassword($arg2);

            $customer->save();
        }

    }

    /**
     * @Given I am logged in as :arg1 :arg2
     */
    public function iAmLoggedInAs($arg1, $arg2)
    {
        $session = $this->getSession();
        $session->visit($this->locatePath('customer/account/login'));

        $login = $session->getPage()->find('css', '#email');
        $pwd = $session->getPage()->find('css', '#pass');
        $submit = $session->getPage()->find('css', '#send2');
        if ($login && $pwd && $submit) {
            $email = $arg1;
            $password = $arg2;
            $login->setValue($email);
            $pwd->setValue($password);
            $submit->click();
            $this->findElement('div .welcome');
        }
    }

    /**
     * @Given I empty cart
     */
    public function iEmptyCart()
    {
        $this->iAmOnPage('checkout/cart/');
        $removeButton = $this->getSession()->getPage()->find('css', '.action-delete');
        if ($removeButton) {
            $removeButton->press();
            $this->iEmptyCart();
        }
    }

    /**
     * @When I am on page :arg1
     */
    public function iAmOnPage($arg1)
    {
        $this->getSession()->visit($this->locatePath($arg1));
    }

    /**
     * @Given I press :cssClass element
     */
    public function iPressElement($cssClass)
    {
        $this->getSession()->wait(10000);
        $button = $this->findElement($cssClass);
        $button->press();
    }

    /**
     * @When I select shipping method :arg1
     */
    public function iSelectShippingMethod($method)
    {
        $page = $this->getSession()->getPage();
        $page->fillField('s_method_flatrate_flatrate', $method);
    }

    /**
     * @When I select payment method :arg1
     */
    public function iSelectPaymentMethod($method)
    {
        $page = $this->getSession()->getPage();
        $page->fillField('payment[method]', $method);
    }

    /**
     * @Then I should see MercadoPago Standard available
     */
    public function iShouldSeeMercadopagoStandardAvailable()
    {
        $this->getSession()->wait(10000);
        $this->findElement('#mercadopago_standard');
    }

    /**
     * @When I fill the shipping address
     */
    public function iFillTheShippingAddress()
    {
        try {
            $this->findElement('.selected-item');
        } catch (ElementNotFoundException $e) {
            try {
                $button = $this->findElement('.action-select-shipping-item');
                $button->press();
            } catch (ElementNotFoundException $e) {
                $page = $this->getSession()->getPage();
                $page->fillField('street[0]', 'Street 123');
                $page->fillField('city', 'City');
                $page->selectFieldOption('country_id', 'AR');
                $page->fillField('postcode', '7000');
                $page->fillField('telephone', '123456');
            }

        }
    }

    /**
     * @Given Setting Config :arg1 is :arg2
     */
    public function settingConfig($arg1, $arg2)
    {
        $this->getConfigManager()->changeConfigs([['path' => $arg1, 'value' => $arg2, 'scope_type' => 'default', 'scope_code' => null]]);
    }


    /**
     * @Then I should not see MercadoPago Standard available
     *
     */
    public function iShouldNotSeeMercadopagoStandardAvailable()
    {
        $this->getSession()->wait(10000);
        if ($this->getSession()->getPage()->find('css', '#mercadopago_standard')) {
            throw new ExpectationException('I saw payment method available', $this->getSession()->getDriver());
        }

        return;
    }

    /**
     * @Given I configure mercadopago standard
     */
    public function iConfigureMercadopagoStandard()
    {
        $configs = [
            ['path' => 'payment/mercadopago/country', 'value' => 'mla', 'scope_type' => 'default', 'scope_code' => null],
            ['path' => 'payment/mercadopago_standard/active', 'value' => '1', 'scope_type' => 'default', 'scope_code' => null],
            ['path' => 'payment/mercadopago_standard/client_id', 'value' => '446950613712741', 'scope_type' => 'default', 'scope_code' => null],
            ['path' => 'payment/mercadopago_standard/client_secret', 'value' => '0WX05P8jtYqCtiQs6TH1d9SyOJ04nhEv', 'scope_type' => 'default', 'scope_code' => null]
        ];
        $this->getConfigManager()->changeConfigs($configs);

    }

    /**
     * @Given /^I switch to the iframe "([^"]*)"$/
     */
    public function iSwitchToIframe($arg1 = null)
    {
        $this->getSession()->wait(10000);
        $this->findElement('iframe[id=' . $arg1 . ']');
        $this->getSession()->switchToIFrame($arg1);
        $this->getSession()->wait(5000);
    }


    /**
     * @When I fill the iframe fields
     */
    public function iFillTheIframeFields()
    {
        $page = $this->getSession()->getPage();

        $page->selectFieldOption('pmtOption', 'visa');

        $page->fillField('cardNumber', '4509 9535 6623 3704');
        $this->getSession()->wait(3000);
        $page->selectFieldOption('creditCardIssuerOption', '1');
        $page->selectFieldOption('cardExpirationMonth', '01');
        $page->selectFieldOption('cardExpirationYear', '2017');
        $page->fillField('securityCode', '123');
        $page->fillField('cardholderName', 'Name');
        $page->selectFieldOption('docType', 'DNI');

        $page->fillField('docNumber', '12345678');

        $page->selectFieldOption('installments', '1');
    }

    /**
     * @Given I press :cssClass input element
     */
    public function iPressInputElement($cssClass)
    {
        $button = $this->findElement($cssClass);
        $button->click();
    }

    /**
     * @Given I switch to the site
     */
    public function iSwitchToSite()
    {
        $this->getSession()->wait(15000);
        $this->getSession()->switchToIFrame(null);
    }

    /**
     * @Then I should be on :arg1
     */
    public function iShouldBeOn($arg1)
    {
        $session = $this->getSession();
        $session->wait(10000);
        $currentUrl = $session->getCurrentUrl();

        if (strpos($currentUrl, $arg1)) {
            return;
        }
        throw new ExpectationException('Wrong url: you are in ' . $currentUrl, $this->getSession()->getDriver());
    }

    /**
     * @Then I should see html :arg1
     */
    public function iShouldSeeHtml($arg1)
    {
        $actual = $this->getSession()->getPage()->getHtml();
        if (!$this->_stringMatch($actual, $arg1)) {
            throw new ExpectationException('Element' . $arg1 . ' not found', $this->getSession()->getDriver());
        }
    }

    /**
     * @When I wait for :arg1 seconds
     */
    public function iWaitForSeconds($secs)
    {
        $milliseconds = $secs * 1000;
        $this->getSession()->wait($milliseconds);
    }

    /**
     * @When I test
     */
    public function iTest()
    {
        $page = $this->getSession()->getPage();
        var_dump($page->getHtml());
    }

    /**
     * @When I am logged in MP as :arg1 :arg2
     */
    public function iAmLoggedInMPAs($arg1, $arg2)
    {
        $session = $this->getSession();
        $logged = $session->getPage()->find('css', '#payerAccount');
        if ($logged) {
            $exit = $session->getPage()->find('css', '#payerAccount a');
            $exit->press();
            $this->iWaitForSeconds(5);
        }

        $login = $session->getPage()->find('css', '#user_id');
        $pwd = $session->getPage()->find('css', '#password');
        $submit = $session->getPage()->find('css', '#init');
        if ($login && $pwd && $submit) {
            $email = $arg1;
            $password = $arg2;
            $login->setValue($email);
            $pwd->setValue($password);
            $submit->click();
            $this->iWaitForSeconds(7);
            $logged = $session->getPage()->find('css', '#payerAccount');
            if ($logged) {
                return;
            } else {
                $actual = $this->getSession()->getPage()->getHtml();
                if ($this->_stringMatch($actual, "captcha")) {
                    throw new ExpectationException('This form has a captcha', $this->getSession()->getDriver());
                }
            }
        }
    }

    /**
     * @Given Setting merchant :arg1
     */
    public function settingMerchant($arg1)
    {
        $dataCountry = [
            'mla' => [
                'client_id'     => '446950613712741',
                'client_secret' => '0WX05P8jtYqCtiQs6TH1d9SyOJ04nhEv',
                'public_key'    => 'TEST-d5a3d71b-6bd4-4bfc-a1f3-7ed77987d5aa',
                'access_token'  => 'TEST-446950613712741-091715-092a6109a25bb763aa94c61688ada0cd__LC_LA__-192627424'
            ],
            'mlb' => [
                'client_id'     => '1872374615846510',
                'client_secret' => 'WGfDqM8bNLzjvmrEz8coLCUwL8s4h9HZ',
                'public_key'    => 'TEST-09fcb4ab-319c-45d1-8e33-9a92bf11de11',
                'access_token'  => 'TEST-1872374615846510-111016-1db7450c5b662c1be62b45eebef82f32__LA_LC__-193992978'
            ],
            'mlm' => [
                'client_id'     => '2272101328791208',
                'client_secret' => 'cPi6Mlzc7bGkEaubEJjHRipqmojXLtKm',
                'public_key'    => 'TEST-687f89f2-19d1-4a8b-893e-8da5c7e238ac',
                'access_token'  => 'TEST-2272101328791208-111108-fabd0182d1c7c7ba554b1773558a828a__LD_LB__-193996689'
            ],
            'mlv' => [
                'client_id'     => '201313175671817',
                'client_secret' => 'bASLUlb5s12QYPAUJwCQUMa21wFzFrzz',
                'public_key'    => 'TEST-a4f588fd-5bb8-406c-9811-1536154d5d73',
                'access_token'  => 'TEST-201313175671817-111108-b30483a389dbc6a04e401c23e62da2c1__LB_LC__-193994249'
            ],
            'mco' => [
                'client_id'     => '3688958250893559',
                'client_secret' => 'bASLUlb5s12QYPAUJwCQUMa21wFzFrzz',
                'public_key'    => 'TEST-6226129a-143f-4f87-973c-060c2426510a',
                'access_token'  => 'TEST-7635994297462517-030309-51dfe28ab15e9d0f30a7bdd12f019675__LA_LD__-193993045'
            ],
            'mlc' => [
                'client_id'     => '4911204937414957',
                'client_secret' => '81SULY2VoVfjYBufde7s7njAnhT2tNSU',
                'public_key'    => 'TEST-94844496-13a2-4977-8ecd-f5ccdae9e14a',
                'access_token'  => 'TEST-7635994297462517-030309-51dfe28ab15e9d0f30a7bdd12f019675__LA_LD__-193993045'
            ]
        ];
        $clientId = $dataCountry[$arg1]['client_id'];
        $clientSecret = $dataCountry[$arg1]['client_secret'];
        $this->settingConfig('payment/mercadopago/country', $arg1);
        $this->settingConfig('payment/mercadopago/debug_mode', "1");
        $this->settingConfig('payment/mercadopago_standard/client_id', $clientId);
        $this->settingConfig('payment/mercadopago_standard/client_secret', $clientSecret);
        $this->settingConfig('payment/mercadopago/use_successpage_mp', "1");
        if (isset($dataCountry[$arg1]['public_key'])) {
            $publicKey = $dataCountry[$arg1]['public_key'];
            $accessToken = $dataCountry[$arg1]['access_token'];
            $this->settingConfig('payment/mercadopago_custom/active', "1");
            $this->settingConfig('payment/mercadopago_custom/public_key', $publicKey);
            $this->settingConfig('payment/mercadopago_custom/access_token', $accessToken);
        }

    }

    /**
     * @Given I fill text field :arg1 with :arg2
     */
    public function iFillTextFieldWith($arg1, $arg2)
    {
        $page = $this->getSession()->getPage();
        $page->fillField($arg1, $arg2);
    }

    /**
     * @Given I select option field :arg1 with :arg2
     */
    public function iSelectOptionFieldWith($arg1, $arg2)
    {
        //$this->getSession()->wait(20000, '(0 === Ajax.activeRequestCount)');
        $page = $this->getSession()->getPage();

        $page->selectFieldOption($arg1, $arg2);
    }

    /**
     * @Given I select shipping method option field :arg1 with :arg2
     */
    public function iSelectShippingMethodOptionFieldWith($arg1, $arg2)
    {
        $page = $this->getSession()->getPage();
        $field = $page->find('css', $arg1);
        if (null !== $field) {
            $field->selectOption($arg2, false);
        }
    }


    /**
     * @Given I select installment :arg1
     */

    public function iSelectInstallment($installment)
    {
        $page = $this->getSession()->getPage();
        $this->getSession()->wait(20000, "jQuery('#installments').children().length > 1");
        $page->selectFieldOption('installments', $installment);
    }

    /**
     * @When I wait for :secs seconds avoiding alert
     */
    public function iWaitForSecondsAvoidingAlert($secs)
    {
        $milliseconds = $secs * 1000;
        try {
            $this->getSession()->wait($milliseconds, '(0 === Ajax.activeRequestCount)');
        } catch (Exception $e) {
            $this->acceptAlert();
        }
    }
    /**
     * @Then I should see :arg1
     */
    public function iShouldSee($arg1)
    {
        $actual = $this->getSession()->getPage()->getText();
        if (!$this->_stringMatch($actual, $arg1)) {
            throw new ExpectationException('Element' . $arg1 . ' not found', $this->getSession()->getDriver());
        }
    }

    /**
     * @Then I should stay step :arg1
     */
    public function iShouldStayStep($arg1)
    {
        if ($this->findElement($arg1)->hasClass('active')) {
            return;
        }
        throw new ExpectationException('I am not stay in ' . $arg1, $this->getSession()->getDriver());

    }

    /**
     * @When I wait for :secs seconds with :cond
     */
    public function iWaitForSecondsWithCondition($secs, $condition)
    {
        $milliseconds = $secs * 1000;
        $this->getSession()->wait($milliseconds, $condition);
    }

    /**
     * @Given I blur field :arg1
     */
    public function iBlurField($arg1)
    {
        $field = $this->findElement($arg1);
        $this->getSession()->getDriver()->blur($field->getXpath());
    }

    /**
     * @Given Product with sku :arg1 has a price of :arg2
     */
    public function productWithSkuHasAPriceOf($arg1, $arg2)
    {
        $product = $this->getMagentoObject('Magento\Catalog\Model\Product')->loadByAttribute('sku', $arg1);

        $product->setPrice($arg2)->save();
    }

}
