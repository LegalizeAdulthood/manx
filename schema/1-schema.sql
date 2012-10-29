--
-- Table structure for table `CITEPUB`
--

CREATE TABLE IF NOT EXISTS `CITEPUB` (
  `pub` int(11) NOT NULL default '0',
  `mentions_pub` int(11) NOT NULL default '0',
  PRIMARY KEY  (`pub`,`mentions_pub`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `COMPANY`
--

CREATE TABLE IF NOT EXISTS `COMPANY` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) default NULL,
  `shortname` varchar(50) default NULL,
  `sort_name` varchar(50) NOT NULL default '',
  `display` enum('N','Y') NOT NULL default 'N',
  `notes` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=105 DEFAULT CHARSET=utf8;

--
-- Table structure for table `COPY`
--

CREATE TABLE IF NOT EXISTS `COPY` (
  `copyid` int(11) NOT NULL auto_increment,
  `pub` int(11) NOT NULL default '0',
  `format` varchar(10) NOT NULL default '',
  `site` int(11) default NULL,
  `url` varchar(255) default NULL,
  `notes` varchar(200) default NULL,
  `size` int(11) default NULL,
  `md5` varchar(32) default NULL,
  `credits` varchar(200) default NULL,
  `amend_serial` int(11) default NULL,
  PRIMARY KEY  (`copyid`),
  KEY `pub` (`pub`),
  KEY `url` (`url`)
) ENGINE=MyISAM AUTO_INCREMENT=15239 DEFAULT CHARSET=utf8;

--
-- Table structure for table `LANGUAGE`
--

CREATE TABLE IF NOT EXISTS `LANGUAGE` (
  `lang_alpha_3` char(3) NOT NULL,
  `lang_alpha_2` char(2) default NULL,
  `eng_lang_name` varchar(40) default NULL,
  PRIMARY KEY  (`lang_alpha_3`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `mirror`
--

CREATE TABLE IF NOT EXISTS `mirror` (
  `mirror_id` smallint(6) NOT NULL auto_increment,
  `site` smallint(6) NOT NULL,
  `original_stem` varchar(200) NOT NULL default '',
  `copy_stem` varchar(200) NOT NULL default '',
  `rank` smallint(6) NOT NULL default '0',
  PRIMARY KEY  (`mirror_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

--
-- Table structure for table `PART`
--

CREATE TABLE IF NOT EXISTS `PART` (
  `id` int(11) NOT NULL auto_increment,
  `company` int(11) NOT NULL,
  `part_num` varchar(15) default NULL,
  `part_class` varchar(10) default NULL,
  `part_basic` varchar(10) default NULL,
  `part_variant` varchar(5) default NULL,
  `description` varchar(50) default NULL,
  `hw_sw` varchar(2) default NULL,
  `where_used` varchar(20) default NULL,
  `eng_mgr` varchar(5) default NULL,
  `eng_mgr_first` varchar(20) default NULL,
  `eng_mgr_last` varchar(20) default NULL,
  `des_mgr` varchar(5) default NULL,
  `des_mgr_first` varchar(20) default NULL,
  `des_mgr_last` varchar(20) default NULL,
  `prod_mgr` varchar(5) default NULL,
  `prod_mgr_first` varchar(20) default NULL,
  `prod_mgr_last` varchar(20) default NULL,
  `mfg_rep` varchar(5) default NULL,
  `mfg_rep_first` varchar(20) default NULL,
  `mfg_rep_last` varchar(20) default NULL,
  `mntt_eng_mgr` varchar(5) default NULL,
  `mntt_eng_first` varchar(20) default NULL,
  `mntt_eng_last` varchar(20) default NULL,
  `src_stock_room` varchar(5) default NULL,
  `src_stock_room_locn` varchar(5) default NULL,
  `mfg_prod_line` varchar(20) default NULL,
  `status_code` varchar(20) default NULL,
  `status_date` varchar(7) default NULL,
  `prod_cat` varchar(5) default NULL,
  `part_type` varchar(9) default NULL,
  `voltage_code` char(1) default NULL,
  `func_class` char(1) default NULL,
  `date_added` char(6) default NULL,
  `date_changed` char(6) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=502968 DEFAULT CHARSET=utf8;

--
-- Table structure for table `PUB`
--

CREATE TABLE IF NOT EXISTS `PUB` (
  `pub_id` int(11) NOT NULL auto_increment,
  `pub_active` tinyint(1) NOT NULL default '1',
  `pub_history` int(11) NOT NULL,
  `pub_has_online_copies` tinyint(1) NOT NULL default '0',
  `pub_has_offline_copies` tinyint(1) NOT NULL default '0',
  `pub_has_toc` tinyint(1) NOT NULL default '0',
  `pub_superseded` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`pub_id`)
) ENGINE=MyISAM AUTO_INCREMENT=18307 DEFAULT CHARSET=utf8;

--
-- Table structure for table `PUBHISTORY`
--

CREATE TABLE IF NOT EXISTS `PUBHISTORY` (
  `ph_id` int(11) NOT NULL auto_increment,
  `ph_active` tinyint(1) NOT NULL default '1',
  `ph_created` datetime NOT NULL,
  `ph_edited_by` int(11) default NULL,
  `ph_pub` int(11) NOT NULL,
  `ph_pubtype` char(1) NOT NULL default 'D',
  `ph_company` int(11) default NULL,
  `ph_part` varchar(50) default NULL,
  `ph_alt_part` varchar(50) default NULL,
  `ph_revision` varchar(20) default NULL,
  `ph_pubdate` varchar(10) default NULL,
  `ph_title` varchar(255) default NULL,
  `ph_keywords` varchar(100) default NULL,
  `ph_notes` varchar(255) default NULL,
  `ph_class` varchar(40) default NULL,
  `ph_match_part` varchar(30) default NULL,
  `ph_match_alt_part` varchar(30) default NULL,
  `ph_sort_part` varchar(30) default NULL,
  `ph_abstract` varchar(255) default NULL,
  `ph_ocr_file` varchar(50) default NULL,
  `ph_cover_image` varchar(255) default NULL,
  `ph_lang` varchar(20) NOT NULL default '+en',
  `ph_amend_pub` int(11) default NULL,
  `ph_amend_serial` int(11) default NULL,
  PRIMARY KEY  (`ph_id`),
  KEY `ph_pub` (`ph_pub`)
) ENGINE=MyISAM AUTO_INCREMENT=18299 DEFAULT CHARSET=utf8;

--
-- Table structure for table `PUBTAG`
--

CREATE TABLE IF NOT EXISTS `PUBTAG` (
  `pub` int(11) NOT NULL default '0',
  `tag` int(11) NOT NULL default '0',
  PRIMARY KEY  (`pub`,`tag`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `SITE`
--

CREATE TABLE IF NOT EXISTS `SITE` (
  `siteid` int(11) NOT NULL auto_increment,
  `name` varchar(100) default NULL,
  `url` varchar(200) default NULL,
  `description` varchar(200) NOT NULL default '',
  `copy_base` varchar(200) NOT NULL default '',
  `low` enum('N','Y') NOT NULL default 'N',
  `live` enum('N','Y') NOT NULL default 'Y',
  `display_order` int(11) NOT NULL default '999',
  PRIMARY KEY  (`siteid`)
) ENGINE=MyISAM AUTO_INCREMENT=54 DEFAULT CHARSET=utf8;

--
-- Table structure for table `SUPERSESSION`
--

CREATE TABLE IF NOT EXISTS `SUPERSESSION` (
  `old_pub` int(11) NOT NULL default '0',
  `new_pub` int(11) NOT NULL default '0',
  PRIMARY KEY  (`old_pub`,`new_pub`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `TAG`
--

CREATE TABLE IF NOT EXISTS `TAG` (
  `id` int(11) NOT NULL auto_increment,
  `class` varchar(20) default NULL,
  `tag` varchar(20) NOT NULL default '',
  `tag_text` varchar(200) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=116 DEFAULT CHARSET=utf8;

--
-- Table structure for table `TOC`
--

CREATE TABLE IF NOT EXISTS `TOC` (
  `pub` int(11) NOT NULL default '0',
  `line` int(11) NOT NULL default '0',
  `level` int(11) NOT NULL default '0',
  `label` varchar(20) NOT NULL default '',
  `name` varchar(255) NOT NULL default '',
  KEY `pub` (`pub`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL auto_increment,
  `email` character varying(32),
  `pw_sha1` character varying(40),
  `first_name` character varying(64),
  `last_name` character varying(64),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `user_session`
--

CREATE TABLE IF NOT EXISTS user_session (
  `id` int(11) NOT NULL auto_increment,
  `ascii_session_id` character varying(32),
  `logged_in` bool,
  `user_id` int(11),
  `last_impression` timestamp,
  `created` timestamp,
  `user_agent` character varying(256),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `session_variable`
--

CREATE TABLE IF NOT EXISTS `session_variable` (
  `id` int(11) NOT NULL auto_increment,
  `session_id` int4,
  `name` character varying(64),
  `value` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

COMMIT;
