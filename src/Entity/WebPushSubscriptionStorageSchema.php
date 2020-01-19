<?php

namespace Drupal\web_push_api\Entity;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the entity storage schema.
 */
class WebPushSubscriptionStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping): array {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);

    if ($storage_definition->getSetting('unique_key')) {
      $this->addSharedTableFieldUniqueKey($storage_definition, $schema);
    }

    return $schema;
  }

}
