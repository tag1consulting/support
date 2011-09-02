/**
 * @file
 * Behaviors for support_reference.
 * Based on code from api.module.
 */
(function($) {
Drupal.behaviors.supportReferenceAutoComplete = {
  attach: function (context) {
    $('#support-reference-block-form:not(.supportReferenceAutoCompleteProcessed)', context).addClass('supportReferenceAutoCompleteProcessed').each(function () {
      // On the first focus.
      $('#edit-reference', this).attr('autocomplete', 'off').one('focus', function () {
        var $this = $(this);
        // Fetch the ticket list.
        $.getJSON(Drupal.settings.supportReferenceAutoCompletePath, function (data) {
          // Attach to autocomplete.
          $this.autocomplete({
            source: data,
            matchContains: true,
            max: 200,
            scroll: true,
            scrollHeight: 360,
            width: 300
          }).result(function () {
            $this.get(0).form.submit();
          }).focus();
        });
      });
    });
  }
};
})(jQuery);