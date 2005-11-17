<?php

  /**
   * @author Matthew McNaney <mcnaney at gmail dot com>
   * @version $Id$
   */

class RSS_Channel {
    var $id              = 0;
    var $module          = NULL;
    var $title           = NULL;
    var $link            = NULL;
    var $description     = NULL;
    var $pub_date        = 0;
    var $last_build_date = 0;
    var $category        = NULL;
    var $ttl             = 0;
    var $image           = 0;
    var $text_input      = NULL;
    var $active          = 1;

    var $_feeds          = NULL;
    var $_error          = NULL;


    function RSS_Channel($id=NULL)
    {
        if (!$id) {
            return;
        }

        $this->id = (int)$id;
        $this->init();
    }

    function init()
    {
        $db = & new PHPWS_DB('rss_channel');
        $result = $db->loadObject($this);
        if (PEAR::isError($result)) {
            $this->_error = $result;
            return $result;
        }
    }

    function post()
    {
        if (isset($_POST['title'])) {
            $this->title = $_POST['title'];
        } else {
            $errors[] = _('You must enter a title.');
        }

        if (isset($_POST['description'])) {
            $this->description = strip_tags($_POST['description']);
        } else {
            $this->description = NULL;
        }

        if (isset($errors)) {
            return $errors;
        } else {
            return TRUE;
        }
    }

    function save()
    {
        $db = & new PHPWS_DB('rss_channel');
        return $db->saveObject($this);
    }

    function getActionLinks()
    {
        $vars['channel_id'] = $this->id;

        $vars['command'] = 'edit_channel';
        $links[] = PHPWS_Text::secureLink(_('Edit'), 'rss', $vars);

        return $links;
    }

    function getAddress()
    {
        return 'index.php?module=rss&amp;mod_title=' . $this->module;
    }

    function loadFeeds()
    {
        $db = & new PHPWS_DB('phpws_key');
        $db->addWhere('active', 1);
        $db->addWhere('restricted', 0);
        $db->addOrder('create_date desc');
        // rss limit is 15
        $db->setLimit('15');

        $result = $db->getObjects('Key');

        if (PEAR::isError($result)) {
            $this->_feeds = NULL;
            $this->_error = $result;
            return $result;
        } else {
            $this->_feeds = $result;
            return TRUE;
        }

    }

    function view()
    {
        $home_http = PHPWS_Core::getHomeHttp();

        $tpl = & new PHPWS_Template('rss');
        $tpl->setFile('rss10.tpl');
        
        $channel_data['CHANNEL_TITLE']       = $this->title;
        $channel_data['CHANNEL_ADDRESS']     = $this->getAddress();
        $channel_data['HOME_ADDRESS']        = $home_http;
        $channel_data['CHANNEL_DESCRIPTION'] = $this->description;
        $channel_data['LANGUAGE']            = 'en-us'; // change later
        $channel_data['SEARCH_LINK'] = sprintf('%sindex.php?module=search&amp;mod_title=%s&amp;user=search',
                                               $home_http, $this->module);
        $channel_data['SEARCH_DESCRIPTION'] = sprintf('Search in %s', $this->title);
        $channel_data['SEARCH_NAME'] = 'search';

        //        $channel_data['LAST_BUILD_DATE'] = $this->last_build_date;

        foreach ($this->_feeds as $key) {
            $itemTpl = NULL;
            $itemTpl['ITEM_LINK']         = $home_http . $key->url;

            $tpl->setCurrentBlock('item-about');
            $tpl->setData($itemTpl);
            $tpl->parseCurrentBlock();

            $itemTpl['ITEM_TITLE']        = $key->title;
            $itemTpl['ITEM_GUID']         = $home_http . $key->url;
            $itemTpl['ITEM_LINK']         = $home_http . $key->url;
            $itemTpl['ITEM_SOURCE']       = sprintf('%sindex.php?module=rss&amp;mod_title=%s', $home_http, $this->module);

            $itemTpl['ITEM_DESCRIPTION']  = $key->summary;
            $itemTpl['ITEM_AUTHOR']       = $key->creator;
            $itemTpl['ITEM_PUBDATE']      = $key->getCreateDate('%a, %d %b %Y %T GMT');

            $itemTpl['ITEM_DC_DATE']      = $key->getCreateDate('%Y-%m-%dT%H:%M');
            $itemTpl['ITEM_DC_TYPE']      = 'Text'; //pull from db later
            $itemTpl['ITEM_DC_CREATOR']   = $key->creator;

            $itemTpl['ITEM_SOURCE_TITLE'] = $this->title;

            $tpl->setCurrentBlock('item-listing');
            $tpl->setData($itemTpl);
            $tpl->parseCurrentBlock();
        }

        $tpl->setData($channel_data);
        $content = $tpl->get();
        return $content;
        //        echo preg_replace('/.*(<\?xml)/Ui', '\\1', $content);
        exit();
    }

}

?>