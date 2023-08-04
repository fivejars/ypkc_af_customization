<?php

namespace Drupal\ypkc_activity_finder\Plugin\search_api\processor;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds the Session time info to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "ypkc_af_session_time",
 *   label = @Translation("Session time"),
 *   description = @Translation("Adds time and week day to index."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = false,
 *   hidden = false,
 * )
 */
class SessionTime extends ProcessorPluginBase {

  /**
   * Site's default timezone.
   *
   * @var \DateTimeZone
   */
  protected $timezone;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $plugin->setTimeZone($container->get('config.factory'));

    return $plugin;
  }

  /**
   * Retrieves the default timezone.
   *
   * @var \DateTimeZone
   *   The config factory.
   */
  protected function getTimeZone() {
    return $this->timezone;
  }

  /**
   * Sets the default timezone.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   *
   * @return $this
   */
  protected function setTimeZone(ConfigFactoryInterface $config_factory) {
    $this->timezone = new \DateTimeZone($config_factory->get('system.date')->get('timezone')['default']);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Session time'),
        'description' => $this->t('Adds time and week day to index.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
        'is_list' => TRUE,
      ];
      $properties['ypkc_search_api_session_time'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $object = $item->getOriginalObject()->getValue();
    $dates = $object->field_session_time->referencedEntities();
    $result = [];
    foreach ($dates as $key => $date) {
      if (empty($date) || empty($date->field_session_time_date->getValue())) {
        continue;
      }
      $_period = $date->field_session_time_date->getValue()[0];
      $_from = DrupalDateTime::createFromTimestamp(strtotime($_period['value'] . 'Z'), $this->getTimeZone());
      $_to = DrupalDateTime::createFromTimestamp(strtotime($_period['end_value'] . 'Z'), $this->getTimeZone());
      $result[$key]['timestamp'] = [
        'value' => $_from->getTimestamp(),
        'end_value' => $_to->getTimestamp(),
      ];
      $days = [];
      foreach ($date->field_session_time_days->getValue() as $time_days) {
        $days[] = substr(ucfirst($time_days['value']), 0, 3);
      }
      $result[$key]['schedule_items'] = [
        'days' => implode(', ', $days),
        'time' => $_from->format('g:ia') . '-' . $_to->format('g:ia'),
      ];
      $result[$key]['grouped_schedule_items'][implode(', ', $days)][] = $_from->format('g:ia') . '-' . $_to->format('g:ia');
      $from_md = $_from->format('M d');
      $to_md = $_to->format('M d');
      // For equal starting and ending dates show only starting date.
      $result[$key]['full_dates'] = $from_md == $to_md ? $from_md : $from_md . '-' . $to_md;
      // It is necessary to calculate not the number of full weeks,
      // but the number of sessions that takes place in the specified period.
      // I.e. we calculate the amount of the last day of the week
      // with the session (for example, Friday) in the period.
      $result[$key]['weeks'] = $this->countDaysByName(end($days), $_from->getPhpDateTime(), $_to->getPhpDateTime());
    }

    // It will be easier to retrieve data on service from structured data, so
    // save values as JSON.
    if (!empty($result)) {
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($item->getFields(), NULL, 'ypkc_search_api_session_time');
      foreach ($fields as $field) {
        $field->addValue(json_encode($result));
      }
    }
  }

  /**
   * Helper function to calculate weekday quantity in period.
   *
   * The same function as in contrib OpenY AF.
   *
   * @param string $dayName
   *   Day name. eg 'Mon', 'Tue' etc.
   * @param \DateTimeInterface $start
   *   Start datetime.
   * @param \DateTimeInterface $end
   *   End datetime.
   *
   * @return int
   *   Weekday quantity in period.
   */
  protected function countDaysByName($dayName, \DateTimeInterface $start, \DateTimeInterface $end) {
    $count = 0;
    $interval = new \DateInterval('P1D');
    $period = new \DatePeriod($start, $interval, $end);

    foreach ($period as $day) {
      if ($day->format('D') === ucfirst(substr($dayName, 0, 3))) {
        $count++;
      }
    }
    return $count;
  }

}
