$(document).ready(function() {

    if ($("#id_autovalidation").prop('checked')) {
        $('input.user-selector').attr("disabled","disabled");
    }
    $("#id_autovalidation").click(
        function() {
            if ($(this).prop('checked')) {
                var select = $("div.users-selected").children('div[class=teacher-item-block]');
                if (select.length != 0) {
                    var res = confirm("Se désigner comme responsable de l'enseignement supprime le validateur que vous avez désigné."
                        + "\n Voulez-vous effectuer cette opération ?");
                    if (res == true) {
                        $("div.users-selected").empty();
                        $('input.user-selector').attr("disabled","disabled");
                    } else {
                        $("#id_autovalidation").removeAttr("checked")
                    }

                } else {
                    $('input.user-selector').attr("disabled","disabled");
                }
            } else {
                $('input.user-selector').removeAttr("disabled");
            }
    });
});
