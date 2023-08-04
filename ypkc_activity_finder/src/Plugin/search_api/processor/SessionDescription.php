<?php

namespace Drupal\ypkc_activity_finder\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds the Session's processed description value to the index.
 *
 * @SearchApiProcessor(
 *   id = "ypkc_af_session_description",
 *   label = @Translation("Session description"),
 *   description = @Translation("Adds trimmed and stripped description to the index."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = false,
 *   hidden = false,
 * )
 */
class SessionDescription extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];
    if (!$datasource) {
      $definition = [
        'label' => $this->t('Session description'),
        'description' => $this->t('Processed session description.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
        'is_list' => TRUE,
      ];
      $properties['ypkc_search_api_session_description'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $object = $item->getOriginalObject()->getValue();
    $field_value = $object->get('field_session_description')->getValue();
    $field_value = reset($field_value);

    $summary = text_summary(
      text: $field_value['value'] ?? '',
      format: $field_value['format'] ?? '',
      size: 600) ?? '';
    $description = html_entity_decode(strip_tags($summary));

    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, 'ypkc_search_api_session_description');
    foreach ($fields as $field) {
      $field->addValue($description);
    }
  }

}
