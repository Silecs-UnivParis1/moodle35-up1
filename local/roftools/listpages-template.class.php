<?php

/**
 * @package    local
 * @subpackage roftools
 * @copyright  2012-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Return an array of templates.
 *
 * @return array of templates
 */
class ListpagesTemplates
{
// {format} = table | tree
    /** @var string tableau | arborescence */
    public $view;
    /** @var string link to page arbre if current page = tableau, and reverse */
    public $sisterpagelink;

    private $category;
    /** @var string component name ex. 02-Économie */
    private $compname;
    /** @var array 4th depth subcategories (Licence, Master, ...) */
    private $niveauxLmda;
    private static $tpl_name = 'Espaces de cours de {compname} ({vue})';
    private static $tpl_intro = <<<EOL
<p>
</p>
EOL;
    private static $tpl_contenttab = array(
        'tableau' => <<< EOL
<div class="tabtree">
    <ul class="tabrow0">
        <li class="first onerow here selected">
            <a class="nolink"><span>{vue}</span></a>
            <div class="tabrow1 empty"></div>
        </li>
        <li class="last onerow"><a href="{link-arborescence}">Vue arborescente</a></li>
    </ul>
</div>
EOL
        ,
        'arborescence' => <<< EOL
<div class="tabtree">
    <ul class="tabrow0">
        <li class="first onerow"><a href="{link-tableau}">Vue tableau</a></li>
        <li class="last onerow here selected">
            <a class="nolink"><span>{vue}</span></a>
            <div class="tabrow1 empty"></div>
        </li>
    </ul>
</div>
EOL
    );
    private static $tpl_contentmain = <<< EOL
<h3>Espaces de cours de {niveaulmda}</h3>
<div>
    [course{format} node={node}]
</div>
<p style="clear:both"></p>
EOL;
    private static $tpl_contentfoot = <<< EOL
<p style="font-size: x-small;">
        Les Espaces pédagogiques interactifs proposent des informations et des ressources pédagogiques en accompagnement des cours.
        Les enseignants les publient à l’intention des étudiants inscrits aux enseignements concernés pour guider leur travail personnel,
        approfondir certaines questions, préparer les travaux et devoirs ou encore réviser les examens.
</p>
<p style="font-size: x-small;">
        Les documents, quelle que soit leur nature, publiés dans les Espaces pédagogiques interactifs de l'Université Paris 1 Panthéon-Sorbonne,
        sont protégés par le <a title="Code de la propriété intellectuelle - Legifrance" href="http://www.legifrance.gouv.fr/affichCode.do?cidTexte=LEGITEXT000006069414">Code de la propriété intellectuelle</a> (Article L 111-1). Toute reproduction partielle ou totale sans autorisation écrite de l\'auteur est interdite, sauf celles prévues à l'article L 122-5 du <a title="Code de la propriété intellectuelle - Legifrance" href="http://www.legifrance.gouv.fr/affichCode.do?cidTexte=LEGITEXT000006069414">Code de la propriété intellectuelle</a>.
    <span style="font-size: small;">
        <a href="http://www.celog.fr/cpi/lv1_tt2.htm"><br /> </a>
    </span>
</p>
EOL;

    public function __construct($category) {
        $this->setCategory($category);
    }

    public function setCategory($category) {
        global $DB;
        $this->category = $category;
        $this->compname = $category->name;
        $this->niveauxLmda = $DB->get_records('course_categories', array('parent' => $category->id));
    }

    public function getName() {
        return str_replace(
                array('{compname}', '{vue}'),
                array($this->compname, $this->view['name']),
                self::$tpl_name
        );
    }

    public function getIntro() {
	global $CFG;
	$annee = get_config('local_roftools','rof_year_name');
	$html_annee = '<h4>'.$annee.'</h4>';
        return $html_annee . self::$tpl_intro;
    }

    public function getContent() {
        $content = str_replace('{vue}', $this->view['name'], self::$tpl_contenttab[$this->view['code']]);
        foreach ($this->niveauxLmda as $niveau) {
            $node = '/cat' . $niveau->id;
            $content .= str_replace(
                    array('{niveaulmda}', '{node}', '{format}'),
                    array($niveau->name, $node, $this->view['format']),
                    self::$tpl_contentmain
            );
        }
        $content .= self::$tpl_contentfoot;
        return $content;
    }
}
