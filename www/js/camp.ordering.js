function changeYear(val) {
    $.nette.ajax({url: "\/tabory\/?do=changeYear" + "&year=" + val});
}
function changeState(val) {
    $.nette.ajax({url: "\/tabory\/?do=changeState" + "&state=" + val});
}