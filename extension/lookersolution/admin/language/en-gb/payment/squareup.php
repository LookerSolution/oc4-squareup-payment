<?php
/**
 * @package    LookerSolution\SquarePayment
 * @author     Ali Ahmed <ali@lookersolution.com>
 * @copyright  Copyright (c) 2026 LookerSolution (https://www.lookersolution.com)
 * @license    https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link       https://github.com/LookerSolution/oc4-squareup-payment
 */

// Heading
$_['heading_title']                                     = 'Square';
$_['heading_title_payment']                             = 'View Payment #%s';

// Help
$_['help_total']                                        = 'The checkout total the order must reach before this payment method becomes active.';
$_['help_local_cron']                                   = 'Insert this command in your web server CRON tab. Set it up to run at least once per day.';
$_['help_remote_cron']                                  = 'Use this URL to set up a CRON task via a web-based CRON service. Set it up to run at least once per day.';
$_['help_recurring_status']                             = 'Enable to allow periodic subscription payments. You must also set up the CRON task.';
$_['help_cron_email']                                   = 'A summary of the subscription task will be sent to this e-mail after completion.';
$_['help_cron_email_status']                            = 'Enable to receive a summary after every CRON task.';
$_['help_notify_recurring_success']                     = 'Notify customers about successful subscription payments.';
$_['help_notify_recurring_fail']                        = 'Notify customers about failed subscription payments.';

// Tab
$_['tab_setting']                                       = 'Settings';
$_['tab_payment']                                       = 'Payments';
$_['tab_cron']                                          = 'CRON';
$_['tab_subscription']                                  = 'Subscriptions';
$_['tab_webhook']                                       = 'Webhooks';

// Text
$_['text_access_token_expires_label']                   = 'Access token expires';
$_['text_access_token_expires_placeholder']             = 'Not setup';
$_['text_acknowledge_cron']                             = 'I confirm that I have set up an automated CRON task using one of the methods above.';
$_['text_admin_notifications']                          = 'Admin notifications';
$_['text_authorize_label']                              = 'Authorize (delayed capture)';
$_['text_canceled_success']                             = 'Success: You have successfully canceled this payment!';
$_['text_capture']                                      = 'Capture';
$_['text_capture_section_heading']                      = 'Payment Capture Settings';
$_['text_clear']                                        = 'Clear';
$_['text_client_id_help']                               = 'Get this from the Developer Applications page on Square';
$_['text_client_id_label']                              = 'Square Application ID';
$_['text_client_id_placeholder']                        = 'Square Application ID';
$_['text_client_secret_help']                           = 'Get this from the Developer Applications page on Square';
$_['text_client_secret_label']                          = 'OAuth Application Secret';
$_['text_client_secret_placeholder']                    = 'OAuth Application Secret';
$_['text_confirm_action']                               = 'Are you sure?';
$_['text_confirm_cancel']                               = 'Are you sure you want to cancel the subscription payments?';
$_['text_confirm_capture']                              = 'You are about to capture the following amount: <strong>%s</strong>. Click OK to proceed.';
$_['text_confirm_refund']                               = 'Please provide a reason for the refund:';
$_['text_confirm_refresh']                              = 'You are about to reload the latest payment details. Click OK to proceed.';
$_['text_confirm_void']                                 = 'You are about to void the following amount: <strong>%s</strong>. Click OK to proceed.';
$_['text_connected']                                    = 'Connected';
$_['text_connected_info']                               = "Reconnect if you want to switch accounts or have manually revoked this extension's access from the Square App console. Manually refresh the access token if it has been close to 45 days since the last sale or reconnect.";
$_['text_connection_section']                           = 'Square Connection';
$_['text_content_security_advice']                      = 'See <a href="https://developer.squareup.com/docs/web-payments/content-security-policy" target="_blank">Square Developer Docs</a> or <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP" target="_blank">Mozilla - Content Security Policy</a> for more details.';
$_['text_content_security_help']                        = 'The content security policy used in a meta tag for the checkout page. Not needed for Quick Pay mode.';
$_['text_content_security_label']                       = 'Content Security Policy';
$_['text_cron_email']                                   = 'Send task summary to this e-mail:';
$_['text_cron_email_status']                            = 'Send e-mail summary:';
$_['text_customer_notifications']                       = 'Customer notifications';
$_['text_debug_disabled']                               = 'Disabled';
$_['text_debug_enabled']                                = 'Enabled';
$_['text_debug_help']                                   = 'API requests and responses will be logged in the OpenCart error log. Use only for debugging and development purposes.';
$_['text_debug_label']                                  = 'Debug Logging';
$_['text_delay_capture_help']                           = 'Authorize payments only, or authorize and capture payments automatically';
$_['text_delay_capture_label']                          = 'Delay capture';
$_['text_disabled_connect_help_text']                   = 'The client ID and secret are required fields.';
$_['text_edit_heading']                                 = 'Edit Square';
$_['text_enable_sandbox_help']                          = 'Enable sandbox mode for testing payments';
$_['text_enable_sandbox_label']                         = 'Enable sandbox mode';
$_['text_executables']                                  = 'CRON execution methods';
$_['text_extension']                                    = 'Extensions';
$_['text_extension_status']                             = 'Extension status';
$_['text_extension_status_disabled']                    = 'Disabled';
$_['text_extension_status_enabled']                     = 'Enabled';
$_['text_extension_status_help']                        = 'Enable or disable the payment method';
$_['text_insert_amount']                                = 'Please insert the refund amount. Maximum: %s in %s:';
$_['text_loading']                                      = 'Loading data... Please wait...';
$_['text_loading_short']                                = 'Please wait...';
$_['text_local_cron']                                   = 'Method #1 - CRON Task:';
$_['text_location_error']                               = 'There was an error when trying to sync locations and token: %s';
$_['text_location_help']                                = 'Select which configured Square location to be used for payments. Must have card processing capabilities enabled.';
$_['text_location_label']                               = 'Location';
$_['text_merchant_info_section_heading']                = 'Merchant Information';
$_['text_merchant_name_label']                          = 'Merchant name';
$_['text_merchant_name_placeholder']                    = 'Not setup';
$_['text_no_appropriate_locations_warning']             = 'There are no locations capable of online card processing setup in your Square account.';
$_['text_no_payments']                                  = 'No payments have been logged yet.';
$_['text_not_connected']                                = 'Not connected';
$_['text_not_connected_info']                           = 'By clicking this button you will connect this module to your Square account and activate the service.';
$_['text_notification_ssl']                             = 'Make sure you have SSL enabled on your checkout page. Otherwise, the extension will not work.';
$_['text_notify_recurring_fail']                        = 'Subscription Payment Failed:';
$_['text_notify_recurring_success']                     = 'Subscription Payment Successful:';
$_['text_ok']                                           = 'OK';
$_['text_order_history_cancel']                         = 'An administrator has canceled the subscription payments. The card will no longer be charged.';
$_['text_payment_method_name_help']                     = 'Checkout payment method name';
$_['text_payment_method_name_label']                    = 'Payment method name';
$_['text_payment_method_name_placeholder']              = 'Credit / Debit Card';
$_['text_quick_pay_help']                               = 'Choose between redirected Quick Pay payment gateway or a local Web Payments SDK form. Quick Pay authorizes and captures a payment instantly.';
$_['text_quick_pay_label']                              = 'Quick Pay';
$_['text_quick_pay_no']                                 = 'No - Use Web Payments SDK on checkout page';
$_['text_quick_pay_yes']                                = 'Yes - Redirect to Square hosted payment gateway';
$_['text_recurring_info']                               = 'Please make sure to set up a CRON task using one of the methods below. CRON jobs handle automatic refresh of your API access token and processing of subscription payments.';
$_['text_recurring_status']                             = 'Status of subscription payments:';
$_['text_redirect_uri_help']                            = 'Paste this link into the Redirect URI field under Manage Application / OAuth';
$_['text_redirect_uri_label']                           = 'Square OAuth Redirect URL';
$_['text_refresh_access_token_success']                 = 'Successfully refreshed the connection to your Square account.';
$_['text_refresh_token']                                = 'Re-create token';
$_['text_refund']                                       = 'Refund';
$_['text_refund_details']                               = 'Refund details';
$_['text_refunded_amount']                              = 'Refunded: %s. Status of the refund: %s. Reason for the refund: %s';
$_['text_remote_cron']                                  = 'Method #2 - Remote CRON:';
$_['text_reset']                                        = 'Reset';
$_['text_sale_label']                                   = 'Sale (instant capture)';
$_['text_sandbox_access_token_help']                    = 'Get this from the Developer Applications page on Square';
$_['text_sandbox_access_token_label']                   = 'Sandbox Access Token';
$_['text_sandbox_access_token_placeholder']             = 'Sandbox Access Token';
$_['text_sandbox_client_id_help']                       = 'Get this from the Developer Applications page on Square';
$_['text_sandbox_client_id_label']                      = 'Sandbox Application ID';
$_['text_sandbox_client_id_placeholder']                = 'Sandbox Application ID';
$_['text_sandbox_disabled_label']                       = 'Disabled';
$_['text_sandbox_enabled']                              = 'Sandbox mode is enabled! Payments will appear to go through, but no charges will be carried out.';
$_['text_sandbox_enabled_label']                        = 'Enabled';
$_['text_sandbox_section_heading']                      = 'Square Sandbox Settings';
$_['text_select_location']                              = 'Select location';
$_['text_settings_section_heading']                     = 'Square Settings';
$_['text_success']                                      = 'Success: You have modified Square payment settings!';
$_['text_success_capture']                              = 'Payment successfully captured!';
$_['text_success_refresh']                              = 'Payment details successfully reloaded!';
$_['text_success_refund']                               = 'Payment successfully refunded!';
$_['text_success_void']                                 = 'Payment successfully voided!';
$_['text_token_expired']                                = 'Your Square access token has expired! <a href="%s">Click here</a> to renew it now.';
$_['text_token_expiry_warning']                         = 'Your Square access token will expire on %s. <a href="%s">Click here</a> to renew it now.';
$_['text_token_revoked']                                = 'Your Square access token has expired or has been revoked! <a href="%s">Click here</a> to re-authorize the Square extension.';
$_['text_payment_statuses']                             = 'Payment Statuses';
$_['text_view']                                         = 'View More';
$_['text_void']                                         = 'Void';
$_['text_na']                                           = 'N/A';
$_['text_no_reason_provided']                           = 'Reason not provided.';

// Enhanced Payment Methods
$_['text_payment_methods_section']                      = 'Payment Methods';
$_['text_apple_pay_label']                              = 'Apple Pay';
$_['text_apple_pay_help']                               = 'Enable Apple Pay in checkout. Requires HTTPS and domain verification.';
$_['text_google_pay_label']                             = 'Google Pay';
$_['text_google_pay_help']                              = 'Enable Google Pay in checkout. Requires HTTPS.';
$_['text_cashapp_pay_label']                            = 'Cash App Pay';
$_['text_cashapp_pay_help']                             = 'Enable Cash App Pay in checkout.';
$_['text_afterpay_label']                               = 'Afterpay / Clearpay';
$_['text_afterpay_help']                                = 'Enable Afterpay / Clearpay buy now pay later. Not available for subscriptions.';
$_['text_ach_label']                                    = 'ACH Bank Transfer';
$_['text_ach_help']                                     = 'Enable ACH direct debit bank transfers. Payments settle in 3-5 business days.';

// Webhooks
$_['text_webhook_section']                              = 'Webhook Configuration';
$_['text_webhook_url_label']                            = 'Webhook URL';
$_['text_webhook_url_help']                             = 'Enter this URL in your Square Developer Console webhook subscription settings.';
$_['text_webhook_signature_key_label']                  = 'Webhook Signature Key';
$_['text_webhook_signature_key_help']                   = 'Get this from the Square Developer Console after creating a webhook subscription.';
$_['text_webhook_events']                               = 'Webhook Event Log';
$_['text_no_webhook_events']                            = 'No webhook events have been received yet.';

// Statuses
$_['squareup_status_comment_authorized']                = 'The card payment has been authorized but not yet captured.';
$_['squareup_status_comment_captured']                  = 'The card payment was authorized and subsequently captured (i.e., completed).';
$_['squareup_status_comment_voided']                    = 'The card payment was authorized and subsequently voided (i.e., canceled).';
$_['squareup_status_comment_failed']                    = 'The card payment failed.';

// Entry
$_['entry_opencart_order_id']                           = 'OpenCart Order ID';
$_['entry_total']                                       = 'Total';
$_['entry_geo_zone']                                    = 'Geo Zone';
$_['entry_sort_order']                                  = 'Sort Order';
$_['entry_merchant_id']                                 = 'Merchant ID';
$_['entry_location_id']                                 = 'Location ID';
$_['entry_payment_id']                                  = 'Payment ID';
$_['entry_order_id']                                    = 'Order ID';
$_['entry_customer_id']                                 = 'Customer ID';
$_['entry_status']                                      = 'Status';
$_['entry_source_type']                                 = 'Source Type';
$_['entry_currency']                                    = 'Currency';
$_['entry_amount']                                      = 'Amount';
$_['entry_square_product']                              = 'Square Product';
$_['entry_application_id']                              = 'Application ID';
$_['entry_refunded_currency']                           = 'Refunded Currency';
$_['entry_refunded_amount']                             = 'Refunded Amount';
$_['entry_card_fingerprint']                            = 'Card Fingerprint';
$_['entry_user_agent']                                  = 'User Agent';
$_['entry_ip']                                          = 'IP';
$_['entry_created_at']                                  = 'Created At';
$_['entry_updated_at']                                  = 'Updated At';
$_['entry_first_name']                                  = 'First Name';
$_['entry_last_name']                                   = 'Last Name';
$_['entry_address_line_1']                              = 'Address Line 1';
$_['entry_address_line_2']                              = 'Address Line 2';
$_['entry_address_line_3']                              = 'Address Line 3';
$_['entry_locality']                                    = 'Locality';
$_['entry_sublocality']                                 = 'Sublocality';
$_['entry_sublocality_2']                               = 'Sublocality 2';
$_['entry_sublocality_3']                               = 'Sublocality 3';
$_['entry_administrative_district_level_1']             = 'Administrative Level 1';
$_['entry_administrative_district_level_2']             = 'Administrative Level 2';
$_['entry_administrative_district_level_3']             = 'Administrative Level 3';
$_['entry_postal_code']                                 = 'Postal Code';
$_['entry_country']                                     = 'Country';
$_['entry_status_authorized']                           = 'Authorized';
$_['entry_status_captured']                             = 'Captured';
$_['entry_status_voided']                               = 'Voided';
$_['entry_status_failed']                               = 'Failed';
$_['entry_setup_confirmation']                          = 'Setup confirmation:';

// Override errors
$_['squareup_override_error_billing_address.country']   = 'Payment Address country is not valid. Please modify it and try again.';
$_['squareup_override_error_shipping_address.country']  = 'Shipping Address country is not valid. Please modify it and try again.';
$_['squareup_override_error_email_address']             = 'Your customer e-mail address is not valid. Please modify it and try again.';
$_['squareup_override_error_phone_number']              = 'Your customer phone number is not valid. Please modify it and try again.';
$_['squareup_error_field']                              = ' - Field: %s';

// Error
$_['error_permission']                                  = 'Warning: You do not have permission to modify Square payment settings!';
$_['error_permission_subscription']                     = 'Warning: You do not have permission to modify subscriptions!';
$_['error_payment_missing']                             = 'Payment not found!';
$_['error_no_ssl']                                      = 'Warning: SSL is not enabled on your admin panel. Please enable it to finish your configuration.';
$_['error_user_rejected_connect_attempt']               = 'Connection attempt was canceled by the user.';
$_['error_possible_xss']                                = 'We detected a possible cross site attack and have terminated your connection attempt. Please verify your application ID and secret and try again using the buttons in the admin panel.';
$_['error_invalid_email']                               = 'The provided e-mail address is not valid!';
$_['error_cron_acknowledge']                            = 'Please confirm you have set up a CRON job.';
$_['error_client_id']                                   = 'The Application ID is a required field.';
$_['error_client_secret']                               = 'The OAuth Application Secret is a required field.';
$_['error_sandbox_client_id']                           = 'The Sandbox Application ID is a required field when sandbox mode is enabled.';
$_['error_sandbox_token']                               = 'The Sandbox Access Token is a required field when sandbox mode is enabled.';
$_['error_no_location_selected']                        = 'A location must be selected.';
$_['error_refresh_access_token']                        = "An error occurred when trying to refresh the extension's connection to your Square account. Please verify your application credentials and try again.";
$_['error_form']                                        = 'Please check the form for errors and try to save again.';
$_['error_token']                                       = 'An error was encountered while refreshing the token: %s';
$_['error_no_refund']                                   = 'Refund failed.';
$_['error_refund_too_large']                            = 'Planned refund amount is too large!';
$_['error_cancel_payment']                              = 'Unable to cancel payment!';
$_['error_capture_payment']                             = 'Unable to capture payment!';
$_['error_refresh_payment']                             = 'Unable to refresh payment status details.';

// Column
$_['column_payment_id']                                 = 'Payment ID';
$_['column_order_id']                                   = 'Order ID';
$_['column_customer']                                   = 'Customer';
$_['column_status']                                     = 'Status';
$_['column_source_type']                                = 'Source Type';
$_['column_amount']                                     = 'Amount';
$_['column_date_updated']                               = 'Date Updated';
$_['column_action']                                     = 'Action';
$_['column_refunds']                                    = 'Refunds';
$_['column_event_id']                                   = 'Event ID';
$_['column_event_type']                                 = 'Event Type';
$_['column_processed']                                  = 'Processed';

// Button
$_['button_void']                                       = 'Void';
$_['button_refund']                                     = 'Refund';
$_['button_capture']                                    = 'Capture';
$_['button_connect']                                    = 'Connect';
$_['button_reconnect']                                  = 'Reconnect';
$_['button_refresh']                                    = 'Refresh token';
$_['button_refresh_status']                             = 'Refresh';
$_['button_default_csp']                                = 'Reset to default content security policy';
$_['button_clear']                                      = 'Clear all content security settings';
