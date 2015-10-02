<?php

namespace Drupal\stripe_api\Controller;

use Drupal\Core\Controller\ControllerBase;

class StripeApiWebhook extends ControllerBase {

  /**
   * Captures the incoming webhook request.
   */
  public function handleIncomingWebhook() {
    $input = file_get_contents("php://input");
    $event_json = json_decode($input);
    $event = NULL;

    // Validate the webhook if we are in LIVE mode.
    if (variable_get('stripe_api_mode', 'test') === 'live' && ($event_json->livemode == TRUE || $event_json->id !== 'evt_00000000000000')) {
      $event = stripe_api_call('event', 'retrieve', $event_json->id);
      if (!$event) {
        watchdog('stripe_api', 'Invalid webhook event: @data', array(
          '@data' => $input,
        ), WATCHDOG_ERROR);
        // This webhook event is invalid.
        drupal_add_http_header('Status', '403 Forbidden');
        print 'Forbidden';
        exit;
      }
    }

    // Invoke webhooks for others to use.
    module_invoke_all('stripe_api_webhook', $event_json->type, $event_json->data, $event);
    module_invoke_all('stripe_api_webhook_' . str_replace('.', '_', $event_json->type), $event_json->data, $event);
    print 'okay';
  }

}