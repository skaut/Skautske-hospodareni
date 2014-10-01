/*!
 * @author Jakub Skrzeczek
 * @author Daniel Robenek
 * @license MIT
 */

(function($, undefined) {

$.nette.ext('dependentselectbox', {
	load: function() {
		this.hideSubmits();
		$('.' + this.controlClass).off('change', this.sendSelectBox).on('change', this.sendSelectBox);
	},
	success: function (payload) {
		if (payload["type"] && payload["type"] === "JsonDependentSelectBoxResponse") {
			var items = payload["items"];
			for (var i in items) {
				this.updateSelectBox(i, items[i]["selected"], items[i]["items"]);
			}
		}
	}
}, {
	hideSubmits: function() {
		// Here hide all you want. Default is to hide <tr> of button
		$('.' + this.controlClass + this.buttonSuffix).parent().parent().hide();
	},
	sendSelectBox: function(e) {
		var buttonSuffix = $.nette.ext('dependentselectbox').buttonSuffix;
		$('#' + (e.target.id) + buttonSuffix).netteAjax(e);
	},
	updateSelectBox: function(id, selectedKey, items) {
		$("#" + id + " option").remove();
		var select = $("#" + id);
		for (var i in items) {
			var item = $("<option></option>").attr("value", i).html(items[i]);
			if (i === selectedKey)
				item.attr("selected", "selected");
			if (i === "")
				select.prepend(item);
			else
				select.append(item);
		}
	},
	controlClass: 'dependentControl',
	buttonSuffix: '_submit'
});

})(jQuery);
