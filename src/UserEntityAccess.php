<?php

namespace Drupal\user_entity_access;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\Entity\User;

class UserEntityAccess {

  /**
   * Check user access to the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity access is checked.
   *
   * @param integer $uid
   *   Id of current user.
   *
   * @return bool
   *   TRUE if user has allowe access to the entity, FALSE otherwise.
   *
   */
  public function isAllowed(EntityInterface $entity, $uid) {
    $fields = \Drupal::config('user_entity_access.settings')->get('field_names');

    if ($fields) {
      // Get current user.
      $user = User::load($uid);

      // Find a field referencing this type of entity.
      foreach ($fields as $field_name => $target) {
        if ($user->hasField($field_name)) {
          $field = $user->getFieldDefinition($field_name);
          if ($field->getType() == 'entity_reference') {
            if ($target == $entity->getEntityTypeId()) {
              // Search reference on current entity in this field.
              $values = $user->get($field_name)->getValue();
              if ($values) {
                // Array of entities which is current field.
                $entities = [];
                foreach ($values as $value) {
                  // Field can be empty on the user edit page form.
                  if ($value) {
                    $entities[] = $value['target_id'];
                  }
                }
                // Whether is current entity in the entities.
                if (in_array($entity->id(), $entities)) {
                  $access = TRUE;
                  // It is hardly necessary to support two fields that refer to
                  // the same entity.
                  break;
                }
                else {
                  $access = FALSE;
                }
              }
            }
          }
        }
      }
    }

    return empty($access) ? FALSE : TRUE;
  }

}
