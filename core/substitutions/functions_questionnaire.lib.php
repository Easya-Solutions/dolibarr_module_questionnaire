<?php

/**
 * @param array         $substitutionarray
 * @param Translate     $outputlangs
 * @param Questionnaire $object
 * @param array         $parameters
 * @return mixed
 */
function questionnaire_completesubstitutionarray(&$substitutionarray, $outputlangs, $object, $parameters) {
    global $conf;
    $outputlangs->load('questionnaire@questionnaire');

//    if(!empty($conf->global->QUESTIONNAIRE_CUSTOM_DOMAIN)) {
//        $link = $conf->global->QUESTIONNAIRE_CUSTOM_DOMAIN.'toAnswer.php?id=' . $object->id . '&action=answer&fk_invitation=' . $invuser->id . '&token=' . $invuser->token;
//    }
//    else {
//        $link = dol_buildpath('/questionnaire/public/toAnswer.php?id=' . $object->id . '&action=answer&fk_invitation=' . $invuser->id . '&token=' . $invuser->token, 2);
//    }
//    $substitutionarray['__QUESTIONNAIRE_LINK__'] = $link;

    return $substitutionarray;
}