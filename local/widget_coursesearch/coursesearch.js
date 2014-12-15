/*
 * @license http://www.gnu.org/licenses/gpl-2.0.html  GNU GPL v2
 */
(function() {

    var rootUrl = findScriptUrl('coursesearch.js');

    if (window.jQuery === undefined) {
        loadJs(rootUrl + "../jquery/jquery.js", onLoadFinished);
        loadJs(rootUrl + "../jquery/jquery-ui.js", false);
        loadJs(rootUrl + "../jquery/jquery.dataTables.min.js", false);
    } else {
        onLoadFinished();
        if (window.jQuery.fn.accordion === undefined) {
            if (window.jQuery.fn.dataTables === undefined) {
                loadJs(rootUrl + "../jquery/jquery-ui.js", false);
                loadJs(rootUrl + "../jquery/jquery.dataTables.min.js", false);
            } else {
                loadJs(rootUrl + "../jquery/jquery-ui.js", false);
            }
        } else if (window.jQuery.fn.dataTables === undefined) {
            loadJs(rootUrl + "../jquery/jquery.dataTables.min.js", false);
        }
    }

    {
        var linkTag = document.createElement('link');
        linkTag.setAttribute("type","text/css");
        linkTag.setAttribute("rel","stylesheet");
        linkTag.setAttribute("href", rootUrl + '../jquery/css/jquery.dataTables.css');
        (document.getElementsByTagName("head")[0] || document.documentElement).appendChild(linkTag);
    }

    function findScriptUrl(name) {
        var scripts = document.getElementsByTagName('script');
        for (var i=0; i<scripts.length; i++) {
            if (scripts[i].src.indexOf('/'+name) !== -1) {
                return scripts[i].src.replace('/'+name, '/');
            }
        }
        return false;
    }

    function loadJs(url, last) {
        var script_tag = document.createElement('script');
        script_tag.setAttribute("type","text/javascript");
        script_tag.setAttribute("src", url);
        (document.getElementsByTagName("head")[0] || document.documentElement).appendChild(script_tag);
        if (typeof last !== 'undefined' && last) {
            if (script_tag.readyState) {
                script_tag.onreadystatechange = function () {
                    if (this.readyState === 'complete' || this.readyState === 'loaded') {
                        last.call();
                    }
                };
            } else {
                script_tag.onload = last;
            }
        } else {
            if (script_tag.readyState) { // IE < 9, bug on async script loading
                script_tag.onreadystatechange = function () {
                    if (this.readyState === 'complete' || this.readyState === 'loaded') {
                        script_tag.onreadystatechange = null; // bug IE8
                    }
                };
            }
        }
    }

    function onLoadFinished() {
        $.fn.coursesearch = function (params) {
            $(this.selector).each(function() {
                var $elem = $(this);
                $elem.load(rootUrl + 'ajax.php', params, function() {
					if (typeof M.form.dependencyManagers === 'undefined') {
						loadJs(rootUrl + '../../lib/javascript.php/0/lib/form/form.js');
					}
					$('.collapsed').removeClass('collapsed');
				});
                $elem.on('submit', 'form', buildSearchMoodleCourses($elem));
            });
            return this;
        };
        function buildSearchMoodleCourses($elem) {
            return function (event) {
                var data = $(this).closest('form').serialize();
                $.ajax({
                    'url': rootUrl + 'ajax.php',
                    'type': 'GET',
                    'data': data
                }).done(function(html) {
                    $elem.html(html).find('table').dataTable({ "oLanguage": {
                        "sProcessing":     "Traitement en cours...",
                        "sSearch":         "Rechercher&nbsp;:",
                        "sLengthMenu":     "Afficher _MENU_ &eacute;l&eacute;ments",
                        "sInfo":           "Affichage de l'&eacute;lement _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
                        "sInfoEmpty":      "Affichage de l'&eacute;lement 0 &agrave; 0 sur 0 &eacute;l&eacute;ments",
                        "sInfoFiltered":   "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
                        "sInfoPostFix":    "",
                        "sLoadingRecords": "Chargement en cours...",
                        "sZeroRecords":    "Aucun &eacute;l&eacute;ment &agrave; afficher",
                        "sEmptyTable":     "Aucune donnée disponible dans le tableau",
                        "oPaginate": {
                            "sFirst":      "Premier",
                            "sPrevious":   "Pr&eacute;c&eacute;dent",
                            "sNext":       "Suivant",
                            "sLast":       "Dernier"
                        },
                        "oAria": {
                            "sSortAscending":  ": activer pour trier la colonne par ordre croissant",
                            "sSortDescending": ": activer pour trier la colonne par ordre décroissant"
                        }
                    }
                    });
                });
                return false;
            };
        }
    }

})();
