<?php

namespace Drupal\ypkc_activity_finder\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\ypkc_activity_finder\Helper\AfAgeHelper;

/**
 * Adds the Session's processed age value to the index.
 *
 * @SearchApiProcessor(
 *   id = "ypkc_af_session_ages",
 *   label = @Translation("Session ages"),
 *   description = @Translation("Adds processed ages to the index."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = false,
 *   hidden = false,
 * )
 */
class SessionAges extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];
    if (!$datasource) {
      $definition = [
        'label' => $this->t('Session ages'),
        'description' => $this->t('Processed session ages.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
        'is_list' => TRUE,
      ];
      $properties['ypkc_search_api_session_ages'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $object = $item->getOriginalObject()->getValue();
    $min_age = $object->get('field_session_min_age')?->value ?? 0;
    $max_age = $object->get('field_session_max_age')?->value ?? 0;

    $processed_ages = AfAgeHelper::convertData([$min_age, $max_age]);

    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, 'ypkc_search_api_session_ages');
    foreach ($fields as $field) {
      $field->addValue($processed_ages);
    }
  }

}
