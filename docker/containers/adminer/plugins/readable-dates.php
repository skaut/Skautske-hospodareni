<?php

declare(strict_types=1);

/** This plugin replaces UNIX timestamps with human-readable dates in your local format.
 * Mouse click on the date field reveals timestamp back.
 *
 * @see https://www.adminer.org/plugins/#use
 */
class AdminerReadableDates
{
    public $prepend;

    public function __construct()
    {
        $this->prepend = <<<EOT

document.addEventListener('DOMContentLoaded', function(event) {
	var date = new Date();
	var tds = document.querySelectorAll('td[id^="val"]');
	for (var i = 0; i < tds.length; i++) {
		var text = tds[i].innerHTML.trim();
		if (text.match(/^\d{10}$/) || text.match(/^\d{13}$/)) {
			date.setTime(parseInt(text) * (text.length === 10 ? 1000 : 1));
			tds[i].oldDate = text;

			// tds[i].newDate = date.toUTCString().substr(5); // UTC format
			tds[i].newDate = date.toLocaleString();	// Local format
			// tds[i].newDate = date.toLocaleFormat('%e %b %Y %H:%M:%S'); // Custom format - works in Firefox only

			tds[i].newDate = '<span style="color: #009900">' + tds[i].newDate + '</span>';
			tds[i].innerHTML = tds[i].newDate;
			tds[i].dateIsNew = true;

			tds[i].addEventListener('click', function(event) {
				this.innerHTML = (this.dateIsNew ? this.oldDate : this.newDate);
				this.dateIsNew = !this.dateIsNew;
			});
		}
	}
});

EOT;
    }

    public function head(): void
    {
        echo script($this->prepend);
    }
}
