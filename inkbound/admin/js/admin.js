(function ($) {
	$(function () {
		var $story = $("#inkbound_story_id");
		if ($story.length && !$("#title").val()) {
			var num = $("#inkbound_number").val();
			if (num) {
				$("#title").attr("placeholder", "Chapter " + num + " title");
			}
		}
	});
})(jQuery);
