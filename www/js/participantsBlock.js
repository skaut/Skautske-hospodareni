$(document).ready(function () {

    $(document).keypress(
            function (event) {
                if (event.which === '13') {
                    event.preventDefault();
                }
            });

    //zaškrtávání checkboxu v editačním formuláři
    $("input[name^=edit]").change(function () {
        var position = this.name.length - 1;
        var checkBoxName = [this.name.slice(0, 4), '\\', this.name.slice(4, position), 'c\\', this.name.slice(position)].join('');
        $("input[name=" + checkBoxName + "]").attr('checked', true);
    });

    //(de)aktivace tlačítka pro hromadné akce nad poteciálními účastníky
    $(".dependentButtonList").addClass("disabled").attr("disabled", "disabled");
    onlyWithCheckbox("massList", "dependentButtonList");

    //(de)aktivace tlačítka pro hromadné akce nad seznamem účastníků
    $(".dependentButtonParticipant").addClass("disabled").attr("disabled", "disabled");
    onlyWithCheckbox("massParticipants", "dependentButtonParticipant");


    //mění pozici řádku pro oba směry
    function addRow(rowId, targetBlockId) {
        $ne = $('#' + rowId);
        $.nette.ajax($ne.find("a.addRow").attr("href"));
        //$.post($ne.find("a.addRow").attr("href"), $.nette.success);

        $topAlertBoxText = $("#topAlertBox ul");
        var rand = Math.floor((Math.random() * 10000) + 1);
        if (targetBlockId === "participants-list-tbody") {
            newName = $ne.text().trim();
            $topAlertBoxText.append('<li id="newParticipant-' + rand + '"><span>Účastník ' + newName + ' byl přidán');
        } else {
            newName = $('#' + rowId + ' td:first').text().trim();
            $topAlertBoxText.append('<li id="newParticipant-' + rand + '"><span>Účastník ' + newName + ' byl odebrán');
            if (participantsList.size() === 1) {
                $(".noparticipants").show();
                $(".onlyWithParticipants").hide();
            }
        }

        setTimeout(function () {
            $('#newParticipant-' + rand + '').hide('blind', 400);
        }, 4000);

        $ne.hide(0, function () {
            if (targetBlockId === "participants-list-tbody") {
                $ne = $('<tr><td>' + newName + '</td><td><img src="/images/layout/spinner.gif"/></td><td><img src="/images/layout/spinner.gif"/></td><td></td></tr>');
                participantsList.push(newName);
                participantsList.sort();
                var index = jQuery.inArray(newName, participantsList);

                if (index === 0) {
                    $($('#' + targetBlockId + ' tr').get(index)).before($ne);
                } else {
                    $($('#' + targetBlockId + ' tr').get(index - 1)).after($ne);
                }
                $(".noparticipants").hide();
                $ne.hide().show(500);
                $(".onlyWithParticipants").show(500);

                $unitPersons = $("#unit-participants-tbody tr:not(.no-unit-persons)");
//                console.log($unitPersons.size());
                if ($unitPersons.size() === 1) { //byl posledni zobrazeny
                    $(".onlyWithUnitPersons").hide();
                    $(".no-unit-persons").show();
                }
            }
        });

        return false;
    }

    objects = document.querySelectorAll('a.addRow');
    [].forEach.call(objects, function (col) {
        col.addEventListener('click', aAddRow, false);
    });

    function aAddRow(e) {
        e.preventDefault();
        $this = $(this);
        var rowId = $this.parents("tr").attr("id");
        var myBlockId = $this.parents("tbody.dropable").attr("id");
        if (myBlockId === "participants-list-tbody") {
            targetBlockId = "unit-participants-tbody";
        } else {
            targetBlockId = "participants-list-tbody";
        }
//         console.log(rowId);
//         console.log(targetBlockId);
        return addRow(rowId, targetBlockId);
    }

    function getDropableId(o) {
        $obj = $(o);
        if ($obj.hasClass(".dropable")) {
            return $obj.id;
        }
        parents = $obj.parents(".dropable");
        return (parents.length > 0) ? parents.get(0).id : null;
    }
});