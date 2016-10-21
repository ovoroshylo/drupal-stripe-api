<?php

namespace Drupal\stripe_api;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Url;
use Drupal\stripe_api\Entity\StripeSubscriptionEntity;
use Stripe\Plan;
use Stripe\Stripe;
use Stripe\Subscription;
use Symfony\Component\Validator\Tests\Fixtures\EntityInterface;

/**
 * Class StripeApiService.
 *
 * @package Drupal\stripe_api
 */
class StripeApiService implements StripeApiServiceInterface {

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

    /** @var \Drupal\Core\Logger\LoggerChannelInterface */
    protected $logger;

    /**
     * Constructor.
     */
    public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LoggerChannelInterface $logger) {
        $this->config = $config_factory->get('stripe_api.settings');
        $this->apiKey = $this->config->get('api_key');
        $this->entityTypeManager = $entity_type_manager;
        $this->logger = $logger;
        Stripe::setApiKey($this->apiKey);
    }

    public function getMode() {
        $mode = $this->config->get('mode');

        return $mode;
    }

    public function getApiKey() {
        $api_key = $this->config->get('api_key');

        return $api_key;
    }

    public function getPubKey() {
        $pub_key = $this->config->get('pub_key');

        return $pub_key;
    }
}
