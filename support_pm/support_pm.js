/**
 * Throw warning if user navigates away from page without saving timer
 * information
 */
jQuery(document).ready(function() {
  if (Drupal.settings.suppot_pm.unload_warning) {
    jQuery('#support-plan-user-week').bind("change", function() { confirm_unload(true); });
    jQuery("#edit-submit").click(function() { window.confirm_unload(false); });
  }
});

/**
 * Determine whether or not we should display a message when a user navigates
 * away from the current page.
 */
function confirm_unload(on) {
  window.onbeforeunload = (on) ? unload_message : null;
}

/**
 * The message we display when a user navigates away from a changed plan
 * without saving his/her changes.
 */
function unload_message() {
  return Drupal.t("Any plan details you have entered for this week will be lost if you navigate away from this page without pressing the 'Save plan' button.");
}
