$(document).ready(function() { $("#select-state").change(function() { change_substatus($("#select-state").val()); }).change(); });

function change_substatus($state) {

  // State has changed, update substatus.
  var $ss = $("#select-substatus");
  // Capture current state so we don't lose track of it on page load.
  var curvalue = $ss.val();
  $ss.empty();

  // @todo: if no substatus for this state, remove from form
  $.each(Drupal.settings.substatus[$state], function(key, value) {
    $ss.append($("<option></option>").attr("value", key).text(value));
  });
  // Preserve current state if applicable.
  $ss.find("option[value='" + curvalue + "']").attr('selected', 'selected');
}