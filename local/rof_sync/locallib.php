<?php
/**
 * @package    local
 * @subpackage rof_sync
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$rofUrl = 'http://formation.univ-paris1.fr/cdm/services/cataManager?wsdl' ;

/**
 * clean all five rof_ tables : component, constant, program, course, person
 */
function rofCleanAll() {
    global $DB;

    $DB->delete_records('rof_constant');
    $DB->delete_records('rof_component');
    $DB->delete_records('rof_program');
    $DB->delete_records('rof_course');
    $DB->delete_records('rof_person');
}

function rofGlobalSync($verb=0, $dryrun=false) {

    rofCleanAll();

    progressBar($verb, 1, "Constants... ");
    $constants = fetchConstants();
    $countdiag = setConstants($constants);
    progressBar($verb, 1, countDisplay($countdiag) . "\n");

    progressBar($verb, 1, "\nComponents... ");
    $countdiag = setComponents();
    progressBar($verb, 1, countDisplay($countdiag) . "\n");

    progressBar($verb, 1, "\nPrograms... \n");
    // echo fetchPrograms($verb, $dryrun);
    processPrograms($verb, $dryrun);


die();

    progressBar($verb, 1, "\nCourses... \n");
    echo fetchCourses($verb, $dryrun);

    progressBar($verb, 1, "\nCourse parents... \n");
    setCourseParents($verb, $dryrun);
}


/**
 * fetch "constantes" from SOAP webservice
 *
 * @return object (xml node) $constants
 */
function fetchConstants() {
    $reqParams = array(
        '_cmd' => 'getAllFormations',
        '_lang' => 'fr-FR',
        '__composante' => '00', // fausse composante, pour obtenir uniquement les constantes
        '__1' => '__composante',  // incompréhensible mais nécessaire
    );
    $xmlResp = doSoapRequest($reqParams);
    $xmlTree = new SimpleXMLElement($xmlResp);
    $constants = $xmlTree->properties->infoBlock->extension->uniform->constantes;
    return $constants;
}

/**
 * simple webservice test based on fetchConstants()
 *
 * @return $bool
 */
function rofTestWebservice() {
    $xmlTree = fetchConstants();
    $n = $xmlTree->count();
    if ($n > 0) {
        echo "L'arbre des constantes est lisible et a $n fils. OK.\n\n";
        return true;
    } else {
        echo "L'arbre des constantes n'a pas de fils. Erreur probable.\n\n";
        return false;
    }
}

/**
 * insert "constantes" into table rof_constant
 *
 * @param object (xml node) $constants
 * @return array $count  array('ok' => int, 'err' => int);
 */
function setConstants($constants) {
    global $DB;
    $count = array('ok' => 0, 'err' => 0);

    // step 1 : element domaineDiplome
    foreach ($constants->domaineDiplome as $dd) {
        $elt='domaineDiplome';
        $elttype = (string)$dd->attributes()->type;
        foreach ($dd->data as $singledata) {
            $record = defineConstant($elt, $elttype, $singledata);
            $lastinsertid = $DB->insert_record('rof_constant', $record);
            ( $lastinsertid ? $count['ok']++ : $count['err']++ );
        }
    }
    // step 2 : other elements
    foreach ($constants->children() as $element) {
        $elt = (string)$element->getName();
        if ($elt == 'domaineDiplome') continue;
        foreach ($element->data as $singledata) {
            $record = defineConstant($elt, '', $singledata);  //** @todo Vérifier si elttype est toujours vide
            $lastinsertid = $DB->insert_record('rof_constant', $record);
            ( $lastinsertid ? $count['ok']++ : $count['err']++ );
        }
    }
    return $count;
}

/**
 * define a "Constante" record from an XML node and metadata
 * @param string $element
 * @param string $elementtype
 * @param xml object $singledata
 * @return \stdClass record to be inserted
 */
function defineConstant($element, $elementtype, $singledata) {
    $record = new stdClass();
    $record->element = $element;
    $record->elementtype = $elementtype;
    $record->dataid = (string)$singledata->attributes()->id;
    $record->dataimport = (string)$singledata->attributes()->import;
    $record->dataoai = (string)$singledata->attributes()->oai;
    $record->value = (string)$singledata->value;
    $record->timesync = time();
    return $record;
}


/**
 * fetch "composantes" from table comstants and insert them into table rof_component
 * @return array $count  array('ok' => int, 'err' => int);
 */
function setComponents() {
    global $DB;
    $components = $DB->get_records('rof_constant', array('element' => 'composante'));
    $count = array('ok' => 0, 'err' => 0);

    foreach ($components as $component) {
        $record = new stdClass();
        $record->rofid = ''; // to be completed later
        $record->import = $component->dataimport; // = dataid
        $record->oai = $component->dataoai;
        $record->name = $component->value;
        $record->number = $component->dataid; // = dataimport
        $record->sub = ''; // to be completed later
        $record->subnb = 0;
        $record->timesync = time();
        $lastinsertid = $DB->insert_record('rof_component', $record);
        ( $lastinsertid ? $count['ok']++ : $count['err']++ );
    }
    return $count;
}


/**
 * return SOAP request parameters to fetch a "composante"
 * @param string $compNumber '01' to '37'
 * @return array
 */
function getProgramsRequest($compNumber) {
    $reqParams = array(
        '_cmd' => 'getAllFormations',
        '_lang' => 'fr-FR',
        '__composante' => $compNumber,
        '__1' => '__composante',  // incompréhensible mais nécessaire
    );
    return $reqParams;
}


function fetchProgramsByComponent($verb=0, $compNumber) {
        $subComp = array(); // liste des programmes fils
        $records = array(); //liste des objets program et subprogram à retourner

        $reqParams = getProgramsRequest($compNumber);
        $xmlResp = doSoapRequest($reqParams);
        progressBar($verb, 3, "\n$compNumber size=". strlen($xmlResp) ."\n");
        $xmlTree = new SimpleXMLElement($xmlResp);
        $cnt = array('prog' => 0, 'subp' => 0);

        foreach ($xmlTree->children() as $element) { //only program elements should be better
            $elt = (string)$element->getName();
            if ($elt != 'program') continue;
            $ProgRofid = (string)$element->programID;
            $subComp[] = $ProgRofid;

            $subProgs[$ProgRofid] = array();
            $program = new stdClass();
            $program->compnumber = ''; // potentiellement plusieurs composantes mères
            $program->rofid = $ProgRofid;
            $program->name  = (string)$element->programName->text;
            $program->level = 1;
            $program->oneparent = $compNumber;
            $program->timesync = time();
            $program->sub = '';
            $program->courses = '';
            $program->parents = '';
            $program->refperson = '';
            // dans la boucle : typedip, domainedip, naturedip, cycledip, rythmedip, languedip
            foreach($element->programCode as $code) {
                $codeset = (string)$code->attributes();
                $val = (string)$code[0];
                if (preg_match('/Diplome$/', $codeset) && ! strrchr($codeset, '.')) { // ni oai. ni uniform.
                    $field = str_replace('Diplome', 'dip', $codeset);
                    $program->$field = $val;
                }
            }
            // on récupère acronyme, mention, specialite sous /CDM/program/infoBlock/extension/cdmUP1/
            if ( ! empty($element->infoBlock->extension->cdmUP1) ) {
                $program->acronyme = (string)$element->infoBlock->extension->cdmUP1->acronyme;
                $program->mention = (string)$element->infoBlock->extension->cdmUP1->mention;
                $program->specialite = (string)$element->infoBlock->extension->cdmUP1->specialite;
            }
            $cnt['prog']++;

            // insert subprograms
            foreach($element->subProgram as $subp) {
                $subprogram = clone $program;
                $subprogram->rofid = (string)$subp->programID;
                $subProgs[$ProgRofid][] = $program->rofid;
                $subprogram->name  = (string)$subp->programName->text;
                $subprogram->level = 2;
                $subprogram->oneparent = $ProgRofid;
                $subprogram->timesync = time();
                $records[] = $subprogram;
                $cnt['subp']++;
            } // foreach subprogram

            // update program to store subprograms
            $program->sub = serializeArray($subProgs[$ProgRofid]);
            $program->subnb = count($subProgs[$ProgRofid]);
            $records[] = $program;
        } //foreach ($element)

        progressBar($verb, 1, "$compNumber ");
        progressBar($verb, 2, ": " . $cnt['prog'] .'+'. $cnt['subp'] . ' ');
        return array($subComp, $records);
}


function insertProgramsByComponent($compNumber, $subComp, $records) {
    global $DB;

    $dbcomp= $DB->get_record('rof_component', array('number' => $compNumber));
    $dbcomp->sub = serializeArray($subComp);
    $dbcomp->subnb = count($subComp);
    $DB->update_record('rof_component', $dbcomp);

    foreach ($records as $record) {
        $DB->insert_record('rof_program', $record); //TODO OR UPDATE
    }
}


function processPrograms($verb=0, $dryrun=false) {

    $compNumber = '01';
    list($subComp, $records) = fetchProgramsByComponent($verb, $compNumber);
    insertProgramsByComponent($compNumber, $subComp, $records);

    return 0;
}


/**
 * fetch "programs" and "subPrograms" from webservice and insert them into table rof_program
 * @param integer $verb verbosity
 * @param bool $dryrun : if set, no modification to database
 * @return number of inserted rows
 */
function fetchPrograms($verb=0, $dryrun=false) {
global $DB;
    $total = 0;

    $components = $DB->get_records_menu('rof_component', array(), '', 'id, number');
    foreach ($components as $id => $compNumber) {

    } //foreach($components)


    progressBar($verb, 1, "\n relations composantes <-> programmes\n");
    foreach ($components as $id => $compNumber) {

        // programme -> composantes
        foreach ($subComp[$compNumber] as $prog) {
            $parentProg[$prog][] = $compNumber;
        }
    }
    
    foreach ($parentProg as $prog => $parents) {
        progressBar($verb, 1, ".");
        $dbprog= $DB->get_record('rof_program', array('rofid' => $prog));
        $dbprog->parents = serializeArray($parents);
        $dbprog->components = $dbprog->parents;
        $dbprog->parentsnb = count($parents);
        if (! $dryrun ) {
            $DB->update_record('rof_program', $dbprog);
        }
    }

    progressBar($verb, 1, "\n relations programmes <-> sous-programmes\n");
    // relations programmes <-> sous-programmes
    foreach ($subProgs as $prog => $listSubs) {
        progressBar($verb, 1, '.');
        foreach ($listSubs as $subprog) {
            $parentSubProg[$subprog][] = $prog;
        }
    }
    foreach ($parentSubProg as $subprog => $listParents) {
        progressBar($verb, 1, '*');
        $dbprog= $DB->get_record('rof_program', array('rofid' => $subprog));
        $dbprog->parents = serializeArray($listParents);
        $dbprog->parentsnb = count($listParents);
        if (! $dryrun ) {
            $DB->update_record('rof_program', $dbprog);
        }
    }

    return $total;
}


/**
 * fetch "courses" from webservice and insert them into table rof_course
 * @param integer $verb verbosity
 * @param bool $dryrun : if set, no modification to database
 * @return number of inserted rows
 */
function fetchCourses($verb=0, $dryrun=false) {
global $DB;
    $total = 0;
    $dbltotal = 0;
    $cnt = 1;

    $programs = $DB->get_records_menu('rof_program', array('level' => 1), '', 'id, rofid');
    foreach ($programs as $id => $progRofId) {
        progressBar($verb, 1, '.');
        progressBar($verb, 2, $cnt. "  id=". $id ."  p=". $progRofId ."->");
        $count = fetchCoursesByProgram($progRofId, $verb, $dryrun);
        $total += $count[0];
        $dbltotal += $count[1];
        progressBar($verb, 2, " cnt=". $count[0] . "   dbl=".$count[1]);
        $cnt++;
    }
    progressBar($verb, 1, "\nCourses : total=$total  doublons=$dbltotal \n");
    return $total;
}

/**
 * fetch "courses" from webservice and insert them into table rof_course
 * limited to a Program
 * @param string $progRofId (ex. UP1-PROG35376) = rof_program.rofid
 * @param integer $verb verbosity
 * @param bool $dryrun : if set, no modification to database
 * @return array(number of inserted rows, number of prevented doublets)
 */
function fetchCoursesByProgram($progRofId, $verb=0, $dryrun=false) {
global $DB;

    $reqParams = array(
        '_cmd' => 'getFormation',
        '_lang' => 'fr-FR',
        '_oid' => $progRofId,
    );
    $xmlResp = doSoapRequest($reqParams);
    $xmlTree = new SimpleXMLElement($xmlResp);
    $cnt = 0;
    $dblcnt = 0; // compte les doublons évités

    // references subProgram -> courses level 1 (UE)
    $program = $xmlTree->program;
    $progCourses = fetchRefCourses($program); //fetch courses under Program
    updateRefCourses($progRofId, $progCourses, $dryrun);

    foreach($program->subProgram as $subp) {
        $subpRofId = (string)$subp->programID;

        $subProgCourses = fetchRefCourses($subp); //fetch courses under subprograms
        updateRefCourses($subpRofId, $subProgCourses, $dryrun);
        if ( ! empty($subp->contacts) ) {
            $listRefPersons = fetchRefPersons($subp->contacts) ;
            updateRefPersons('rof_program', $subpRofId, $listRefPersons, $dryrun);
        }
    }

    if ( ! empty($program->contacts) ) {
        $listRefPersons = fetchRefPersons($program->contacts) ;
        updateRefPersons('rof_program', $progRofId, $listRefPersons, $dryrun);
    }

    // then, browse all courses
    foreach ($xmlTree->children() as $element) {
        if ( (string)$element->getName() != 'course') continue;
        if ($DB->record_exists('rof_course', array('rofid' => (string)$element->courseID))) {
            $dblcnt++;
            continue;
        }
        $record = new stdClass();
        $record->rofid = (string)$element->courseID;
        $record->name  = (string)$element->courseName->text;
        $record->code = (string)$element->courseCode;
        $record->level = 0; // on ne sait pas encore
        $record->oneparent = $progRofId;
        $record->timesync = time();
        $record->parents = '';
        $record->sub = '';
        $record->refperson = '';
        $subsCourse[$record->rofid] = array();
        // on récupère les infos sous course/infoBlock/extension/cdmUP1/ (seulement composition ?)
        if ( ! empty($element->infoBlock->extension->cdmUP1) ) {
            $record->composition = (string)$element->infoBlock->extension->cdmUP1->composition;
        }

        if (! $dryrun ) {
            $lastinsertid = $DB->insert_record('rof_course', $record);
            if ( $lastinsertid) {
                $cnt++;
            }
        }
        // print_r($record);
        $desc = $element->courseDescription;

        if ( ! empty($element->contacts) ) {
            $listRefPersons = fetchRefPersons($element->contacts) ;
            updateRefPersons('rof_course', $record->rofid, $listRefPersons, $dryrun);
        }

        //** @todo réécrire la suite en DOM ?
        foreach ($element->courseDescription->children() as $subBlock) {
            if ( (string)$subBlock->getName() != 'subBlock') continue;
            $attrs = $subBlock->attributes();
            if ( (string)$attrs['userDefined'] != 'courseStructure' ) continue ;

            if ($subBlock->count() > 0) {
                $refBlock = $subBlock->subBlock;
                // print_r($refBlock); die();
                foreach ($refBlock->children() as $refCourse) {
                    if ( (string)$refCourse->getName() != 'refCourse') continue;
                    $attrRC = $refCourse->attributes();
                    $subsCourse[$record->rofid][] = (string)$attrRC['ref'];
                    // echo $attrRC['ref']." "; die();
                }
            }
        } // fin réécrire en DOM ?
    }

    // update courses with subcourses (sub)
    if ( isset($subsCourse) ) {
        foreach($subsCourse as $course => $subcourses) {
            $dbcourse = $DB->get_record('rof_course', array('rofid' => $course));
            $dbcourse->sub = serializeArray($subcourses);
            $dbcourse->subnb = count($subcourses);
            if (! $dryrun ) {
                $DB->update_record('rof_course', $dbcourse);
            }
        }
    }

    // then, browse all persons
    foreach ($xmlTree->children() as $person) {
        if ( (string)$person->getName() != 'person') continue;
        if ($DB->record_exists('rof_person', array('rofid' => (string)$element->personID))) {
            continue;
        }
        $record = fetchPerson($person);
        if ( ! $DB->record_exists('rof_person', array('rofid' => $record->rofid)) ) {
            $DB->insert_record('rof_person', $record);
        }
    }

    return array($cnt, $dblcnt);
}

/**
 * browse the (sub)programs and courses "sub" to fill the courses "level", "parents" and "parentsnb"
 * @param integer $verb
 * @param boolean $dryrun
 */
function setCourseParents($verb, $dryrun=false) {
    global $DB;
    $cntlevel = array('1'=>0, '2'=>0, '3'=>0, '4'=>0, '5'=>0, '6'=>0, '7'=>0, '8'=>0, '9'=>0);

    $courses = $DB->get_records('rof_course', array('level'=>0)); // niveau indéterminé
    foreach ($courses as $course) {
        $parents[$course->rofid] = array();
    }

    // FIRST, children of programs AND subprograms
    $programs = $DB->get_records('rof_program'); // both programs and subprograms
    foreach ($programs as $program) {
        progressBar($verb, 1, '*');
        $childcourses = explode(',', $program->courses);
        if ( ! $childcourses ) continue ;
        foreach ($childcourses as $childcourse) {
            progressBar($verb, 2, '.');
            $parents[$childcourse][] = $program->rofid;
            $alevel[$childcourse] = 1;
        }
    }

    foreach ($courses as $course) {
        if ( count($parents[$course->rofid]) == 0) continue;
        $dbcourse = $DB->get_record('rof_course', array('rofid' => $course->rofid));
        $dbcourse->level = 1;
        $dbcourse->parents = serializeArray($parents[$course->rofid]);
        $dbcourse->parentsnb = count($parents[$course->rofid]);
        if ( ! $dryrun ) {
            $DB->update_record('rof_course', $dbcourse);
        }
        $cntlevel[1]++;
    }

    // THEN, children of other courses
    $level = 1; // parent level
    $maxlevel=10;
    do { // loop on levels
        $finished = true;
        progressBar($verb, 1, "\nlooping courses level $level.\n");
        $pcourses = $DB->get_records('rof_course', array('level' => $level));
        foreach ($pcourses as $pcourse) {
            progressBar($verb, 2, '*');
            $childcourses = explode(',', $pcourse->sub);
            if ( ! $childcourses ) continue ;
            $finished = false;
            foreach ($childcourses as $childcourse) {
                progressBar($verb, 3, '.');
                $parents[$childcourse][] = $pcourse->rofid;
                $alevel[$childcourse] = $level+1;
            }
        }
        $zcourses = $DB->get_records('rof_course', array('level' => 0));
        foreach ($zcourses as $zcourse) {
            if ( count($parents[$zcourse->rofid]) == 0) continue;
            $dbcourse = $DB->get_record('rof_course', array('rofid' => $zcourse->rofid));
            $dbcourse->level = $level+1;
            $dbcourse->parents = serializeArray($parents[$zcourse->rofid]);
            $dbcourse->parentsnb = count($parents[$zcourse->rofid]);
            if ( ! $dryrun ) {
                $DB->update_record('rof_course', $dbcourse);
            }
            $cntlevel[$level+1]++;
        }
        $level++;
    } while ( ! $finished and $level < $maxlevel); //loop on levels

    // FINALLY
    if ($verb >= 3) {
        foreach ($cntlevel as $level => $count)
        echo "$count courses level $level.\n";
    }
}


/**
 * fetch <refCourse>s from an xml program OR subProgram element
 * @param type $xmlNode
 * @return array( string refCourse )
 */
function fetchRefCourses($xmlNode) {
    $res = array();
    if ( ! empty($xmlNode->programStructure->subBlock->subBlock) ) {
        $content = $xmlNode->programStructure->subBlock->subBlock; //ELP
        foreach ($content->children() as $refCourse) {
            if ( (string)$refCourse->getName() != 'refCourse') continue;
            $attrs = $refCourse->attributes();
            $courseref = (string)$attrs['ref'];
            $res[] = $courseref;
        }
    }
    return $res;
}

/**
 * updateRefCourses for given program and list
 * @global type $DB
 * @param string $rofid
 * @param array(string) $listRefCourses
 * @param bool $dryrun : if set, no modification to database
 */
function updateRefCourses($rofid, $listRefCourses, $dryrun) {
    global $DB;
    $dbprogram = $DB->get_record('rof_program', array('rofid' => $rofid));
    if ($dbprogram) {
        $dbprogram->courses = serializeArray($listRefCourses);
        $dbprogram->coursesnb = count($listRefCourses);
        if (! $dryrun ) {
            $DB->update_record('rof_program', $dbprogram);
        }
    }
}

/**
 * fetch <refPerson>s from an xml <contacts> element
 * @param type XmlElement
 * @return array( string refPerson )
 */
function fetchRefPersons($xmlContacts) {
    $res = array();
    foreach ($xmlContacts->children() as $refPerson) {
        if ( (string)$refPerson->getName() != 'refPerson' ) continue;
        $attrs = $refPerson->attributes();
        $res[] = (string)$attrs['ref'];
    }
    return $res;
}

/**
 * updateRefPersons for given table and list
 * @global type $DB
 * @param string $table (rof_program or rof_course)
 * @param string $rofid
 * @param array(string) $listRefPersons
 * @param bool $dryrun : if set, no modification to database
 */
function updateRefPersons($table, $rofid, $listRefPersons, $dryrun) {
    global $DB;
    $dbrecord = $DB->get_record($table, array('rofid' => $rofid));
    if ( $dbrecord ) {
        $dbrecord->refperson = serializeArray($listRefPersons);
        if (! $dryrun ) {
            $DB->update_record($table, $dbrecord);
        }
    }
}


/**
 * fetch info from an xml <person> element
 * @param XmlElement $xmlPerson
 * @return object record
 */
function fetchPerson($xmlPerson) {
    $record = new stdClass();
    $record->rofid = trim((string)$xmlPerson->personID);
    $record->givenname = substr(trim((string)$xmlPerson->name->given), 0, 250);
    $record->familyname = substr(trim((string)$xmlPerson->name->family), 0, 250);
    $record->title = substr(trim((string)$xmlPerson->title->text), 0, 250);
    $record->role = substr(trim((string)$xmlPerson->role->text), 0, 250);
    $record->email = substr(trim((string)$xmlPerson->contactData->email), 0, 250);
    $record->oneparent = '';
    $record->timesync = time();
    return $record;
}

/**
 * returns a trivial serialization (csv) from an array
 * @param type $array (simple)
 * @return string serialized array
 */
function serializeArray($array) {
    sort($array, SORT_STRING);
    return join(',', $array);
}


/**
 * turns "logical" parameters into the form needed by the webservice
 * @param type $reqParams array of parameters
 * @return string XML response
 */
function doSoapRequest($reqParams) {
    global $rofUrl;

    $callParams = setCallParams($reqParams);
    $soapClient = new SoapClient($rofUrl, array('trace' => 1));

    try {
        $formResponse = $soapClient->getResponse($callParams, '1010');
        return $formResponse;
    } catch (SoapFault $soapFault) {
        echo "SoapFault : \n";
        echo $soapFault;
        file_put_contents("lastrequest.xml", $soapClient->__getLastRequest());
        echo "\nFin SoapFault\n\n" ;
        return false;
    }
}

/**
 * Turn "logical" $reqParams into "artificial" $callParams used to request
 * the CDM-fr web service
 * @param  array $reqParams
 * @return $callParams
 */
function setCallParams($reqParams) {
    $v = array();
    foreach($reqParams as $key=>$value) {
        $v[] = array($value);
    }
    $callParams = array(
        'names' => array_keys($reqParams),
        'values' => $v,
    );
    return $callParams;
}

/**
 * Display the WSDL auto-documented prototypes
 * @return void
 */
function displayWsdlInformation($url) {
    $soapClient = new SoapClient($url);

    echo "Functions: ";
    $functions = $soapClient->__getFunctions();
    print_r($functions);

    echo "\nTypes: ";
    $types = $soapClient->__getTypes();
    print_r($types);

    echo "\n\n**************\n\n";
}

/**
 * progress bar display
 * @param int $verb verbosity
 * @param int $verbmin minimal verbosity
 * @param string $strig to display
 */
function progressBar($verb, $verbmin, $string) {
    if ($verb >= $verbmin) {
        echo $string;
    }
}

/**
 * display a count diagnostic (specialt for DB inserts diagnostic)
 * @param array $count ex. ('OK' => 14, 'err' => 0)
 */
function countDisplay($count) {
    foreach ($count as $label => $nb) {
        echo " $label=$nb ";
    }
}
