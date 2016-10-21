<?php
/**
 * @file
 * Contains the default webhook controller.
 */
namespace Drupal\stripe_api\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\stripe_api\Event\StripeApiWebhookEvent;
use Stripe\Event;
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
  public function handleIncomingWebhook( Request $request ) {

      if ( 0 === strpos( $request->headers->get( 'Content-Type' ), 'application/json' ) ) {

          $input = $request->getContent();
          $decoded_input = json_decode( $input, TRUE );
          $config = $this->config('stripe_api.settings');
          $mode = $config->get('mode') ?: 'test';

          if (!$event = $this->isValidWebhook($mode, $decoded_input)) {
              $this->getLogger('stripe_api')
                ->error('Invalid webhook event: @data', [
                  '@data' => $input,
                ]);
              return new Response(NULL, Response::HTTP_FORBIDDEN);
          }

          /** @var LoggerChannelInterface $logger */
          $logger = $this->getLogger('stripe_api');
          $logger->info("Stripe webhook received event:\n @event", ['@event' => (string) $event]);

          // Dispatch the webhook event.
          $dispatcher = \Drupal::service('event_dispatcher');
          $e = new StripeApiWebhookEvent($event->type, $decoded_input->data, $event);
          $dispatcher->dispatch('stripe_api.webhook', $e);

          return new Response('Okay', Response::HTTP_OK);

      }

      return new Response(NULL, Response::HTTP_FORBIDDEN);
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
  private function isValidWebhook($mode, $data) {
      if (!empty($data->id)
        && $mode === 'live'
        && ($data->livemode == TRUE || $data->id !== self::FAKE_EVENT_ID)) {

          // Verify the event by fetching it from Stripe.
          return Event::retrieve($data->id);
      }

      return FALSE;
  }

}
