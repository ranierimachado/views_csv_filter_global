<?php

namespace Drupal\rani_views_csv_source_global\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views_csv_source\Plugin\views\ColumnSelectorTrait;

/**
 * Global filter handler for searching across multiple CSV columns.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("rani_csv_filter_global")
 */
class RaniCsvFilterGlobal extends FilterPluginBase {

  use ColumnSelectorTrait;

  /**
   * Exposed filter options.
   *
   * @var bool
   */
  protected $alwaysMultiple = TRUE;

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    $summary = parent::adminSummary();
    if (empty($this->options['key'])) {
      return $summary;
    }

    // Handle array of columns for global filter.
    $columns = is_array($this->options['key'])
      ? implode(', ', $this->options['key'])
      : $this->options['key'];

    return $summary ? $summary . " [Columns: {$columns}]" : "[Columns: {$columns}]";
  }


  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['key'] = ['default' => ''];
    $options['expose']['contains']['required'] = ['default' => FALSE];
    $options['expose']['contains']['placeholder'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultExposeOptions() {
    parent::defaultExposeOptions();
    $this->options['expose']['placeholder'] = NULL;
    $this->options['expose']['identifier'] .= '_global';
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    $form['expose']['placeholder'] = [
      '#type' => 'textfield',
      '#default_value' => $this->options['expose']['placeholder'],
      '#title' => $this->t('Placeholder'),
      '#size' => 40,
      '#description' => $this->t('Hint text that appears inside the field when empty.'),
    ];
  }

  /**
   * Operators.
   *
   * This kind of construct makes it relatively easy for a child class
   * to add or remove functionality by overriding this function and
   * adding/removing items from this array.
   *
   * @return array
   *   The operators.
   */
  public function operators(): array {
    $operators = [
      '=' => [
        'title' => $this->t('Is equal to'),
        'short' => $this->t('='),
        'method' => 'opSimple',
        'values' => 1,
      ],
      '!=' => [
        'title' => $this->t('Is not equal to'),
        'short' => $this->t('!='),
        'method' => 'opSimple',
        'values' => 1,
      ],
      'contains' => [
        'title' => $this->t('Contains'),
        'short' => $this->t('contains'),
        'method' => 'opSimple',
        'values' => 1,
      ],
      'starts' => [
        'title' => $this->t('Starts with'),
        'short' => $this->t('begins'),
        'method' => 'opSimple',
        'values' => 1,
      ],
      'not_starts' => [
        'title' => $this->t('Does not start with'),
        'short' => $this->t('not_begins'),
        'method' => 'opSimple',
        'values' => 1,
      ],
      'ends' => [
        'title' => $this->t('Ends with'),
        'short' => $this->t('ends'),
        'method' => 'opSimple',
        'values' => 1,
      ],
      'not_ends' => [
        'title' => $this->t('Does not end with'),
        'short' => $this->t('not_ends'),
        'method' => 'opSimple',
        'values' => 1,
      ],
      'not' => [
        'title' => $this->t('Does not contain'),
        'short' => $this->t('!has'),
        'method' => 'opSimple',
        'values' => 1,
      ],
      'shorterthan' => [
        'title' => $this->t('Length is shorter than'),
        'short' => $this->t('shorter than'),
        'method' => 'opSimple',
        'values' => 1,
      ],
      'longerthan' => [
        'title' => $this->t('Length is longer than'),
        'short' => $this->t('longer than'),
        'method' => 'opSimple',
        'values' => 1,
      ],
      'regular_expression' => [
        'title' => $this->t('Regular expression'),
        'short' => $this->t('regex'),
        'method' => 'opSimple',
        'values' => 1,
      ],
    ];

    // If the definition allows for the empty operator, add it.
    if (!empty($this->definition['allow empty'])) {
      $operators += [
        'empty' => [
          'title' => $this->t('Is empty (NULL)'),
          'method' => 'opEmpty',
          'short' => $this->t('empty'),
          'values' => 0,
        ],
        'not empty' => [
          'title' => $this->t('Is not empty (NOT NULL)'),
          'method' => 'opEmpty',
          'short' => $this->t('not empty'),
          'values' => 0,
        ],
      ];
    }

    return $operators;
  }

  /**
   * {@inheritdoc}
   */
  public function operatorOptions($which = 'title'): array {
    $options = [];
    foreach ($this->operators() as $id => $info) {
      $options[$id] = $info[$which];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state): void {
    // We have to make some choices when creating this as an exposed
    // filter form. For example, if the operator is locked and thus
    // not rendered, we can't render dependencies; instead we only
    // render the form items we need.
    $which = 'all';
    $source = '';
    if (!empty($form['operator'])) {
      $source = ':input[name="options[operator]"]';
    }
    if ($exposed = $form_state->get('exposed')) {
      $identifier = $this->options['expose']['identifier'];

      if (empty($this->options['expose']['use_operator']) || empty($this->options['expose']['operator_id'])) {
        // Exposed and locked.
        $which = in_array($this->operator, $this->operatorValues(1)) ? 'value' : 'none';
      }
      else {
        $source = ':input[name="' . $this->options['expose']['operator_id'] . '"]';
      }
    }

    if ($which == 'all' || $which == 'value') {
      $form['value'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Value'),
        '#size' => 30,
        '#default_value' => $this->value,
      ];
      if (!empty($this->options['expose']['placeholder'])) {
        $form['value']['#attributes']['placeholder'] = $this->options['expose']['placeholder'];
      }
      $user_input = $form_state->getUserInput();
      if ($exposed && !isset($user_input[$identifier])) {
        $user_input[$identifier] = $this->value;
        $form_state->setUserInput($user_input);
      }

      if ($which == 'all') {
        // Setup #states for all operators with one value.
        foreach ($this->operatorValues(1) as $operator) {
          $form['value']['#states']['visible'][] = [
            $source => ['value' => $operator],
          ];
        }
      }
    }

    if (!isset($form['value'])) {
      // Ensure there is something in the 'value'.
      $form['value'] = [
        '#type' => 'value',
        '#value' => NULL,
      ];
    }
  }

  /**
   * Get the operator values.
   *
   * @param int $values
   *   The number of values.
   *
   * @return array
   *   The operator values.
   */
  protected function operatorValues(int $values = 1): array {
    $options = [];
    foreach ($this->operators() as $id => $info) {
      if (isset($info['values']) && $info['values'] == $values) {
        $options[] = $id;
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildOptionsForm($form, $form_state);
    $form = $this->buildKeyOptionElement($form);
    // Make it multi-value to allow selection of multiple columns.
    $form['key']['#multiple'] = TRUE;
    $form['key']['#size'] = 10;
    $form['key']['#description'] = $this->t('Select multiple columns to search across. The filter will match if the value is found in ANY of the selected columns.');
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    $this->ensureMyTable();
    $selected_columns = $this->options['key'];

    // Ensure we have an array of columns.
    if (!is_array($selected_columns)) {
      $selected_columns = [$selected_columns];
    }

    // Filter out empty values.
    $selected_columns = array_filter($selected_columns);

    if (empty($selected_columns)) {
      return;
    }

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      if (is_array($this->value)) {
        $this->value = $this->value[0];
      }
      $this->{$info[$this->operator]['method']}($selected_columns);
    }
  }

  /**
   * Adds WHERE clauses for simple operations across multiple columns.
   *
   * Uses OR logic: if the value matches ANY column, include the row.
   *
   * @param array $column_list
   *   The array of column names.
   */
  public function opSimple(array $column_list): void {
    // Add conditions with OR logic - if the value matches ANY column, include the row.
    $or_group = $this->query->setWhereGroup('OR');
    foreach ($column_list as $column_name) {
      $this->query->addWhere($or_group, $column_name, $this->value, $this->operator);
    }
  }

  /**
   * Adds WHERE clauses for empty operations across multiple columns.
   *
   * @param array $column_list
   *   The array of column names.
   */
  protected function opEmpty(array $column_list): void {
    $operator = $this->operator == 'empty' ? 'empty' : 'not empty';
    $or_group = $this->query->setWhereGroup('OR');
    foreach ($column_list as $column_name) {
      $this->query->addWhere($or_group, $column_name, NULL, $operator);
    }
  }

}
