<?php

namespace Drupal\rani_views_csv_source_global\Service;

/**
 * Helper service for global filter operations.
 */
class GlobalFilterHelper {

  /**
   * Normalize column names input to array.
   *
   * @param mixed $columns_names
   *   The column names (can be string, array, or iterable).
   *
   * @return array
   *   Array of column names.
   */
  public function normalizeColumns($columns_names): array {
    if (is_iterable($columns_names) && !is_string($columns_names)) {
      return is_array($columns_names) ? $columns_names : iterator_to_array($columns_names);
    }
    return [$columns_names];
  }

}
