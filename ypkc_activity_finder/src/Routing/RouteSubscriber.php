<?php

namespace Drupal\ypkc_activity_finder\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Replace default AF controller for /af/get-data/ method.
    if ($route = $collection->get('openy_activity_finder.get_results')) {
      $route->setDefault('_controller', 'Drupal\ypkc_activity_finder\Controller\ActivityFinderController::getData');
    }
  }

}
