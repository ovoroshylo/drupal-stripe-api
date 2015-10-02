<?php
namespace Drupal\stripe_api\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class StripeApiAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'stripe_api_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'stripe_api.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('stripe_api.settings');

    $form['link'] = [
      '#markup' => $this->t('!dashboard | !api_keys | !docs<br /><br />', [
        '!dashboard' => \Drupal::l($this->t('Dashboard'), Url::fromUri('https://dashboard.stripe.com', ['attributes' => ['target' => '_blank']])),
        '!api_keys' => \Drupal::l($this->t('API Keys'), Url::fromUri('https://dashboard.stripe.com/account/apikeys', ['attributes' => ['target' => '_blank']])),
        '!docs' => \Drupal::l($this->t('Docs'), Url::fromUri('https://stripe.com/docs/api', ['attributes' => ['target' => '_blank']])),
      ]),
    ];
    $form['test_secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stripe Secret Key (test)'),
      '#default_value' => $config->get('test.secret_key'),
    ];
    $form['test_public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stripe Public Key (test)'),
      '#default_value' => $config->get('test.public_key'),
    ];
    $form['live_secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stripe Secret Key (live)'),
      '#default_value' => $config->get('live.secret_key'),
    ];
    $form['live_public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stripe Public Key (live)'),
      '#default_value' => $config->get('live.public_key'),
    ];
    $form['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Mode'),
      '#options' => [
        'test' => $this->t('Test'),
        'live' => $this->t('Live'),
      ],
      '#default_value' => $config->get('mode') ?: 'test',
    ];

    $form['webhook_url'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('Webhook URL'),
      '#default_value' => Url::fromRoute('stripe_api.webhook', [], ['absolute' => TRUE])
        ->toString(),
      '#description' => $this->t('Add this webhook path in the !link', [
        '!link' => \Drupal::l($this->t('Stripe Dashboard'), Url::fromUri('https://dashboard.stripe.com/account/webhooks', ['attributes' => ['target' => '_blank']])),
      ]),
    ];

    $form['log_webhooks'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log incoming webhooks'),
      '#default_value' => $config->get('log_webhooks') ?: TRUE,
    ];

    if (_stripe_api_secret_key()) {
      $form['stripe_test'] = [
        '#type' => 'button',
        '#value' => $this->t('Test Stripe Connection'),
        '#ajax' => [
          'callback' => [$this, 'testStripeConnection'],
          'wrapper' => 'stripe-connect-results',
          'method' => 'append',
        ],
        '#suffix' => '<div id="stripe-connect-results"></div>',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * AJAX callback to test the Stripe connection.
   */
  function testStripeConnection(array &$form, FormStateInterface $form_state) {
    $account = stripe_api_call('account', 'retrieve');
    if ($account && $account->email) {
      return ['#markup' => $this->t('Success! Account email: %email', ['%email' => $account->email])];
    }
    else {
      return ['#markup' => $this->t('Error! Could not connect!')];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('stripe_api.settings')
      ->set('mode', $form_state->getValue('mode'))
      ->set('log_webhooks', $form_state->getValue('log_webhooks'))
      ->set('test.secret_key', $form_state->getValue('test_secret_key'))
      ->set('test.public_key', $form_state->getValue('test_public_key'))
      ->set('live.secret_key', $form_state->getValue('live_secret_key'))
      ->set('live.public_key', $form_state->getValue('live_public_key'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
