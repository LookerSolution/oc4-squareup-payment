# Square Payment Gateway for OpenCart 4

Accept Square payments in OpenCart with support for cards, digital wallets, buy now pay later, ACH bank payments, recurring subscriptions, and webhooks.

## Features

- Credit and debit card payments through the Square API
- Digital wallets: Apple Pay, Google Pay, and Cash App Pay
- Buy now, pay later: Afterpay and Clearpay
- ACH bank transfer payments
- Recurring subscription payments, processed on a schedule via CRON
- Webhook handling to keep payment and refund statuses in sync
- Sandbox and production environments

## Compatibility

- OpenCart 4.x

## Installation

1. Download the latest `lookersolution.ocmod.zip` from the [Releases](https://github.com/LookerSolution/oc4-squareup-payment/releases/latest) page.
2. In the OpenCart admin, go to Extensions then Installer, and upload the file.
3. Go to Extensions then Extensions, and choose Payments from the dropdown.
4. Find Square, click Install, then Edit to configure it.

The package is self-contained. All dependencies are bundled, so no Composer step is required on the store.

## Configuration

The settings screen is organised into tabs:

- Settings: connect your Square account and set the Application ID, Location ID, Merchant ID, environment (Sandbox or Production), geo zone, order statuses, and sort order.
- Payments: review and manage captured payments.
- Subscriptions: manage recurring subscription payments.
- CRON: the URL to run for processing scheduled subscription charges.
- Webhooks: the endpoint to add in your Square dashboard so payment and refund updates are received.

Use Sandbox to test the full flow before switching to Production for live payments.

## License

Released under the GNU General Public License v3.0. See [LICENSE](LICENSE).
