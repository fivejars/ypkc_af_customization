<?php

namespace Drupal\ypkc_activity_finder;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\openy_activity_finder\OpenyActivityFinderSolrBackend;
use Drupal\search_api\Query\ResultSet;

/**
 * {@inheritdoc}
 */
class YpkcActivityFinderSolrBackend extends OpenyActivityFinderSolrBackend {

  /**
   * {@inheritdoc}
   */
  public function runProgramSearch($parameters, $log_id): array {
    // Make a request to Search API.
    $results = $this->doSearchRequest($parameters);

    // Get results count.
    $data['count'] = $results->getResultCount();

    // Get facets and enrich, sort data, add static filters.
    $data['facets'] = $this->getFacets($results);

    // Set pager as current page number.
    $data['pager'] = isset($parameters['page']) && $data['count'] > self::TOTAL_RESULTS_PER_PAGE ? $parameters['page'] : 0;

    // Get pager structure.
    $data['pager_info'] = $this->getPages($data['count']);

    // Process results.
    $data['table'] = $this->processResults($results, $log_id);

    $locations = $this->getLocations();
    foreach ($locations as $key => $group) {
      $locations[$key]['count'] = 0;
      foreach ($group['value'] as $location) {
        if (!empty($data['facets']['locations'])) {
          foreach ($data['facets']['locations'] as $fl) {
            if (isset($fl['id']) && isset($location['value']) && $fl['id'] == $location['value']) {
              $locations[$key]['count'] += $fl['count'];
            }
          }
        }
      }
    }
    $data['groupedLocations'] = $locations;

    $data['sort'] = $parameters['sort'] ?? 'title__ASC';

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function processResults(ResultSet $results, $log_id): array {
    $data = [];
    $locations_info = $this->getLocationsInfo();
    /** @var \Drupal\search_api\Item\Item $result_item */
    foreach ($results->getResultItems() as $result_item) {
      $fields = $result_item->getFields(FALSE);
      $keys = [
        'field_session_instructor',
        'field_session_gender',
        'field_activity_type',
        'field_session_gender',
        'waitlist_capacity',
        'ypkc_session_description',
      ];
      foreach ($keys as $key) {
        if (!array_key_exists($key, $fields)) {
          $fields[$key] = NULL;
        }
      }

      $schedule_items = $grouped_schedule_items = [];
      $session_time = json_decode($fields['ypkc_session_time']?->getValues()[0] ?? [], TRUE);
      foreach ($session_time as $item) {
        $schedule_items[] = $item['schedule_items'];
        $grouped_schedule_items = $item['grouped_schedule_items'];
        $full_dates = $item['full_dates'];
        $weeks = $item['weeks'];
      }

      $availability_status = $fields['field_session_online']?->getValues()[0] ? 'open' : 'closed';

      $availability_note = '';
      if ($availability_status == 'closed') {
        $availability_note = $this->t('Registration closed')->__toString();
      }

      // As we don't have `field_learn_more` field in subcategory, assume that
      // this field is always empty (as in fact it was before).
      $learn_more = '';

      $instructor = $fields['field_session_instructor']?->getValues()[0];

      // YPKC customization.
      $price = $fields['field_price_description']?->getValues()[0] ?? '';

      $activity_type = $fields['field_activity_type']?->getValues()[0] ?? '';

      $atc_info = [];
      if ($activity_type == 'group') {
        // Create "Add to calendar" info for "group" activity types.
        // Example of calendar format 2018-08-21 14:15:00.
        $atc_info['time_start_calendar'] = DrupalDateTime::createFromTimestamp($session_time[0]['timestamp']['value'], $this->timezone)->format('Y-m-d H:i:s');
        $atc_info['time_end_calendar'] = DrupalDateTime::createFromTimestamp($session_time[0]['timestamp']['end_value'], $this->timezone)->format('Y-m-d H:i:s');
        $atc_info['timezone'] = date_default_timezone_get();
      }
      $wait_list_availability = $fields['waitlist_capacity']?->getValues()[0] ?? 0;
      $session_location = $fields['field_session_location']?->getValues()[0] ?? 0;
      $location_info = $session_location ? $locations_info[$session_location] : [];

      $item_data = [
        'nid' => $fields['nid']?->getValues()[0],
        'availability_note' => $availability_note,
        'availability_status' => $availability_status,
        'activity_type' => $activity_type,
        'dates' => $full_dates ?? '',
        'weeks' => $weeks ?? '',
        'schedule' => $schedule_items,
        'grouped_schedule' => $grouped_schedule_items,
        'days' => $schedule_items[0]['days'] ?? '',
        'times' => $schedule_items[0]['time'] ?? '',
        'location' => $session_location,
        'location_id' => $location_info ? $location_info['nid'] : '',
        'location_info' => $location_info,
        'instructor' => $instructor,
        'log_id' => $log_id,
        'name' => $fields['title']->getValues()[0]->getText(),
        'price' => $price,
        'link' => $fields['field_session_reg_link']?->getValues()[0],
        'description' => $fields['ypkc_session_description']?->getValues()[0],
        'ages' => $fields['ypkc_session_ages']?->getValues()[0],
        'gender' => $fields['field_session_gender']?->getValues()[0] ?? '',
        // We keep empty variables in order to have the same structure with
        // other backends (e.g. Daxko) for avoiding unexpected errors.
        'program_id' => $fields['field_activity_category']?->getValues()[0] ?? '',
        'program_subcategory' => $fields['activity_program_subcategory_title']?->getValues()[0] ?? '',
        'program_subcategory_path' => $fields['activity_program_subcategory_path']?->getValues()[0] ?? '',
        'offering_id' => '',
        'info' => [],
        'location_name' => '',
        'location_address' => '',
        'location_phone' => '',
        'spots_available' => $fields['field_availability']?->getValues()[0] ?? '',
        'status' => $availability_status,
        'note' => $availability_note,
        'learn_more' => $learn_more,
        'more_results' => '',
        'more_results_type' => 'program',
        'program_name' => $fields['title']->getValues()[0]->getText(),
        'atc_info' => $atc_info,
        'wait_list_availability' => $fields['waitlist_unlimited_capacity']?->getValues()[0] ? 1 : $wait_list_availability,
      ];
      $data[] = $item_data;
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    $filters = parent::getFilters();
    // Unset this filter, as we don't use it.
    unset($filters['af_weeks']);
    return $filters;
  }

}
