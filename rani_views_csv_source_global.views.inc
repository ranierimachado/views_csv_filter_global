<?php

/**
 * @file
 * Views related hooks for rani_views_csv_source_global.
 */

/**
 * Implements hook_views_data_alter().
 */
function rani_views_csv_source_global_views_data_alter(array &$data) {
  // Add the global filter to the CSV table.
  $data['csv']['value_global'] = [
    'title' => t('CSV Field - Global Search'),
    'help' => t('Search across multiple CSV columns simultaneously.'),
    'filter' => [
      'id' => 'rani_csv_filter_global',
    ],
  ];
}
