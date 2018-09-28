<?php

/**
 * @file
 * Theme functions for the islandora_solr_metadata module.
 */

use Drupal\islandora_solr\IslandoraSolrQueryProcessor;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Link;
use Drupal\Component\Utility\Unicode;

/**
 * Prepares variables for islandora_solr_metadata_display templates.
 *
 * @param array $variables
 *   An associative array containing:
 *   - islandora_object: The AbstractObject for which we are generating a
 *     metadata display.
 *   - print: A boolean indicating to disable some functionality, to facilitate
 *     printing. When TRUE, avoids adding the "collapsible" and "collapsed"
 *     classes to fieldsets.
 */
function template_preprocess_islandora_solr_metadata_display(array &$variables) {
  module_load_include('inc', 'islandora_solr_metadata', 'includes/db');
  module_load_include('inc', 'islandora', 'includes/utilities');
  $variables['#attached']['library'][] = 'islandora_solr_metadata/display';
  $variables['#attached']['library'][] = 'islandora_solr/toggle';

  $object = $variables['islandora_object'];
  $db_fields = [];
  $solr_fields =& $variables['solr_fields'];
  $associations = $variables['associations'];

  foreach ($associations as $configuration_id) {
    $field = islandora_solr_metadata_get_fields($configuration_id['configuration_id']);
    $db_fields = array_merge($db_fields, $field);
  }
  foreach ($db_fields as $solr_field => $value) {
    if (isset($solr_fields[$solr_field])) {
      continue;
    }
    // Make an array for use later on.
    $solr_fields[$solr_field] = $value + [
      'value' => [],
    ];
  }
  $separator = \Drupal::config('islandora_solr_metadata.settings')->get('islandora_solr_metadata_field_value_separator');

  if (empty($separator)) {
    $separator = "\n";
  }
  $variables['separator'] = $separator;
  $variables['parent_collections'] = islandora_get_parents_from_rels_ext($object);

  $variables['islandora_solr_metadata_omit_empty_values'] = \Drupal::config('islandora_solr_metadata.settings')->get('islandora_solr_metadata_omit_empty_values');

  $variables['found'] = islandora_solr_metadata_query_fields($variables['islandora_object'], $variables['solr_fields']);
  $variables['not_found_message'] = t('Document not present in Solr while generating the metadata display for @id. Try @reloading the page, or contact an administrator if this issue persists.', [
    '@id' => $variables['islandora_object']->id,
    '@reloading' => '<a href="" onclick="window.location.reload(true)">' . t('reloading') . '</a>',
  ]);

  foreach ($variables['solr_fields'] as &$solr_field) {
    $solr_field['value_filtered'] = check_markup($solr_field['value'][0], 'islandora_solr_metadata_filtered_html');
  }
}

/**
 * Prepares variables for islandora_solr_metadata_description templates.
 *
 * @param array $variables
 *   An associative array containing:
 *   - islandora_object: The AbstractObject for which we are generating a
 *     metadata display.
 */
function template_preprocess_islandora_solr_metadata_description(array &$variables) {
  $solr_fields =& $variables['solr_fields'];
  $associations = $variables['associations'];
  $variables['#attached']['library'][] = 'islandora_solr/toggle';

  $descriptions = [];
  module_load_include('inc', 'islandora_solr_metadata', 'includes/db');
  foreach ($associations as $configuration_id) {
    $description = islandora_solr_metadata_retrieve_description($configuration_id['configuration_id']);
    if ($description['description_field'] !== NULL) {
      $descriptions[] = $description;
    }
  }
  foreach ($descriptions as $description) {
    $desc_field = $description['description_field'];
    $solr_fields[$desc_field] = [
      'display_label' => $description['description_label'],
      'value' => [],
      'description_data' => $description['description_data'],
    ];
  }
  $separator = \Drupal::config('islandora_solr_metadata.settings')->get('islandora_solr_metadata_field_value_separator');

  if (empty($separator)) {
    $separator = "\n";
  }
  $variables['separator'] = $separator;

  $variables['found'] = islandora_solr_metadata_query_fields($variables['islandora_object'], $variables['solr_fields']);

  // XXX: Seems a little odd, yes... Was reusing the "description" value,
  // though.
  $variables['description'] = $variables['solr_fields'];
  $separator = \Drupal::config('islandora_solr_metadata.settings')->get('islandora_solr_metadata_field_value_separator');

  if (empty($separator)) {
    $separator = "\n";
  }
  // Truncate the description value(s).
  foreach ($variables['description'] as &$field) {
    if (isset($field['description_data']['truncation'])) {
      if ($field['description_data']['truncation']['max_length'] > 0) {
        module_load_include('inc', 'islandora_solr', 'includes/utilities');
        $field['value'] = array_filter($field['value']);
        if ($field['description_data']['truncation']['truncation_type'] == 'whole_field_option') {
          $updated_value[] = islandora_solr_truncate_field_display(
            $field['value'],
            $field['description_data']['truncation']['max_length'],
            $field['description_data']['truncation']['ellipsis'],
            $field['description_data']['truncation']['word_safe'],
            $field['description_data']['truncation']['min_wordsafe_length'],
            $separator
          );
          $field['value'] = $updated_value;
        }
        else {
          foreach ($field['value'] as $index => $value) {
            $field['value'][$index] = islandora_solr_truncate_field_display(
              (array) $value,
              $field['description_data']['truncation']['max_length'],
              $field['description_data']['truncation']['ellipsis'],
              $field['description_data']['truncation']['word_safe'],
              $field['description_data']['truncation']['min_wordsafe_length'],
              " "
            );
          }
        }
      }
    }
  }
  foreach ($variables['description'] as &$solr_field) {
    $solr_field['value_filtered'] = check_markup($solr_field['value'][0], 'islandora_solr_metadata_filtered_html');
  }
}

/**
 * Perform the query to Solr to get our metadata values.
 *
 * @param AbstractObject $object
 *   The object for which we are going to query Solr... Really only need the
 *   PID...
 * @param array $solr_fields
 *   An associative array mapping Solr fields to associative arrays, of which
 *   we use:
 *   - hyperlink: An optional boolean indicating if the given value should be
 *     rendered a link.
 *   - weight: An optional weight we will use to sort the values.
 *   - formatter: An optional callable to which we will call with each value
 *     from the given field, to return alternate text or markup as desired.
 *
 * @return bool
 *   TRUE if we have found a document for the object in question; otherwise,
 *   FALSE.
 */
function islandora_solr_metadata_query_fields(AbstractObject $object, array &$solr_fields) {
  module_load_include('inc', 'islandora_solr', 'includes/utilities');
  if (!empty($solr_fields)) {
    $user = \Drupal::currentUser();

    $query_processor = new IslandoraSolrQueryProcessor();
    $query_processor->solrQuery = 'PID:"' . $object->id . '"';
    $query_processor->solrParams['fl'] = '*';
    $query_processor->executeQuery(FALSE);
    if ($query_processor->islandoraSolrResult['response']['numFound'] > 0) {
      $solr_results_doc = $query_processor->islandoraSolrResult['response']['objects']['0']['solr_doc'];

      // Ensure we only provide results for what is specified.
      $constrained_results = array_intersect_key($solr_results_doc, $solr_fields);

      foreach ($constrained_results as $solr_field => $value) {
        // XXX. Need to pass $solr_fields by reference here even though it's not
        // modified to get around a bug in PHP 5.3.3 when used on CentOS 6.6,
        // otherwise the calling function doesn't maintain a reference to the
        // original value and it becomes a copy.
        $get_display_value = function ($original_value) use ($solr_field, &$solr_fields) {
          $value = $original_value;
          $field_config = $solr_fields[$solr_field] + [
            'hyperlink' => 0,
            'formatter' => NULL,
            'uri_replacement' => '',
            'date_format' => '',
          ];
          // Replace URI or PID with a configured field.
          if (!empty($field_config['uri_replacement'])) {
            module_load_include('inc', 'islandora', 'includes/utilities');
            $pid = str_replace('info:fedora/', '', $value);
            if (islandora_is_valid_pid($pid)) {
              $relation_qp = new IslandoraSolrQueryProcessor();
              $relation_qp->solrQuery = 'PID:"' . $pid . '"';
              $relation_qp->solrParams['fl'] = "PID,{$field_config['uri_replacement']}";
              $relation_qp->solrLimit = 1;
              $relation_qp->executeQuery(FALSE);
              if ($relation_qp->islandoraSolrResult['response']['numFound'] > 0) {
                $relation_doc = $relation_qp->islandoraSolrResult['response']['objects'][0]['solr_doc'];
                if (isset($relation_doc[$field_config['uri_replacement']])) {
                  $value = $relation_doc[$field_config['uri_replacement']];
                }
              }
            }
          }
          if (($truncation = NestedArray::getValue($field_config, ['truncation'])) && $truncation['max_length'] > 0 && (!isset($truncation['truncation_type']) || $truncation['truncation_type'] == 'separate_value_option')) {
            $value = Unicode::truncate($value, $truncation['max_length'], $truncation['word_safe'], $truncation['ellipsis'], $truncation['min_wordsafe_length']);
            if ($value !== $original_value) {
              // XXX: implode()'ing to avoid newlines which get converted to
              // <br/>'s in the later check_markup.
              return implode('', [
                '<span class="toggle-wrapper">',
                t("<span>!value <a href='#' class='toggler'>Show more</a></span>", [
                  '!value' => $value,
                ]),
                t("<span>!original_value <a href='#' class='toggler'>Show less</a></span>", [
                  '!original_value' => $original_value,
                ]),
                '</span>',
              ]);
            }
          }
          if (islandora_solr_is_date_field($solr_field) && !empty($solr_fields[$solr_field]['date_format'])) {
            $value = format_date(strtotime($value), 'custom', $solr_fields[$solr_field]['date_format'], 'UTC');
          }
          if (is_callable($field_config['formatter'])) {
            $value = call_user_func($field_config['formatter'], $value);
          }
          if ($field_config['hyperlink'] == 1 && trim($value)) {
            if (\Drupal::config('islandora_solr_metadata.settings')->get('islandora_solr_metadata_omit_empty_values') && !trim($value)) {
              // Avoid generating markup (throwing off the "empty" check).
              // Will get stripped out later.
              return FALSE;
            }
            $solr_query = format_string('!field:!value', [
              '!field' => $solr_field,
              '!value' => islandora_solr_facet_escape($original_value),
            ]);
            return Link::createFromRoute($value, 'islandora_solr.islandora_solr', [
              "query" => [
                "type" => "dismax",
                "f" => [
                  $solr_query,
                ],
              ],
            ]);
          }
          return $value;
        };

        // Want the values in array form even if it's a single valued field.
        if (!is_array($value)) {
          $value = (array) $value;
        }

        $solr_fields[$solr_field]['value'] = array_map($get_display_value, $value);
      }

      if (\Drupal::config('islandora_solr_metadata.settings')->get('islandora_solr_metadata_omit_empty_values')) {
        $filter_function = function ($field_info) {
          // Remove all empty field values to determine if field is empty.
          $filtered_values = array_filter($field_info['value']);
          return !empty($filtered_values);
        };
        $filtered = array_filter($solr_fields, $filter_function);
        $solr_fields = $filtered;
      }

      if (\Drupal::config('islandora_solr_metadata.settings')->get('islandora_solr_metadata_dedup_values')) {
        foreach ($solr_fields as &$field) {
          $field['value'] = array_unique($field['value']);
        }
        unset($field);
      }

      // After values are omitted/dedupped filter down the displayed results.
      foreach ($solr_fields as &$field) {
        // Check if the field is configured to filter based on whole field.
        if (($truncation = NestedArray::getValue($field, ['truncation'])) && $truncation['max_length'] > 0 && isset($truncation['truncation_type']) && $truncation['truncation_type'] == 'whole_field_option') {
          // Keep the updated value stored in an array.
          $updated_value[] = islandora_solr_truncate_field_display($field['value'], $truncation['max_length'], $truncation['ellipsis'], $truncation['word_safe'], $truncation['min_wordsafe_length'], \Drupal::config('islandora_solr_metadata.settings')->get('islandora_solr_metadata_field_value_separator'));
          // Replace value with updated field display.
          $field['value'] = $updated_value;
        }

        // Apply rules configurations for this field as configured
        // on the metadata configuration page. Unset the field from
        // the $solr_fields array to prevent it from being shown.
        $permission = NestedArray::getValue($field, ['permissions']);
        if ($permission['enable_permissions'] && !array_intersect(array_keys($user->roles), $permission['permissions'])) {
          // Permissions are being enforced and none of our roles are allowed...
          unset($solr_fields[$field['solr_field']]);
        }
      }

      uasort($solr_fields, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
      return TRUE;
    }
    else {
      return FALSE;
    }
  }
}
