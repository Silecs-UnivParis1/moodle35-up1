$(document).ready(function() {
    $('div.item-select').on("click", ".element", function(event) {
        var intsel = $(this).prevAll('span.collapse').attr('data_rofid');
        var select = $("#items-selected1").children('div[class=item-selected]');
        var intitule = select.children('div[class=intitule-selected]').text();

        var roffirst = select.children('input:first').val();
        if (intsel == roffirst) {
            var comp = $(this).prevAll('span.comp').text();
            $('#id_complement').val(comp);
        }

        $('#fullname').val(intitule);
        $('#fullnamelab').empty();
        $('#fullnamelab').text(intitule + ' - ');

        var myIntitule = $('#id_myurl').val();
        if (myIntitule == '') {
            var url = '';
            var codeComp = '';
            var comp =  $('#select-2').children('option[selected=selected]');
            if (comp) {
                codeComp = comp.attr('data-rofid');
                url = codeComp+'-';
            }
            var dip =  $('#select-3').children('option[selected=selected]');
            var titleDip = dip.attr('title');
            var i = titleDip.indexOf('type:', 0);
            if (i != -1) {
                var j =  Number(i) + Number(5);
                titleDip = titleDip.substring(j);
                var k = titleDip.indexOf(',', 0);
                var codeDip = titleDip.substring(0, k);
                url = url + codeDip + '-';
            }

            myintitule = intitule.toLowerCase();
            myintitule = myintitule.replace(/[àäâá]/g,"a");
            myintitule = myintitule.replace(/[èéêë]/g,"e");
            myintitule = myintitule.replace(/[ìíîï]/g,"i");
            myintitule = myintitule.replace(/[òóôõö]/g,"o");
            myintitule = myintitule.replace(/[ùúûü]/g,"u");
            myintitule = myintitule.replace(/[ýÿ]/g,"y");
            myintitule = myintitule.replace(/œ/g,"oe");
            myintitule = myintitule.replace(/ç/g,"c");
            myintitule = myintitule.replace(/ /g, '-');
            myintitule = myintitule.replace(/[()'&#"]/g,"");
            $('#id_myurl').val(url + myintitule);
        }
    });

    $("#items-selected").on("click", ".selected-remove", function(event) {
        var select = $("#items-selected1").children('div[class=item-selected]');
        var intitule = select.children('div[class=intitule-selected]').text();
        if (intitule == '') {
            $('#id_complement').val(intitule);
            var isoldmyurl = $("#id_oldmyurl").length;
            if (isoldmyurl == 0) {
                $('#id_myurl').val('');
            }
        }
        $('#fullname').val(intitule);
        $('#fullnamelab').empty();
        $('#fullnamelab').text(intitule);
    });

    $('#mform1').submit(function(event){
        var ret = true;
        var select = $("#items-selected1").children('div[class=item-selected]');
        if (select.length==0) {
            ret = false;
            var textm = 'Vous devez sélectionner un élément pédagogique comme rattachement de référence de votre espace de cours avant de passer à l\'étape suivante.';
            $('#mgerrorrof').empty();
            $('#mgerrorrof').append('<div class="felement fselect error"><span class="error">'+textm+'</span></div>');
            event.preventDefault();
            $('html,body').animate({scrollTop: $('#mgerrorrof').offset().top}, 'slow');
        }
        return ret;
    });
});
