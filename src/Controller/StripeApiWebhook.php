<?php
/**
 * @file
 * Contains the default webhook controller.
 */
namespace Drupal\stripe_api\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\stripe_api\Event\StripeApiWebhookEvent;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class StripeApiWebhook
 *
 * Provides the route functionality for stripe_api.webhook route.
 */
class StripeApiWebhook extends ControllerBase {

  // Fake ID from Stripe we can check against.
  const FAKE_EVENT_ID = 'evt_00000000000000';

  /**
   * Captures the incoming webhook request.
   */
  public function handleIncomingWebhook() {
    global $request;
    $input = $request->getContent();
    $event_json = (object) Json::decode($input);

    $config = $this->config('stripe_api.settings');

    // Validate the webhook.
    if (!($event = $this->isValidWebhook($config->get('mode') ?: 'test', $event_json))) {
      // This webhook event is invalid.
      \Drupal::logger('stripe_api')
        ->error('Invalid webhook event: @data', [
          '@data' => $input,
        ]);
      return new Response('Forbidden', Response::HTTP_FORBIDDEN);
    }

    // Dispatch the webhook event.
    $dispatcher = \Drupal::service('event_dispatcher');
    $e = new StripeApiWebhookEvent($event_json->type, $event_json->data, $event);
    $dispatcher->dispatch('stripe_api.webhook', $e);

    // Everything is okay.
    return new Response('Okay', Response::HTTP_OK);
  }

  /**
   * Determines if a webhook is valid.
   *
   * @param string $mode
   *   Stripe API mode. Either 'live' or 'test'.
   * @param object $event_json
   *   Stripe event object parsed from JSON.
   *
   * @return bool|\Stripe\Event
   *   Returns TRUE if the webhook is valid or the Stripe Event object.
   */
  private function isValidWebhook($mode, $event_json = NULL) {
    libraries_load('stripe');
    $event = new \Stripe\Event();
    if (!$event_json || !is_array($event_json->data)) {
      // Invalid data or couldn't parse.
      return NULL;
    }
    if ($mode === 'live' && ($event_json->livemode == TRUE || $event_json->id !== self::FAKE_EVENT_ID)) {
      // Check event if we're in live mode and this isn't a test event.
      $event = stripe_api_call('event', 'retrieve', $event_json->id);
      if (!$event) {
        // This webhook event is invalid.
        return NULL;
      }
    }
    return $event;
  }

}
