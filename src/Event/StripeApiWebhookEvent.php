<?php
namespace Drupal\stripe_api\Event;

use Symfony\Component\EventDispatcher\Event;

class StripeApiWebhookEvent extends Event {

  public $type, $data, $event;

  public function __construct($type, $data, \Stripe\Event $event = NULL) {
    $this->type = $type;
    $this->data = $data;
    $this->event = $event;
  }

}
