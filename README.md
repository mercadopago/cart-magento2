# Magento (v2.0.x - v2.1) - MercadoPago Module

* [Features](#features)
* [installation by Composer](#composer_installation)
* [Installation](#installation)
* [Configuration](#configuration)
* [MercadoEnvios](#mercadoenvios)
* [Feedback](#feedback)

<a name="features"></a>
## Features:

Checkout options right for your business: 
We offer two checkout methods that make it easy to securely accept payments from anyone, anywhere.

**Custom Checkout**

Offers a fully customized checkout to your brand experience with our simple-to-use payments API.

* Seamless integration— no coding required, unless you want to.
* Full control of buying experience.
* Store buyer’s card for fast checkout.
* Accept tickets in addition to cards.
* Accept MercadoPago's discount coupons.
* Improve conversion rate.
* Accept Payment with two cards <sup>*</sup>
* Debug Mode.

*Available for Argentina, Brazil, Chile, Colombia, Mexico, Peru and Venezuela**

**Classic Checkout**

Great for merchants who want to get going quickly and easily.

* Easy website integration —no coding required-.
* Limited control of buying experience —display Checkout window as redirect, modal or iframe-.
* Store buyer’s card for fast checkout.
* Accept tickets, bank transfer and account money in addition to cards.
* Accept MercadoPago's discount coupons.

*Available for Argentina, Brazil, Chile, Colombia, Mexico, Peru, Uruguay and Venezuela*

**Shipment integration**

This feature allows to setup and integrate with MercadoEnvios shipment method as another shipment option for customers. 
It includes the possibility to print the shipping label directly from the Magento Admin Panel. 

*Available for Argentina, Brazil and Mexico only with Standard Checkout*

**Returns and Cancellations between MercadoPago and Magento**

This feature synchronizes orders between MercadoPago and Magento. 
Returns and cancellations made from Magento are synchronized in MercadoPago and vice versa.
Returns can be enabled/disabled within Magento admin panel.
You can also define the maximum amount of partial refunds on the same order and the maximum amount of days until refund is not accepted by using Magento admin panel.

**Configurable success page**

This feature allows to configure the success page to which Magento redirects the customer once a payment was made with MercadoPago.
Within Magento admin panel, you can select between success page from MercadoPago or standard page from Magento (checkout/success).

**Debug Mode in custom checkout**

This feature enabled allows testing the plugin without a SSL certificate. 
The custom checkout does not appear as a payment method if you operate over HTTP and with the configuration disabled.
It is not recommended enable this option in production environment.

**Installments calculator**

This feature allows to add an installment calculator within Magento pages.
It can be enabled/disabled from the Magento admin panel.
The calculator can be visualized within product, cart, or both pages.
The customer can use the installment calculator to see the financing options available and the final amount to be paid.

**Split payments in custom checkout**<sup>*</sup>

This feature allows customer to split Custom Checkout payments by using two different cards.
This payment modality behaves in the same way as the equivalent payment mode in the classic checkout.
This functionality can also be enabled/disabled from Magento plugin configuration.

<a name="composer_installation"></a>
## Installation using composer:

1. Add repository to your Magento installation composer.json file
	
	- "repositories": [
				{
				"type": "vcs",
				"url": "https://github.com/mercadopago/cart-magento2"
				}
	      		  ]
	
2. Execute composer command to download plugin package

	- composer require mercadopago/magento2-plugin


<a name="installation"></a>
## Installation copying files:

1. Copy the folder **src/MercadoPago** to the Magento root installation. Make sure to keep the Magento folders structure intact.

2. Enable modules from console.

	  - bin/magento module:enable MercadoPago_Core
	  - bin/magento module:enable MercadoPago_MercadoEnvios
    
    Then update magento with new modules:
    
      - bin/magento setup:upgrade 

<a name="configuration"></a>
## Configuration

1. Go to **Stores > Configuration > Sales > Payment Methods**. Select **MercadoPago - Global Configuration**.
![MercadoPago Global Configuration](/README.img/mercadopago_global_configuration.png?raw=true)

2. Set your Country to the same where your account was created on, and save config.
	**Note: If you change the Country where your account was created you need save configuration in order to refresh the excluded payment methods.**
	
3. Other general configurations:

    * **Category of your store**: Sets up the category of the store.
    * **Use MercadoPago success page**: Use success page from MercadoPago or standard page from Magento.
  - **Refund Options**
    * **Refund Available**: Enables/disables Refund.
    * **Maximum amount of partial refunds on the same order**: Set the maximum amount of partial refunds on the same order.
    * **Maximum amount of days until refund is not accepted**: Set the maximum amount of days until refund is not accepted.
    * **Choose the status when payment was partially refunded**: Sets up the order status when payments are partially refunded.
  - **Order Status Options**
    * **Choose the status of approved orders**: Sets up the order status when payments are approved.
    * **Choose the status of refunded orders**: Sets up the order status when payments are refunded.
    * **Choose the status when payment is pending**: Sets up the order status when payments are pending.
    * **Choose the status when client open a mediation**: Sets up the order status when client opens a mediation.
    * **Choose the status when payment was reject**: Sets up the order status when payments are rejected.
    * **Choose the status when payment was canceled**: Sets up the order status when payments are canceled.
    * **Choose the status when payment was chargeback**: Sets up the order status when payments are chargeback.
  - **Developer Options**
    * **Logs**: Enables/disables system logs.
    * **Debug Mode**: If enabled, displays the raw response from the API instead of a friendly message.
  - **Payments Calculator**
    * **Enable MercadoPago Installments Calculator**: If enabled, show the Installments Calculator on the selected pages.
    * **Show Calculator on selected pages**: Select the pages to show the Installments Calculator.

<a name="checkout_custom"></a>
### Custom Checkout Payment Solution: 

1. Go to **Stores > Configuration > Sales > Payment Methods**. Select **MercadoPago - Custom Checkout**.
![MercadoPago Custom Checkout Configuration](/README.img/mercadopago_custom_checkout_configuration.png?raw=true)

2. Set your **Public Key** and **Access Token**.
 	In order to get them check the following links according to the country you are operating in:
	
* Argentina: [https://www.mercadopago.com/mla/account/credentials](https://www.mercadopago.com/mla/account/credentials)
* Brazil: [https://www.mercadopago.com/mlb/account/credentials](https://www.mercadopago.com/mlb/account/credentials)
* Chile: [https://www.mercadopago.com/mlc/account/credentials](https://www.mercadopago.com/mlc/account/credentials)
* Colombia: [https://www.mercadopago.com/mco/account/credentials](https://www.mercadopago.com/mco/account/credentials)
* Mexico: [https://www.mercadopago.com/mlm/account/credentials](https://www.mercadopago.com/mlm/account/credentials)
* Peru: [https://www.mercadopago.com/mpe/account/credentials](https://www.mercadopago.com/mpe/account/credentials)
* Venezuela: [https://www.mercadopago.com/mlv/account/credentials](https://www.mercadopago.com/mlv/account/credentials)

If you want to enable credit card solution, check the configurations under **Checkout Custom - Credit Card**:
![MercadoPago Custom Checkout Credit Card](/README.img/mercadopago_custom_checkout_cc.png?raw=true)

* **Enabled**: Enables/disables this payment solution.
* **Payment Title**: Sets the payment title.
* **Statement Descriptor**: Sets the label as the customer will see the charge for amount in his/her bill.
* **Binary Mode**: When set to true, the payment can only be approved or rejected. Otherwise in_process status is added.
* **Banner Checkout**: Sets the URL for the banner image in the payment method selection in the checkout process.
* **Checkout Position**: The position of the payment solution in the checkout process.
* **Marketing - Coupon MercadoPago**: Enables/disables the coupon form.

If you want to enable ticket solution, check the configurations under **Checkout Custom - Ticket**:

![MercadoPago Custom Checkout Ticket](/README.img/mercadopago_custom_checkout_ticket.png?raw=true)

* **Enabled**: Enables/disables this payment solution.
* **Payment Title**: Sets the payment title.
* **Banner Checkout**: Sets the URL for the banner image in the payment method selection in the checkout process.
* **Checkout Position**: The position of the payment solution in the checkout process.
* **Marketing - Coupon MercadoPago**: Enables/disables the coupon form.

<a name="checkout_standard"></a>
### Classic Checkout Payment Solution: 

1. Go to **Stores > Configuration > Sales > Payment Methods**. Select **MercadoPago - Classic Checkout**.

2. Enable the solution and set your **Client Id** and **Client Secret**.

Get them in the following address:

* Argentina: [https://www.mercadopago.com/mla/account/credentials](https://www.mercadopago.com/mla/account/credentials)
* Brazil: [https://www.mercadopago.com/mlb/account/credentials](https://www.mercadopago.com/mlb/account/credentials)
* Chile: [https://www.mercadopago.com/mlc/account/credentials](https://www.mercadopago.com/mlc/account/credentials)
* Colombia: [https://www.mercadopago.com/mco/account/credentials](https://www.mercadopago.com/mco/account/credentials)
* Mexico: [https://www.mercadopago.com/mlm/account/credentials](https://www.mercadopago.com/mlm/account/credentials)
* Peru: [https://www.mercadopago.com/mpe/account/credentials](https://www.mercadopago.com/mpe/account/credentials)
* Venezuela: [https://www.mercadopago.com/mlv/account/credentials](https://www.mercadopago.com/mlv/account/credentials)
* Uruguay: [https://www.mercadopago.com/mlu/account/credentials](https://www.mercadopago.com/mlu/account/credentials)

3. Check the additional configurations:
	* **Payment Title**: Sets the payment title.
	* **Banner Checkout**: Sets the URL for the banner image in the payment method selection in the checkout process.
	* **Checkout Position**: The position of the payment solution in the checkout process.
	* **Type Checkout**: Sets the type of checkout, the options are:
		*  *Iframe*: Opens a Magento URL with a iframe as the content.
		*  *Redirect*: Redirects to MercadoPago URL.
		*  *Lightbox*: Similar to Iframe option but opens a lightbox instead of an iframe. 
	* **Auto Redirect**: If enable, the web return to your store when the payment is approved.
	* **Exclude Payment Methods**: Select the payment methods that you want to not work with MercadoPago.
	* **Maximum number of accepted installments**: Set the maximum installments allowed for your customers.
	* **Width Checkout Iframe**: Set width -in pixels- Checkout Iframe .
	* **Height Checkout Iframe**: Set height -in pixels- Checkout Iframe.
	* **Sandbox Mode**:  Enables/disables MercadoPago sandbox environment.
	
<a name="mercadoenvios">

## MercadoEnvios 

In order to setup MercadoEnvios follow these instructions:

1. Setup MercadoPago Standard Checkout following [these instructions](#checkout_standard).

2. Go to **Sales > Configuration > Sales > Shipping Methods > MercadoEnvios**.

3. Setup the plugin:


![MercadoEnvios Configuration](/README.img/mercadoenvios.png?raw=true)

* **Enabled**: Enables/disables this MercadoEnvios solution.
* **Title**: Sets up the shipping method label displayed in the shipping section in checkout process.
* **Product attributes mapping**: Maps the system attributes with the dimensions and weight. Also allows to set up the attribute unit.
* **Available shipping methods**: Sets up the shipping options visible in the checkout process.
* **Free Method**: Sets up the method to use as free shipping.
* **Free Shipping with Minimum Order Amount**: Enables/disables the order minimum for free shipping to be available.
* **Minimum Order Amount for Free Shipping**: Define Minimum Order Amount for Free Shipping
* **Show method if not applicable**: If enabled, the shipping method is displayed when it's not available.
* **Displayed Error Message**: Sets up the text to be displayed when the shipping method is not available.
* **Log**: Enables/disables system logs.
* **Debug Mode**: If enabled, displays the raw response from the API instead of a friendly message.
* **Sort order**: Sets up the sort order to be displayed in the shipping step in checkout process.
* **Shipping label download option**: Set the format option for downloading shipping labels.
<a name="Feedback"></a>
## Feedback 

We want to know your opinion, please answer the following form.

* [Portuguese](http://goo.gl/forms/2n5jWHaQbfEtdy0E2)
* [Spanish](http://goo.gl/forms/A9bm8WuqTIZ89MI22)