$(document).ready(function () {
    $row = $("#form-category-value").parent().parent();
    $row.hide();
    $("#form-select-parentId").change(function () {
        if ($("#form-select-parentId").val() === "0") {
            $row.hide();
            $("#form-category-value").val("");
        } else {
            $row.show();
        }
    });

    //pri zmenene typu se skryje částka
    $("#form-select-type").change(function () {
        $row.hide();
    });
});