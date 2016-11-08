<?php

namespace Drupal\stripe_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Stripe\Stripe;

/**
 * Class StripeApiService.
 *
 * @package Drupal\stripe_api
 */
class StripeApiService {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface*/
  protected $logger;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LoggerChannelInterface $logger) {
    $this->config = $config_factory->get('stripe_api.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    Stripe::setApiKey($this->getApiKey());
  }

  /**
   *
   */
  public function getMode() {
    $mode = $this->config->get('mode');

    if (!$mode) {
      $mode = 'test';
    }

    return $mode;
  }

  /**
   *
   */
  public function getApiKey() {
    $config_key = $this->getMode() . '_secret_key';
    $api_key = $this->config->get($config_key);

    return $api_key;
  }

  /**
   *
   */
  public function getPubKey() {
    $config_key = $this->getMode() . '_public_key';
    $pub_key = $this->config->get($config_key);;

    return $pub_key;
  }

  /**
   * Makes a call to the Stripe API.
   *
   * @param string $obj
   *   Stripe object. Can be a Charge, Refund, Customer, Subscription, Card, Plan,
   *   Coupon, Discount, Invoice, InvoiceItem, Dispute, Transfer, TransferReversal,
   *   Recipient, BankAccount, ApplicationFee, FeeRefund, Account, Balance, Event,
   *   Token, BitcoinReceiver, FileUpload.
   *
   * @param string $method
   *   Stripe object method. Common operations include retrieve, all, create,.
   *
   * @param mixed $params
   *   Additional params to pass to the method. Can be an array, string.
   *
   * @return Stripe\ApiResource
   *   Returns the ApiResource or NULL on error.
   */
  public function call($obj, $method = NULL, $params = NULL) {
    $obj = ucfirst($obj);
    $class = '\\Stripe\\' . $obj;
    if ($method) {
      try {
        return call_user_func([$class, $method], $params);
      }
      catch (Exception $e) {
        \Drupal::logger('stripe_api')->error('Error: @error <br /> @args', [
          '@args' => Json::encode([
            'object' => $obj,
            'method' => $method,
            'params' => $params,
          ]),
          '@error' => $e->getMessage(),
        ]);
        return NULL;
      }
    }
    return $class;
  }

}
