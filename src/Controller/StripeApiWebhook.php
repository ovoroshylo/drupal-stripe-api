<?php

namespace Drupal\stripe_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\stripe_api\Event\StripeApiWebhookEvent;
use Symfony\Component\HttpFoundation\Response;

class StripeApiWebhook extends ControllerBase {

  const FAKE_EVENT_ID = 'evt_00000000000000';

  /**
   * Captures the incoming webhook request.
   */
  public function handleIncomingWebhook() {
    $input = file_get_contents("php://input");
    $event_json = json_decode($input);
    $event = NULL;

    $config = $this->config('stripe_api.settings');

    // Validate the webhook if we are in LIVE mode.
    if (($config->get('mode') ?: 'test') === 'live' && ($event_json->livemode == TRUE || $event_json->id !== self::FAKE_EVENT_ID)) {
      $event = stripe_api_call('event', 'retrieve', $event_json->id);
      if (!$event) {
        \Drupal::logger('stripe_api')
          ->error('Invalid webhook event: @data', [
            '@data' => $input,
          ]);
        // This webhook event is invalid.
        return new Response('Forbidden', Response::HTTP_FORBIDDEN);
      }
    }

    // Dispatch the webhook event.
    $dispatcher = \Drupal::service('event_dispatcher');
    $e = new StripeApiWebhookEvent($event_json->type, $event_json->data, $event);
    $dispatcher->dispatch('stripe_api.webhook', $e);

    return new Response('okay', Response::HTTP_OK);
  }

}
