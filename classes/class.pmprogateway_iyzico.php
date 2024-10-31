<?php
	//load classes init method
	add_action('init', array('PMProGateway_iyzico', 'init')); 

	class PMProGateway_iyzico extends PMProGateway
	{
		function __construct($gateway = NULL)
		{
			$this->gateway = $gateway;
			return $this->gateway;
		}										

		/**
		 * Run on WP init
		 *
		 * @since 1.8
		 */
		static function init()
		{
			//make sure iyzico is a gateway option
			add_filter('pmpro_gateways', array('PMProGateway_iyzico', 'pmpro_gateways'));

			//add fields to payment settings
			add_filter('pmpro_payment_options', array('PMProGateway_iyzico', 'pmpro_payment_options'));
			add_filter('pmpro_payment_option_fields', array('PMProGateway_iyzico', 'pmpro_payment_option_fields'), 10, 2);

			//updates cron
			add_action('pmpro_activation', array('PMProGateway_iyzico', 'pmpro_activation'));
			add_action('pmpro_deactivation', array('PMProGateway_iyzico', 'pmpro_deactivation'));
			
			add_action('wp_ajax_nopriv_iyzicoResponce', array('PMProGateway_iyzico', 'pmpro_wp_ajax_iyzicoResponce'));
			add_action('wp_ajax_iyzicoResponce', array('PMProGateway_iyzico', 'pmpro_wp_ajax_iyzicoResponce')); 
			
			//code to add at checkout if iyzico is the current gateway
			$gateway = pmpro_getOption("gateway");
			if($gateway == "iyzico")
			{
				add_filter('pmpro_include_payment_information_fields', '__return_false');
				add_filter('pmpro_required_billing_fields', array('PMProGateway_iyzico', 'pmpro_required_billing_fields'));
				add_filter('pmpro_checkout_default_submit_button', array('PMProGateway_iyzico', 'pmpro_checkout_default_submit_button'));
				add_filter('pmpro_checkout_before_change_membership_level', array('PMProGateway_iyzico', 'pmpro_checkout_before_change_membership_level'), 10, 2); 
                                 
			}
		}
                
		 /**
		 * Make sure iyzico is in the gateways list
		 *
		 * @since 1.8
		 */
		static function pmpro_gateways($gateways)
		{
			if(empty($gateways['iyzico']))
				$gateways['iyzico'] = __('Iyzico Payment gateway', 'paid-memberships-pro'); 
				
			return $gateways;
		}

		/**
		 * Get a list of payment options that the iyzico gateway needs/supports.
		 *
		 * @since 1.8
		 */ 
		static function getGatewayOptions()
		{
			$options = array(
				'sslseal',
				'nuclear_HTTPS',
				'gateway_environment',
				'apikey',
				'apisecretkey',
				'iyzicocheckoutform_form_class',
				'currency',
				'use_ssl',
				'tax_state',
				'tax_rate'
			);

			return $options;
		}

		/**
		 * Set payment options for payment settings page.
		 *
		 * @since 1.8
		 */
		static function pmpro_payment_options($options)
		{
			//get iyzico options
			$iyzico_options = PMProGateway_iyzico::getGatewayOptions();
			
			//merge with others.
			$options = array_merge($iyzico_options, $options);
			

			return $options;
		}

		/**
		 * Display fields for iyzico options.
		 *
		 * @since 1.8
		 */
		static function pmpro_payment_option_fields($values, $gateway)
		{
		?>
		<tr class="pmpro_settings_divider gateway gateway_iyzico" <?php if($gateway != "iyzico") { ?>style="display: none;"<?php } ?>>
			<td colspan="2">
				<?php _e('Iyzico Settings', 'paid-memberships-pro' ); ?>
			</td>
		</tr>
		<tr class="gateway gateway_iyzico" <?php if($gateway != "iyzico") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="apikey"><?php _e('API Key', 'paid-memberships-pro' );?>:</label>
			</th>
			<td>
				<input type="text" id="apikey" name="apikey" size="60" value="<?php echo esc_attr($values['apikey'])?>" />
			</td>
		</tr>
		<tr class="gateway gateway_iyzico" <?php if($gateway != "iyzico") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="apisecretkey"><?php _e('API Secret Key', 'paid-memberships-pro' );?>:</label>
			</th>
			<td>
				<input type="text" id="apisecretkey" name="apisecretkey" size="60" value="<?php echo esc_attr($values['apisecretkey'])?>" />
			</td>
		</tr>
		<tr class="gateway gateway_iyzico" <?php if($gateway != "iyzico") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="iyzicocheckoutform_form_class"><?php _e('Form Class', 'paid-memberships-pro' );?>:</label>
			</th>
			<td>
				<select class="select " name="iyzicocheckoutform_form_class" id="iyzicocheckoutform_form_class" style="">
					<option value="popup"<?php if($values['iyzicocheckoutform_form_class'] == 'popup') { ?>selected="selected"<?php } ?>>Popup</option>
					<option value="responsive"<?php if($values['iyzicocheckoutform_form_class'] == 'responsive') { ?>selected="selected"<?php } ?>>Responsive</option>
				</select>
			</td>
		</tr>
		<?php
		}
		
		/**
		 * Remove required billing fields
		 *		 
		 * @since 1.8
		 */
		static function pmpro_required_billing_fields($fields)
		{
			unset($fields['bfirstname']);
			unset($fields['blastname']);
			unset($fields['baddress1']);
			unset($fields['bcity']);
			unset($fields['bstate']);
			unset($fields['bzipcode']);
			unset($fields['bphone']);
			//unset($fields['bemail']);
			unset($fields['bcountry']);
			unset($fields['CardType']);
			unset($fields['AccountNumber']);
			unset($fields['ExpirationMonth']);
			unset($fields['ExpirationYear']);
			unset($fields['CVV']);
			return $fields;
		}
		
		/**
		 * Swap in our submit buttons.
		 *
		 * @since 1.8
		 */
		static function pmpro_checkout_default_submit_button($show)
		{
			global $gateway, $pmpro_requirebilling;
			//show our submit buttons
			?>			
			<span id="pmpro_submit_span">
				<input type="hidden" name="submit-checkout" value="1" />		
				<input type="submit" class="pmpro_btn pmpro_btn-submit-checkout" value="<?php if($pmpro_requirebilling) { _e('Iyzico aracılığıyla Öde', 'paid-memberships-pro' ); } else { _e('Submit and Confirm', 'paid-memberships-pro' );}?> &raquo;" />	
                               
			</span>
		<?php
			//don't show the default
			return false;
		}
                
                
		
		/**
		 * Instead of change membership levels, send users to Iyzico to pay.
		 *
		 * @since 1.8
		 */
		static function pmpro_checkout_before_change_membership_level($user_id, $morder)
		{
			global $wpdb, $discount_code_id;
			
			//if no order, no need to pay
			if(empty($morder))
				return;
			
			$morder->user_id = $user_id;
			$morder->payment_type = "Iyzico";
			$morder->saveOrder();
			
			//save discount code use
			if(!empty($discount_code_id))
				$wpdb->query("INSERT INTO $wpdb->pmpro_discount_codes_uses (code_id, user_id, order_id, timestamp) VALUES('" . $discount_code_id . "', '" . $user_id . "', '" . $morder->id . "', now())");	
			
			do_action("pmpro_before_send_to_iyzico", $user_id, $morder);
			
			
			$morder->Gateway->sendtoiyzico($morder);
		}

		/**
		 * Cron activation for subscription updates.
		 *
		 * @since 1.8
		 */
		static function pmpro_activation()
		{
			wp_schedule_event(time(), 'daily', 'pmpro_cron_iyzico_subscription_updates');
		}

		/**
		 * Cron deactivation for subscription updates.
		 *
		 * @since 1.8
		 */
		static function pmpro_deactivation()
		{
			wp_clear_scheduled_hook('pmpro_cron_iyzico_subscription_updates');
		}

		function process(&$order)
		{						
			if(empty($order->code))
				$order->code = $order->getRandomCode();			
			
			//clean up a couple values
			$order->payment_type = "Iyzico Payment";
			$order->CardType = "";
			
			
			//just save, the user will go to 2checkout to pay
			$order->status = "review";														
			$order->saveOrder();
			return true;			
		}
		
		
		function sendtoiyzico(&$order)
		{
			global $pmpro_currency;		
			if(empty($order->code))
					$order->code = $order->getRandomCode();
			
			
			
				
			require_once 'IyzipayBootstrap.php';

			IyzipayBootstrap::init();
			
			if($pmpro_currency == 'TRY')
			{
			  $currency = \Iyzipay\Model\Currency::TL;
			} elseif($pmpro_currency == 'USD')
			{
			  $currency = \Iyzipay\Model\Currency::USD;
			} elseif($pmpro_currency == 'GBP')
			{
			  $currency = \Iyzipay\Model\Currency::GBP;
			} elseif($pmpro_currency == 'EUR')
			{
			  $currency = \Iyzipay\Model\Currency::EUR;
			} elseif($pmpro_currency == 'IRR')
			{
			  $currency = \Iyzipay\Model\Currency::IRR;
			} 
			
			$api_id = get_option('pmpro_apikey');
			$secret_key = get_option('pmpro_apisecretkey');
			$gateway_environment = get_option('pmpro_gateway_environment');
			$form_class = get_option('pmpro_iyzicocheckoutform_form_class');
			if($gateway_environment == 'live')
			{
				$apiUrl = 'https://api.iyzipay.com';
			} else if($gateway_environment == 'sandbox')
			{
				$apiUrl = 'https://sandbox-api.iyzipay.com';
			}   
			
			$amount = $order->InitialPayment;
			$amount = round((float)$amount);
			$CallBackUrl = admin_url("admin-ajax.php") . "?action=iyzicoResponce";
			$options = new \Iyzipay\Options();
			$options->setApiKey($api_id);
			$options->setSecretKey($secret_key);
			$options->setBaseUrl($apiUrl);
			
			$siteLang = explode('_', get_locale());
			$locale = ($siteLang[0] == "tr") ? Iyzipay\Model\Locale::TR : Iyzipay\Model\Locale::EN;
			
			$request = new \Iyzipay\Request\CreateCheckoutFormInitializeRequest();
			$request->setLocale($locale);
			$request->setConversationId($order->code);
			$request->setPrice("1");
			$request->setPaidPrice($amount);
			$request->setCurrency($currency); 
			$request->setBasketId($order->code);
			$request->setPaymentGroup(\Iyzipay\Model\PaymentGroup::SUBSCRIPTION);
			$request->setCallbackUrl($CallBackUrl);
			$request->setEnabledInstallments(array(2, 3, 6, 9));

			$buyer = new \Iyzipay\Model\Buyer();
			$buyer->setId($order->code);
			$buyer->setName($order->FirstName);
			$buyer->setSurname($order->LastName);
			$buyer->setGsmNumber($order->billing->phone);
			$buyer->setEmail($order->Email);
			$customer_identity_number = str_pad(uniqid(), 11, '0', STR_PAD_LEFT);
			$buyer->setIdentityNumber($customer_identity_number);
			$buyer->setLastLoginDate($order->datetime);
			$buyer->setRegistrationDate($order->datetime);
			$buyer->setRegistrationAddress($order->billing->street);
			$buyer->setIp($_SERVER['REMOTE_ADDR']);
			$buyer->setCity($order->billing->city);
			$buyer->setCountry($order->billing->country);
			$buyer->setZipCode($order->billing->zip);

			$request->setBuyer($buyer);
			$shippingAddress = new \Iyzipay\Model\Address();
			$shippingAddress->setContactName($order->FirstName." ".$order->LastName);
			$shippingAddress->setCity($order->billing->city);
			$shippingAddress->setCountry($order->billing->country);
			$shippingAddress->setAddress($order->billing->street);
			$shippingAddress->setZipCode($order->billing->zip);
			$request->setShippingAddress($shippingAddress);

			$billingAddress = new \Iyzipay\Model\Address();
			$billingAddress->setContactName($order->FirstName." ".$order->LastName);
			$billingAddress->setCity($order->billing->city);
			$billingAddress->setCountry($order->billing->country);
			$billingAddress->setAddress($order->billing->street);
			$billingAddress->setZipCode($order->billing->zip);
			$request->setBillingAddress($billingAddress);

			$basketItems = array();
			$firstBasketItem = new \Iyzipay\Model\BasketItem();
			$firstBasketItem->setId($order->membership_id);
			$firstBasketItem->setName($order->membership_name);
			$firstBasketItem->setCategory1("Collectibles");
			$firstBasketItem->setCategory2("Accessories");
			$firstBasketItem->setItemType(\Iyzipay\Model\BasketItemType::PHYSICAL);
			$firstBasketItem->setPrice("0.3");
			$basketItems[0] = $firstBasketItem;

			$secondBasketItem = new \Iyzipay\Model\BasketItem();
			$secondBasketItem->setId($order->membership_id);
			$secondBasketItem->setName($order->membership_name);
			$secondBasketItem->setCategory1("Game");
			$secondBasketItem->setCategory2("Online Game Items");
			$secondBasketItem->setItemType(\Iyzipay\Model\BasketItemType::VIRTUAL);
			$secondBasketItem->setPrice("0.5");
			$basketItems[1] = $secondBasketItem;
			
			$thirdBasketItem = new \Iyzipay\Model\BasketItem();
			$thirdBasketItem->setId($order->membership_id);
			$thirdBasketItem->setName($order->membership_name);
			$thirdBasketItem->setCategory1("Electronics");
			$thirdBasketItem->setCategory2("Usb / Cable");
			$thirdBasketItem->setItemType(\Iyzipay\Model\BasketItemType::PHYSICAL);
			$thirdBasketItem->setPrice("0.2");
			$basketItems[2] = $thirdBasketItem;
			$request->setBasketItems($basketItems);
			$checkoutFormInitialize = \Iyzipay\Model\CheckoutFormInitialize::create($request, $options);
			$response = $checkoutFormInitialize->getCheckoutFormContent();
			
		   if (is_object($checkoutFormInitialize) && 'success' == $checkoutFormInitialize->getStatus()) {
				echo '<div id="iyzipay-checkout-form" class="'.$form_class.'">' . $response . '</div>';
				exit;
			} else if (is_object($checkoutFormInitialize) && $checkoutFormInitialize->getStatus() == 'failure') {
				echo '<div id="iyzipay-checkout-form" class="'.$form_class.'">' . $checkoutFormInitialize->getErrorMessage() . '</div>'; 
				exit;
			}
			exit;
			
		
		}
		
        function pmpro_wp_ajax_iyzicoResponce()
		{
			
			$siteLanguage = get_locale();
			
			require_once 'IyzipayBootstrap.php';

			IyzipayBootstrap::init();
			
			$token = $_POST['token'];
			$api_id = get_option('pmpro_apikey');
			$secret_key = get_option('pmpro_apisecretkey');
			$gateway_environment = get_option('pmpro_gateway_environment');
			if($gateway_environment == 'live')
			{
				$apiUrl = 'https://api.iyzipay.com';
			} else if($gateway_environment == 'sandbox')
			{
				$apiUrl = 'https://sandbox-api.iyzipay.com';
			} 
			$options = new \Iyzipay\Options();
			$options->setApiKey($api_id);
			$options->setSecretKey($secret_key);
			$options->setBaseUrl($apiUrl);
			 
			$siteLang = explode('_', get_locale());
			$locale = ($siteLang[0] == "tr") ? Iyzipay\Model\Locale::TR : Iyzipay\Model\Locale::EN;
			
			$request = new \Iyzipay\Request\RetrieveCheckoutFormRequest();
			$request->setLocale($locale);
			$request->setToken($token);

			$response = \Iyzipay\Model\CheckoutForm::retrieve($request, $options);
			$json = json_decode($response->getRawResult());
			$payment_id = $json->paymentId;
			$paymentStatus = $json->paymentStatus;
			$code = $response->getBasketId();
			
			global $wpdb;
			 
			$order = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_membership_orders WHERE code = '" . $code . "' LIMIT 1"); 
			 
			 if($paymentStatus == 'SUCCESS')
			 {
				$morder = new MemberOrder( $order->id );
				$morder->getMembershipLevel();
				$morder->getUser();
				$morder->status = $paymentStatus;
				$morder->payment_transaction_id = $payment_id;
				$morder->saveOrder();
			
				//filter for level
				$morder->membership_level = apply_filters("pmpro_inshandler_level", $morder->membership_level, $morder->user_id);

				//set the start date to current_time('mysql') but allow filters (documented in preheaders/checkout.php)
				$startdate = apply_filters("pmpro_checkout_start_date", "'" . current_time('mysql') . "'", $morder->user_id, $morder->membership_level);

				//fix expiration date
				if(!empty($morder->membership_level->expiration_number))
				{
					$enddate = "'" . date_i18n("Y-m-d", strtotime("+ " . $morder->membership_level->expiration_number . " " . $morder->membership_level->expiration_period, current_time("timestamp"))) . "'";
				}
				else
				{
					$enddate = "NULL";
				}

				//filter the enddate (documented in preheaders/checkout.php)
				$enddate = apply_filters("pmpro_checkout_end_date", $enddate, $morder->user_id, $morder->membership_level, $startdate);

				//get discount code
				$morder->getDiscountCode();
				if(!empty($morder->discount_code))
				{
					//update membership level
					$morder->getMembershipLevel(true);
					$discount_code_id = $morder->discount_code->id;
				}
				else
					$discount_code_id = "";

				

				//custom level to change user to
				$custom_level = array(
					'user_id' => $morder->user_id,
					'membership_id' => $morder->membership_level->id,
					'code_id' => $discount_code_id,
					'initial_payment' => $morder->membership_level->initial_payment,
					'billing_amount' => $morder->membership_level->billing_amount,
					'cycle_number' => $morder->membership_level->cycle_number,
					'cycle_period' => $morder->membership_level->cycle_period,
					'billing_limit' => $morder->membership_level->billing_limit,
					'trial_amount' => $morder->membership_level->trial_amount,
					'trial_limit' => $morder->membership_level->trial_limit,
					'startdate' => $startdate,
					'enddate' => $enddate
					);

				

				if( pmpro_changeMembershipLevel($custom_level, $morder->user_id) !== false ) {
					//update order status and transaction ids
					$morder->status = "success";
					$morder->payment_transaction_id = $txn_id;
					
					$morder->saveOrder();

					//add discount code use
					if(!empty($discount_code) && !empty($use_discount_code))
					{
						$wpdb->query("INSERT INTO $wpdb->pmpro_discount_codes_uses (code_id, user_id, order_id, timestamp) VALUES('" . $discount_code_id . "', '" . $morder->user_id . "', '" . $morder->id . "', '" . current_time('mysql') . "')");
					}

					//save first and last name fields
					if(!empty($_POST['first_name']))
					{
						$old_firstname = get_user_meta($morder->user_id, "first_name", true);
						if(!empty($old_firstname))
							update_user_meta($morder->user_id, "first_name", $_POST['first_name']);
					}
					if(!empty($_POST['last_name']))
					{
						$old_lastname = get_user_meta($morder->user_id, "last_name", true);
						if(!empty($old_lastname))
							update_user_meta($morder->user_id, "last_name", $_POST['last_name']);
					}

					//hook
					do_action("pmpro_after_checkout", $morder->user_id);

					//setup some values for the emails
					if(!empty($morder))
						$invoice = new MemberOrder($morder->id);
					else
						$invoice = NULL;

					$user = get_userdata($morder->user_id);
					if(empty($user))
						return false;

					$user->membership_level = $morder->membership_level;		//make sure they have the right level info

					//send email to member
					$pmproemail = new PMProEmail();
					$pmproemail->sendCheckoutEmail($user, $invoice);

					//send email to admin
					$pmproemail = new PMProEmail();
					$pmproemail->sendCheckoutAdminEmail($user, $invoice);
			
					}
			 }
				
			$redirect = pmpro_url("confirmation", "?level=" . $morder->membership_level->id);
			 if(!empty($redirect))
					wp_redirect($redirect);
				
				exit;
	}
}	