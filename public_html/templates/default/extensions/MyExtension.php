<?php
class MyExtension extends ViewPhpExtension
{

    public function getObjects($ids, $sortby=false) {
        $res = array();

        $s = new selector('objects');
        $s->where('id')->equals($ids);
        if($sortby) {
            $s->order('name')->asc();
        }
        return $s->result();
    }

    public function getPages($ids) {
        $res = array();

        $s = new selector('pages');
        $s->where('id')->equals($ids);

        return $s->result();
    }



    public function getObjectsByType($id) {
        $res = array();

        $s = new selector('objects');
        $s->types('object-type')->id($id);

        return $s->result();
    }

    public function getProperty($object, $prop_name) {
        foreach($object['extended']['properties'] as $prop) {
            if($prop->getName() == $prop_name) {
                return $prop->getValue();
            }
        }
    }

    public function getPropertyObj($object, $prop_name) {
        foreach($object['extended']['properties'] as $prop) {
            if($prop->getName() == $prop_name) {
                return $prop;
            }
        }
    }

    public function getLangConst()
    {
        $cmsController = cmsController::getInstance();

        $fileI18N = $cmsController->getTemplatesDirectory() . 'i18n/i18n.' . $cmsController->getCurrentLang()->getPrefix() . '.php';
        if (file_exists($fileI18N)) {
                return json_encode(require $fileI18N);
        }
        
        return json_encode(array());
    }

    public function getSubpages($id, $level=3) {
        $s = new selector('pages');
        $s->types('hierarchy-type')->name('catalog', 'object');
        $s->where('hierarchy')->page($id)->childs($level);
        $s->where('lang')->equals(false);
        return $s->result();
    }

    public function getPagePropByName($page, $property) {
        $obj = $page->getObject();
        return $obj->getPropByName($property);
    }

    public function getPage($id) {
        return umiHierarchy::getInstance()->getElement($id);
    }

    public function getSettings($id, $name="") {
        $settings = cmsController::getInstance()->getModule('umiSettings');
        $settings_id = $settings->getIdByCustomId($id);
        $settings_obj = umiObjectsCollection::getInstance()->getObject($settings_id);

        if($name) {
            return $settings_obj->getValue($name);
        } else {
            return $settings_obj;
        }
    }

    public function countMenuLevel($item) {
        $level = 1;
        
        if(array_key_exists('sub_menu', $item)) {
            
            $next_level = 0;

            foreach ($item['sub_menu']['item'] as $submenu_item) {
                 
                 $next_level_tmp = $this->countMenuLevel($submenu_item);

                 $next_level = ($next_level_tmp > $next_level) ? $next_level_tmp : $next_level;

             } 

             $level += $next_level;

        }

        return $level;
    }

    public function getObject($id) {
        return umiObjectsCollection::getInstance()->getObject($id);
    }

    public function getUser() {
        $pc = permissionsCollection::getInstance();
        $user_id = $pc->getUserId();
        $user = umiObjectsCollection::getInstance()->getObject($user_id);

        return $user;
    }

    public function getUserGroups() {
        $pc = permissionsCollection::getInstance();
        $user_id = $pc->getUserId();
        $user = umiObjectsCollection::getInstance()->getObject($user_id);

        return $user->groups;
    }

    public function drawClasses($classes) {
        if(sizeof($classes)) {
            return 'class="'.implode(' ', $classes).'"';
        } else {
            return '';
        }
    }

}

?>