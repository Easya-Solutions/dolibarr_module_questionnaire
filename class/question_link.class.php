<?php

if (!class_exists('TObjetStd'))
{
    /**
     * Needed if $form->showLinkedObjectBlock() is call
     */
    define('INC_FROM_DOLIBARR', true);
    require_once dirname(__FILE__).'/../config.php';
}

class Questionlink extends SeedObject {
    
    public $table_element = 'quest_question_link';
    
    public $element = 'question_link';
    
    public function __construct(&$db)
    {
        global $conf, $langs;
        
        $this->db = $db;
        
        $this->fields=array(
            'fk_questionnaire'=>array('type'=>'integer','index'=>true)
            ,'fk_question'=>array('type'=>'integer','index'=>true)
            ,'fk_choix'=>array('type'=>'integer','index'=>true)
        );
        
        $this->init();
        
        $this->entity = $conf->entity;
    }
    
    function loadLinks($fk_questionnaire)
    {
        $sql = "SELECT t.fk_question, t.fk_choix FROM " . MAIN_DB_PREFIX . $this->table_element ." as t WHERE t.fk_questionnaire = ". $fk_questionnaire;
        
        $res = $this->db->query($sql);
        $ret = array();
        
        if($res){
            if($this->db->num_rows($res))
            {
                while ($obj = $this->db->fetch_object($res))
                {
                    $ret[$obj->fk_question] = $obj->fk_choix;
                }
            }
        }
        
        return $ret;
    }
    
    public function save() {
        
        global $user;
        
        return $this->id>0 ? $this->updateCommon($user) : $this->createCommon($user);
        
    }
    
    function loadLink($fk_question = 0, $fk_choix = 0)
    {
        //if (empty($fk_question) && empty($fk_choix) return -1
        $sql = "SELECT t.rowid, t.fk_questionnaire, t.fk_question, t.fk_choix, q.label";
        $sql.= " FROM " .MAIN_DB_PREFIX.$this->table_element. " as t";
        $sql.= " LEFT JOIN " .MAIN_DB_PREFIX. "quest_question as q ON q.rowid = t.fk_question";
        $sql.= " WHERE 1 = 1";
        if(!empty($fk_question)) $sql.= " AND fk_question=" . $fk_question;
        if(!empty($fk_choix)) $sql.= " AND fk_choix=" . $fk_choix;
        
        $res = $this->db->query($sql);
        if ($res)
        {
            if ($this->db->num_rows($res))
            {
                $obj = $this->db->fetch_object($res);
                $this->id = $obj->rowid;
                $this->fk_questionnaire = $obj->fk_questionnaire;
                $this->fk_question = $obj->fk_question;
                $this->fk_choix = $obj->fk_choix;
                $this->question_label = $obj->label;
                
                return $this->id;
            } else{ // pas de lien trouvé
                return 0;
            }
            
        } else { // probleme de requête
            $this->errors[] = $this->db->lasterror;
            return -1;
        }
        
    }
    
    public function delete(User &$user, $notrigger = false)
    {
        return parent::deleteCommon($user, $notrigger);
    }
}
