<?php

namespace Drupal\ypkc_activity_finder\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\openy_activity_finder\Controller\ActivityFinderController as BaseController;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 */
class ActivityFinderController extends BaseController {

  /**
   * {@inheritdoc}
   */
  public function getData(Request $request) {
    $log_id = 0;

    $parameters = $request->query->all();
    foreach ($parameters as &$value) {
      $value = urldecode($value);
    }

    $data = $this->backend->runProgramSearch($parameters, $log_id);

    /** @var \Drupal\Core\Config\Config $expanderSectionsConfig */
    $expanderSectionsConfig = $this->config('openy_activity_finder.settings');
    $data['expanderSectionsConfig'] = $expanderSectionsConfig->getRawData();

    $cacheable_metadata = CacheableMetadata::createFromRenderArray([
      '#cache' => [
        'max-age' => Cache::PERMANENT,
        'tags' => [
          'search_api_list:default',
        ],
        'contexts' => ['url'],
      ],
    ]);
    return (new CacheableJsonResponse($data))->addCacheableDependency($cacheable_metadata);
  }

}
