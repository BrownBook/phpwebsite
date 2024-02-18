<?php
/**
 * Tab class
 *
 * @version $Id$
 * @author  Matt McNaney <mcnaney at gmail dot com>
 */

class PHPWS_Panel_Tab {
    public $id           = null;
    public $title        = null;
    public $link         = null;
    public $tab_order    = null;
    public $itemname     = null;
    public $link_title   = null;
    public $_secure      = true;

    // If strict == true, tab links are returned as is and not appended.
    public $_strict      = false;

    public function __construct($id=null)
    {
        if(isset($id)) {
            $this->setId($id);
            $this->init();
        }
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function init()
    {
        $DB = new PHPWS_DB('controlpanel_tab');
        $result = $DB->loadObject($this);
        if (PHPWS_Error::logIfError($result) || !$result) {
            $this->id = null;
        }
    }

    public function isStrict()
    {
        $this->_strict = true;
    }

    public function setTitle($title)
    {
        $this->title = strip_tags($title);
    }

    public function setLinkTitle($link_title)
    {
        $this->link_title = strip_tags($link_title);
    }

    public function getTitle($noBreak=true)
    {
        if ($noBreak) {
            return str_replace(' ', '&nbsp;', dgettext($this->itemname, $this->title));
        } else {
            return dgettext($this->itemname, $this->title);
        }
    }

    public function setLink($link)
    {
        $this->link = $link;
    }

    public function getLinkTitle()
    {
        if (!$this->link_title) {
            return null;
        } else {
            return " title=\"$this->link_title\"";
        }
    }

    public function getLink($addTitle=true, $isActiveTab=false)
    {
        $tabActiveStyle = 'inactive';
        if($isActiveTab){
            $tabActiveStyle = 'active';
        }

        if ($addTitle){
            $title = $this->getTitle();
            $link = $this->getLink(false);
            $link_title = $this->getLinkTitle();

            if ($this->_strict) {
                return "<a class=\"nav-link {$tabActiveStyle}\" href=\"{$link}\" {$link_title}>{$title}</a>";
            } elseif ($this->_secure) {
                $authkey = Current_User::getAuthKey();
                return "<a class=\"nav-link {$tabActiveStyle}\" href=\"{$link}&amp;tab={$this->id}&amp;authkey={$authkey}\" {$link_title}>{$title}</a>";
            } else {
                return "<a class=\"nav-link {$tabActiveStyle}\" href=\"{$link}&amp;tab={$this->id}\" {$link_title}>{$title}</a>";
            }
        } else {
            return $this->link;
        }
    }

    public function setOrder($order)
    {
        $this->tab_order = $order;
    }

    public function getOrder()
    {
        if (isset($this->tab_order))
        return $this->tab_order;

        $DB = new PHPWS_DB('controlpanel_tab');
        $DB->addColumn('tab_order', 'max');
        $max = $DB->select('one');

        if (PHPWS_Error::isError($max))
        exit($max->getMessage());

        if (isset($max))
        return $max + 1;
        else
        return 1;
    }

    public function setItemname($itemname)
    {
        $this->itemname = $itemname;
    }

    public function getItemname()
    {
        return $this->itemname;
    }

    public function disableSecure()
    {
        $this->_secure = false;
    }

    public function enableSecure()
    {
        $this->_secure = true;
    }

    public function save()
    {
        $db = new PHPWS_DB('controlpanel_tab');
        $db->addWhere('id', $this->id);
        $db->delete();
        $db->resetWhere();
        $this->tab_order = $this->getOrder();
        return $db->saveObject($this, false, false);
    }

    public function nextBox()
    {
        $db = new PHPWS_DB('controlpanel_tab');
        $db->addWhere('theme', $this->getTheme());
        $db->addWhere('theme_var', $this->getThemeVar());
        $db->addColumn('box_order', 'max');
        $max = $db->select('one');
        if (isset($max)) {
            return $max + 1;
        } else {
            return 1;
        }
    }


    /**
     * Moves the tab 'up' the order, which is actually a lower
     * order number
     */
    public function moveUp()
    {
        $db = new PHPWS_DB('controlpanel_tab');
        $db->setIndexBy('tab_order');
        $db->addOrder('tab_order');
        $allTabs = $db->getObjects('PHPWS_Panel_Tab');

        $current_order = $this->getOrder();
        if ($current_order == 1){
            unset($allTabs[1]);
            $allTabs[] = $this;
        } else {
            $tempObj = $allTabs[$current_order - 1];
            $allTabs[$current_order] = $tempObj;
            $allTabs[$current_order - 1] = $this;
        }


        $count = 1;
        foreach ($allTabs as $tab){
            $tab->setOrder($count);
            $tab->save();
            $count++;
        }
    }

    public function moveDown()
    {
        $db = new PHPWS_DB('controlpanel_tab');
        $db->setIndexBy('tab_order');
        $db->addOrder('tab_order');
        $allTabs = $db->getObjects('PHPWS_Panel_Tab');
        $number_of_tabs = count($allTabs);

        $current_order = $this->getOrder();
        if ($current_order == $number_of_tabs){
            unset($allTabs[$current_order]);
            array_unshift($allTabs, $this);
        } else {
            $tempObj = $allTabs[$current_order + 1];
            $allTabs[$current_order] = $tempObj;
            $allTabs[$current_order + 1] = $this;
        }

        $count = 1;
        foreach ($allTabs as $tab){
            $tab->setOrder($count);
            $tab->save();
            $count++;
        }

    }

}
