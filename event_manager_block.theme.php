<?php
// $Id$

/**
 * @file
 * Theming functions for Event Manager Block
 */

/**
 *  Generate themed block item (handles fixing up adding or not adding the | separator btw links)
 *
 *  @param node
 *     node for line
 *  @return
 *     themed line content
 *
 *  @ingroup themeable
 */
function theme_event_manager_block_links($left_link, $ll_visible, $middle_link, $ml_visible, $right_link, $rl_visible) {
  $output  = "";
  if ($ll_visible == true){
    $output .= '<small><span style="text-align: left;">'. $left_link ."</span></small>";
    if ($ml_visible == true){
      // Sredinjska poravnava
      $output .= '|<small><span style="text-align: center;">' . $middle_link . "</span></small>";
      if ($rl_visible == true){
        // Desna poravnaa
        $output .= '|<small><span style="text-align: right;">' . $right_link . "</span></small>";
      }
    }
    else {
      // Brez sredinsjke
      if ($rl_visible == true){
        $output .= '|<small><span style="text-align: right;">' . $right_link . "</span></small><br /><br />";
      }
    }
    $output .= "<br /><br />";
  }
  else {
    // Brez leve
    if ($ml_visible == true){
      // Srednijsak vidba
      $output .= '<small><span style="text-align: center;">' . $middle_link . "</span></small>";
      if ($rl_visible == true){
        // Desna vidna
        $output .= '|<small><span style="text-align: right;">' . $right_link . "</span></small>";
      }
      $output .= "<br /><br />";
    }
    else {
      // Brez sredinjske
      if ($rl_visible == true){
        $output .= '<small><span style="text-align: right;">' . $right_link . "</span></small><br /><br />";
      }
    }
  }
  return $output;
}

/**
 *  Generiranje teme bloka
 *
 *  @param node
 
 *  @return
 *     contnet teme
 *
 */
function theme_event_manager_block_item($node) {
  $output = l($node->title, "node/$node->nid", array('title' => $node->title));
  $output .= '<span class="event-timeleft">('. $node->timeleft .')</span>';
  return $output;
}

/**
 *  Generiranje teme ko se odpre event
 *
 *  @param node
 *     node for line
 *  @param help_text
 *     help text for line
 *  @return
 *     themed line content
 *
 *  @ingroup themeable
 */
function theme_event_manager_block_open_item($node, $help_text) {
  $output = l($node->title, "node/$node->nid", array('title' => $node->title));
  $output .= '<br /><strong><span style="color: red;">' . $help_text . '</span></strong><span class="event-timeleft">('. $node->timeleft .')</span>';
  return $output;
}

