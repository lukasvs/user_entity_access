<?php

namespace Drupal\user_entity_access\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for user_entity_access settings.
 */
class UserEntityAccessForm extends ConfigFormBase {

  /**
   * @var EntityFieldManagerInterface $entityFieldManager.
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($config_factory);
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_entity_access_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['user_entity_access.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Select the fields that will control access to the entities to which they refer.'),
    ];

    // Get all fields of User Entity.
    $fields = $this->entityFieldManager->getFieldDefinitions('user', 'user');
    if ($fields) {
      // Get config field_names.
      $config = $this->config('user_entity_access.settings')->get('field_names');
      $form['#tree'] = TRUE;
      foreach ($fields as $field_name => $field) {
        // Set only necessary fields.
        if ($field instanceof FieldConfig && $field->getType() == 'entity_reference') {
          $target = $field->getSetting('target_type');
          $form['fields'][$field_name] = [
            '#type' => 'checkbox',
            '#title' => $field->getLabel() . ' (' . $field_name . '/' . $target . ')',
            '#default_value' => !empty($config) && array_key_exists($field_name, $config) ? 1 : 0,
            //@todo: add description key with link to add field for user account.
          ];
          $storage[$field_name] = $target;
        }
      }
      $form_state->setStorage($storage);
    } else {
      $form['markup']['#markup'] = $this->t('No available fields.');
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('user_entity_access.settings');
    $fields = $form_state->cleanValues()->getValues()['fields'];
    // Get map of field name and target type.
    $storage = $form_state->getStorage();
    // Array values of fields.
    $values = [];
    foreach ($fields as $key => $value) {
      if ($value) {
        $values[$key] = $storage[$key];
      }
    }
    $config->set('field_names', $values);
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
