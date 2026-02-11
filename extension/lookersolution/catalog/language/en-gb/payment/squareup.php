<?php
/**
 * @package    LookerSolution\SquarePayment
 * @author     Ali Ahmed <ali@lookersolution.com>
 * @copyright  Copyright (c) 2026 LookerSolution (https://www.lookersolution.com)
 * @license    https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link       https://github.com/LookerSolution/oc4-squareup-payment
 */

// Text
$_['text_title']                        = 'Credit / Debit Card (Square)';
$_['text_loading']                      = 'Loading... Please wait...';
$_['text_order_id']                     = 'Order ID';
$_['text_capture']                      = 'Capture Payment';
$_['text_authorize']                    = 'Authorize Payment';
$_['text_default_squareup_name']        = 'Credit / Debit Card';

// Subscription
$_['text_trial']                        = '%s every %s %s for %s payments then ';
$_['text_subscription']                 = '%s every %s %s';
$_['text_length']                       = ' for %s payments';

// CRON
$_['text_cron_subject']                 = 'Square CRON job summary';
$_['text_cron_message']                 = 'Here is a list of all CRON tasks performed by your Square extension:';
$_['text_cron_summary_token_heading']   = 'Refresh of access token:';
$_['text_cron_summary_token_updated']   = 'Access token updated successfully!';
$_['text_cron_summary_error_heading']   = 'Transaction Errors:';
$_['text_cron_summary_fail_heading']    = 'Failed Transactions (Subscriptions Suspended):';
$_['text_cron_summary_success_heading'] = 'Successful Transactions:';
$_['text_cron_fail_charge']             = 'Subscription <strong>#%s</strong> could not get charged with <strong>%s</strong>';
$_['text_cron_success_charge']          = 'Subscription <strong>#%s</strong> was charged with <strong>%s</strong>';

// Token
$_['text_token_issue_customer_error']   = 'We are experiencing a technical outage in our payment system. Please try again later.';
$_['text_token_revoked_subject']        = 'Your Square access token has been revoked!';
$_['text_token_revoked_message']        = "The Square payment extension's access to your Square account has been revoked through the Square Dashboard. You need to verify your application credentials in the extension settings and connect again.";
$_['text_token_expired_subject']        = 'Your Square access token has expired!';
$_['text_token_expired_message']        = "The Square payment extension's access token connecting it to your Square account has expired. You need to verify your application credentials and CRON job in the extension settings and connect again.";

// Subscription Status
$_['text_squareup_profile_suspended']   = ' Your subscription payments have been suspended. Please contact us for more details.';
$_['text_squareup_trial_expired']       = ' Your trial period has expired.';
$_['text_squareup_subscription_expired'] = ' Your subscription payments have expired. This was your last payment.';

// Status Comments
$_['squareup_status_comment_authorized'] = 'The card payment has been authorized but not yet captured.';
$_['squareup_status_comment_captured']   = 'The card payment was authorized and subsequently captured (i.e., completed).';
$_['squareup_status_comment_voided']     = 'The card payment was authorized and subsequently voided (i.e., canceled).';
$_['squareup_status_comment_failed']     = 'The card payment failed.';

// Subscription Cancel
$_['text_canceled']                     = 'Success: You have successfully canceled this payment! We sent you a confirmation e-mail.';
$_['text_confirm_cancel']              = 'Are you sure you want to cancel the subscription payments?';
$_['text_order_history_cancel']        = 'You canceled your subscription. Your card will no longer be charged.';
$_['button_cancel']                    = 'Cancel Subscription Payment';

// Error
$_['error_squareup_cron_token']         = 'Error: Access token could not get refreshed. Please connect your Square Payment extension via the OpenCart admin panel.';
$_['error_missing_source_id']           = 'Error: Missing payment token!';
$_['error_missing_verification_token']  = 'Error: Missing verification token!';
$_['error_missing_intent']              = 'Error: Missing Square intent, it got lost in the session data!';
$_['error_missing_amount']              = 'Error: Missing Square payment amount, it got lost in the session data!';
$_['error_missing_currency']            = 'Error: Missing Square payment currency, it got lost in the session data!';
$_['error_missing_payment_link']        = 'Error: Missing Square Payment Link!';
$_['error_missing_order_tender_id']     = 'Error: Missing Square Order Tender ID!';
$_['error_payment_status']              = 'Error: Unexpected Square Payment status \'%1\', it should have been \'COMPLETED\' or \'PENDING\'!';
$_['error_payment']                     = 'Error: Unable to process payment!';
$_['error_missing_email']               = 'Error: Missing or invalid email address which is needed for subscription payment initialisation!';
$_['error_missing_phone']               = 'Error: Missing or invalid phone number which is needed for subscription payment initialisation!';
$_['error_customer']                    = 'Error: Unable to find customer on Square with email=\'%1\' and phone=\'%2\', it\'s needed for creating a subscription payment profile!';
$_['error_card']                        = 'Error: Unable to find card details on Square for customer with email=\'%1\', it\'s needed for creating a subscription payment profile!';
$_['error_not_cancelled']               = 'Error: %s';
$_['error_not_found']                   = 'Could not cancel subscription profile';

// Override Errors
$_['squareup_override_error_billing_address.country']  = 'Payment Address country is not valid. Please modify it and try again.';
$_['squareup_override_error_shipping_address.country'] = 'Shipping Address country is not valid. Please modify it and try again.';
$_['squareup_override_error_email_address']            = 'Your customer e-mail address is not valid. Please modify it and try again.';
$_['squareup_override_error_phone_number']             = 'Your customer phone number is not valid. Please modify it and try again.';
$_['squareup_error_field']                             = ' - Field: %s';

// Warning
$_['warning_test_mode']                 = 'Warning: Sandbox mode is enabled! Transactions will appear to go through, but no charges will be carried out.';
