<?php

namespace Drupal\user_entity_access\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Update config when field delete.
 */
class UserEntityAccessConfigUpdate implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a \Drupal\user_entity_access\EventSubscriber\UserEntityAccessConfigUpdate object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface; $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->getEditable('user_entity_access.settings');
  }

  /**
   * Update config whenever the delete field.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The Event to process.
   */
  public function onDelete(ConfigCrudEvent $event) {
    if (strpos($event->getConfig()->getName(), 'field.field.user.user.field_') === 0) {
      $config = $this->config;
      $field_config = $event->getConfig()->getName();
      $field_name = str_replace('field.field.user.user.', '', $field_config);
      $fields = $config->get('field_names');
      if ($fields) {
        if (array_key_exists($field_name, $fields)) {
          unset($fields[$field_name]);
          $config->set('field_names', $fields);
          $config->save();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::DELETE][] = ['onDelete'];
    return $events;
  }

}
