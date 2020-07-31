<?php
// $Id$

define('EVENT_MANAGER_BLOCK_PATH', drupal_get_path('module', 'event_manager_block'));
include_once(EVENT_MANAGER_BLOCK_PATH .'/event_manager_block.theme');

/**
 * Implementation of hook_help().
 */
function event_manager_block_help($section) {
  switch ($section) {
    case 'admin/help#event_manager_block' :
      $output = "Displays a list of all open volunteer slots for events.";
      return $output;
  }
}

/**
 * Permissions hook
 */
function event_manager_block_perm() {
  return array(
    'access event_manager_block',
    'administer event_manager_block'
  );
}

/*
 * Callback set up in the menu hook
 */
function event_manager_block_settings() {

  $form['event_manager_block_display_events_count'] = array(
    '#type' => 'textfield',
    '#title' => t('Number of events to show in block'),
    '#default_value' => variable_get('event_manager_block_display_events_count', '20'),
    '#maxlength' => 2,
    '#size' => 2,
    '#description' => t('The number of events to show in the events block.'),
  );
  $form['event_manager_block_help_needed_text'] = array(
    '#type' => 'textfield',
    '#title' => t('Text displayed when registration open'),
    '#default_value' => variable_get('event_manager_block_help_needed_text', t('(VOLUNTEERS NEEDED)')),
    '#maxlength' => 20,
    '#size' => 20,
    '#description' => t('The text to display when registration is open.'),
  );
  $form['event_manager_block_show_calendar_link'] = array(
    '#type' => 'radios',
    '#title' => t('Show calendar link at top of block'),
    '#default_value' =>  variable_get('event_manager_block_show_calendar_link', true),
    '#options' => array(true => t('Yes'), false => t('No')),
    '#description' => t('Whether or not to display the "Calendar" link at the top of the block.')
  );
  $form['event_manager_block_show_openings_link'] = array(
    '#type' => 'radios',
    '#title' => t('Show volunteer openings link at top of block'),
    '#default_value' =>  variable_get('event_manager_block_show_openings_link', true),
    '#options' => array(true => t('Yes'), false => t('No')),
    '#description' => t('Whether or not to display the "Volunteer Openings" link at the top of the block.')
  );
  $form['event_manager_block_show_ical_link'] = array(
    '#type' => 'radios',
    '#title' => t('Show ical link at top of block'),
    '#default_value' =>  variable_get('event_manager_block_show_ical_link', true),
    '#options' => array(true => t('Yes'), false => t('No')),
    '#description' => t('Whether or not to display the "ical" link at the top of the block.')
  );
  $form['event_manager_block_openings_page_title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title of the openings page'),
    '#default_value' => variable_get('event_manager_block_openings_page_title', t('Open Volunteer Opportunities')),
    '#maxlength' => 30,
    '#size' => 30,
    '#description' => t('The title of the page which displays all openings.'),
  );
  $form['event_manager_block_openings_page_none'] = array(
    '#type' => 'textfield',
    '#title' => t('String to display on the all openings page when no openings'),
    '#default_value' => variable_get('event_manager_block_openings_page_none', t('No upcoming openings available')),
    '#maxlength' => 40,
    '#size' => 40,
    '#description' => t('This string is displayed when there are no future openings.'),
  );

  return system_settings_form($form);
}

/*
 * Menu hook to set up the admin page
 */
function event_manager_block_menu() {
  global $user;

  $items = array();
  $items[] = array('path' => 'admin/settings/event_manager/block',
    'title' => t('Event Block'),
    'callback' => 'drupal_get_form',
    'callback arguments' => array('event_manager_block_settings'),
    'description' => t('Change how many events are displayed in the events block.'),
    'access' => user_access('administer site configuration'));
  $page_title = variable_get('event_manager_block_openings_page_title', t('Open Volunteer Opportunities'));
  $items[] = array('path' => 'all_openings',
    'title' => t($page_title),
    'callback' => 'all_openings_page',
    'access' => user_access('access content'),
    'type' => MENU_CALLBACK);

  return $items;
}

/**
 * Generate HTML for block (pretty much borrowed from the event module - just added
 * code to figure out if we have open slots)
 */
function event_manager_block_block($op = 'list', $delta = 0) {
  if ($op == "list") {
    $block[0]["info"] = t('Upcoming Events');
    return $block;
  }
  else if ($op == "view") {
    global $user;

    $time = time();
    $limit = variable_get('event_manager_block_display_events_count', '20');

    // Lookup events currently taking place and upcoming events, up to $limit events.
    $result = db_query(db_rewrite_sql("SELECT n.nid, n.title, n.type, n.status, n.changed, e.event_start, e.event_end FROM {node} n INNER JOIN {event} e ON n.nid = e.nid WHERE n.status = 1 AND (((e.event_start <> e.event_end) AND (%d >= e.event_start AND %d < e.event_end)) OR ((e.event_start = e.event_end) AND (%d <= e.event_start + %d )) OR e.event_start >= %d) ORDER BY event_start LIMIT %d"), $time, $time, $time, (60 * 60 * 2), $time, $limit);
    while ($node = db_fetch_object($result)) {
      // Call the event_edit_upcoming hook in all modules. Note that modules can
      // prevent display of a node by setting its status to 0 here.
      foreach (module_implements('event_edit_upcoming') as $module) {
        $function = $module .'_event_edit_upcoming';
        $function ($node);
      }

      if ($node->status) {
        if ($node->event_start >= $time) {
          $minutesleft = floor(($node->event_start - $time) / 60);
          if ($minutesleft >= 0 && $minutesleft < 60) {
            $timeleft = format_plural($minutesleft, '1 minute', '@count minutes');
          }
          else if ($minutesleft >= 60 && $minutesleft < (24 * 60)) {
            $timeleft = format_plural(floor($minutesleft / 60), '1 hour', '@count hours');
          }
          else if ($minutesleft >= (24 * 60)) {
            $days = floor($minutesleft / (24 * 60));
  
            $hours = ($minutesleft % (24 * 60)) / 60;
            /// koliko ur je še ostalo v dnevu do dogodka
            $hours_left = 24 - date('G', time());
            /**
             * pregled koliko ur je še ostalo
             */
            if ($hours > $hours_left) {
              $days++;
            }
            $timeleft = format_plural($days, '1 dan', '@count days');
          }
        }
        else {
          $timeleft = t('Zdaj');
        }

        $node->timeleft = $timeleft;
        $node->typename = node_get_types('name', $node);

        $settings = event_manager_get_event_registration_settings($node->nid);

        if (event_manager_is_registration_open($node->nid, $settings) && event_manager_got_availability($node->nid, $settings)) {
          $output = theme('event_manager_block_open_item', $node,
            variable_get('event_manager_block_help_needed_text', t('(VOLUNTEERS NEEDED)')));
        }
        else {
          $output = theme('event_manager_block_item', $node);
        }
        $items[] = $output;
      }
    }

    if (!count($items)) {
      $items[] = t('No upcoming events available');
    }

    $right_link = l(t("Calendar"), "event", array('title' => t("Kolendar")));
    $right_link_visible = variable_get('event_manager_block_show_calendar_link', true);


    $output = theme('event_manager_block_links',
      $left_link, $left_link_visible, $middle_link,
      $middle_link_visible,
      $right_link, $right_link_visible);

    $output .= theme('event_upcoming_block', $items);

    $block['subject'] = 'Open Volunteer Slots';
    $block['content'] = $output;

    return $block;
  }
}

function all_openings_page() {
  global $user;

  $time = time();

  // Lookup events currently taking place and upcoming events
  $result = db_query(db_rewrite_sql("SELECT n.nid, n.title, n.type, n.status, n.changed, e.event_start, e.event_end FROM {node} n INNER JOIN {event} e ON n.nid = e.nid WHERE n.status = 1 AND (((e.event_start <> e.event_end) AND (%d >= e.event_start AND %d < e.event_end)) OR ((e.event_start = e.event_end) AND (%d <= e.event_start + %d )) OR e.event_start >= %d) ORDER BY event_start"), $time, $time, $time, (60 * 60 * 2), $time);
  while ($node = db_fetch_object($result)) {
    // Call the event_edit_upcoming hook in all modules. Note that modules can
    // prevent display of a node by setting its status to 0 here.
    foreach (module_implements('event_edit_upcoming') as $module) {
      $function = $module .'_event_edit_upcoming';
      $function ($node);
    }

    if ($node->status) {
      $settings = event_manager_get_event_registration_settings($node->nid);
      if (event_manager_is_registration_open($node->nid, $settings) && event_manager_got_availability($node->nid, $settings)) {
        if ($node->event_start >= $time) {
          $minutesleft = floor(($node->event_start - $time) / 60);
          if ($minutesleft >= 0 && $minutesleft < 60) {
            $timeleft = format_plural($minutesleft, '1 minuta', '@count minutes');
          }
          else if ($minutesleft >= 60 && $minutesleft < (24 * 60)) {
            $timeleft = format_plural(floor($minutesleft / 60), '1 ura', '@count hours');
          }
          else if ($minutesleft >= (24 * 60)) {
            $days = floor($minutesleft / (24 * 60));
            // preostanek ur
            $hours = ($minutesleft % (24 * 60)) / 60;
            // preostanek ur do konca dneva
            $hours_left = 24 - date('G', time());
            /**
             * preveri, ali je preostanek ur na datum dogodka večji od preostalih ur danes,
             * če je tako, povečajte dneve za eno, tako da preostali dnevi posnemajo
             * datum, in ne dodajo število ur nad 24.
             */
            if ($hours > $hours_left) {
              $days++;
            }
            $timeleft = format_plural($days, '1 dan', '@count days');
          }
        }
        else {
          $timeleft = t('Now');
        }
        if ($timeleft <= 0) {
            echo 'Dogodek je že potekel';
        } 

        $node->timeleft = $timeleft;
        $node->typename = node_get_types('name', $node);

        $row = array();

        $row[] = $node->timeleft;
        $row[] = l($node->title, "node/$node->nid", array('title' => $node->title));
        $row[] = "&nbsp;&nbsp;&gt;&gt;". l(t("REGISTER"), "node/$node->nid/eventregistration", array('title' => t("REGISTER"))) ."&lt;&lt;&nbsp;&nbsp;";

        $items[] = $row;
      }
    }
  }

  if (!count($items)) {
    $items[] = t(variable_get('event_manager_block_openings_page_none', t('No upcoming openings available')));
  }

  $header = array();
  $header[] = t("Time Until Event");
  $header[] = t("Event Name");
  $header[] = t("");

  $attr = array();
  $attr[] = 'border="0"';

  $output = "<br/>". theme("table", $header, $items, $attr) ."<br/>";

  return $output;
}
