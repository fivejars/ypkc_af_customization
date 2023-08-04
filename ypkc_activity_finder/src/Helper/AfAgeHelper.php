<?php

namespace Drupal\ypkc_activity_finder\Helper;

/**
 * Class intended to help process ages for the Activity finder.
 */
class AfAgeHelper {

  /**
   * Date months to years transformation.
   *
   * Copy of the OpenyActivityFinderSolrBackend::convertData.
   *
   * @param array $ages
   *   Array with min and max age values.
   *
   * @return string
   *   String with month or year.
   */
  public static function convertData(array $ages = []): string {
    $ages_y = [];
    for ($i = 0; $i < count($ages); $i++) {
      if ($ages[$i] > 18) {
        if ($ages[$i] % 12) {
          $ages_y[$i] = number_format($ages[$i] / 12, 1, '.', '');
        }
        else {
          $ages_y[$i] = number_format($ages[$i] / 12, 0, '.', '');
        }
        if (isset($ages[$i + 1]) && $ages[$i + 1] == 0) {
          $ages_y[$i] .= t('+ years');
        }
        if (isset($ages[$i + 1]) && $ages[$i + 1] > 18 || !isset($ages[$i + 1])) {
          if ($i % 2 || (!$ages[$i + 1]) && !($i % 2)) {
            // phpcs:ignore
            $ages_y[$i] .= t(' years');
          }
        }
      }
      else {
        if ($ages[$i] <= 18 && $ages[$i] != 0) {
          $plus = '';
          if (isset($ages[$i + 1]) && $ages[$i + 1] == 0) {
            $plus = ' + ';
          }
          $ages_y[$i] = $ages[$i] . \Drupal::translation()->formatPlural($ages[$i], ' month', ' months' . $plus);
        }
        else {
          if ($ages[$i] == 0 && isset($ages[$i + 1])) {
            $ages_y[$i] = $ages[$i];
          }
        }
      }
    }
    return implode(' - ', $ages_y);
  }

}
