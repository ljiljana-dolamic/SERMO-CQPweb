<?php
/*
 * CQPweb: a user-friendly interface to the IMS Corpus Query Processor
 * Copyright (C) 2008-today Andrew Hardie and contributors
 * 
 * See http://cwb.sourceforge.net/cqpweb.php
 * 
 * This file is part of CQPweb.
 * 
 * CQPweb is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * CQPweb is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */







/*
 * =============================
 * MySQL setup / reset functions
 * =============================
 */




/**
 * Deletes a MySQL table, as long as it is not one of the system tables.
 * 
 * This funciton is intended to allow cleanup of stray tables.
 * 
 * Because this can cause data loss, it is limited to the admin user.
 * 
 * @param string $table  Table name.
 */
function delete_mysql_table($table)
{
	global $User;
	if (!$User->is_admin())
		return;

	$table = mysql_real_escape_string($table);
	
	$not_allowed = cqpweb_mysql_recreate_tables();
	
	if (isset($not_allowed[$table]))
		return;
	
	do_mysql_query("drop table if exists `$table`");
}


/**
 * Function to set the mysql setup to its initialised form.
 */
function cqpweb_mysql_total_reset()
{
	foreach (array( 'db_', 
					'freq_corpus_', 
					'freq_sc_',
					'freq_text_index_',
					'text_metadata_for_',
					'__tempfreq_', 
					'__freqmake_temptable'
					)
			as $prefix)
	{
		$result = do_mysql_query("show tables like '$prefix%'");
		while ( ($r = mysql_fetch_row($result)) !== false)
			do_mysql_query("drop table if exists $r");
	}
	
	$array_of_create_statements = cqpweb_mysql_recreate_tables();

	foreach ($array_of_create_statements as $name => $statement)
	{
		do_mysql_query("drop table if exists `$name`");
		do_mysql_query($statement);
	}
	
	$array_of_extra_statements = cqpweb_mysql_recreate_extras();
	
	foreach ($array_of_extra_statements as $statement)
		do_mysql_query($statement);		
}

/**
 * Gives you the create table statements for setup as an array.
 */
function cqpweb_mysql_recreate_tables()
{
	$create_statements = array();

	/* 
	 * IMPORTANT NOTE.
	 * 
	 * MySQL 5.5.5 (in Q2 2010) changed the default storage engine to InnoDB. 
	 * 
	 * CQPweb was originally based on the assumption that the engine would be MyISAM and
	 * thus, several of the statements below contained MyISAM-isms.
	 * 
	 * In Nov 2013, the MyISAM-isms were removed, so it will still work with the default InnoDB.
	 * 
	 * HOWEVER, fulltext index was not added to InnoDB until 5.6 (rel in Feb 2013)... ergo...
	 * 
	 * Note that we need InnoDB to allow tables to be stored in different locations (i.e. on different drives if necessary....)
	 */
	global $mysql_link;
	list($major, $minor, $rest) = explode('.', mysql_get_server_info($mysql_link), 3);
	$engine_if_fulltext_key_needed = ( ($major > 5 || ($major == 5 && $minor >= 6) ) ? 'ENGINE=InnoDB' : 'ENGINE=MyISAM');
	$engine = 'ENGINE=InnoDB';
	
	/*
	 * STRING FIELD LENGTHS TO USE
	 * ===========================
	 *  
	 * EXCEPTION: dbname is 200 because historically it was built from many components. 
	 * However,now its maxlength = 'db_catquery_' (12) plus the length of an instance name (which is 10).
	 * But we are keeping it at 200 for now because old data could be lost otherwise.
	 * 
	 * Long string (for names, descriptions, etc) - varchar 255
	 * 
	 * Handles - are informed by the limits of MYSQL. Most MySQL identfiiers are limited to 64 chars.
	 * =======
	 *  - the corpus:      20  (as needs to be a sub-part of a table name)
	 *  - p-attributes:    20  (also need to be a sub-part of a table name)
	 *  - s-attributes:    64  (as may need to be a column name)
	 *  - metadata fields: 64  (as may need to be a column name)
	 *  - username:        64
	 *  - metadata values: 200 (as they may need to be part of a key made up of multiple varchars)
	 *  - save names:      200 (to fit with the above)
	 *  - unique text ID:  255 (as they may need to be a key on their own) 
	 */
	
	/* nb it is somewhat inconsistent that here "name" = long desc rather than short handle. never mind.... */
	$create_statements['annotation_mapping_tables'] =
		"CREATE TABLE `annotation_mapping_tables` (
			`handle` varchar(20) NOT NULL,
			`name` varchar(255), 
			`mappings` text character set utf8,
			primary key(`handle`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";
	
	
	$create_statements['annotation_metadata'] =
		"CREATE TABLE `annotation_metadata` (
			`corpus` varchar(20) NOT NULL,
			`handle` varchar(20) NOT NULL,
			`description` varchar(255) default NULL,
			`is_feature_set` tinyint(1) NOT NULL default 0,
			`tagset` varchar(255) default NULL,
			`external_url` varchar(255) default NULL,
			primary key (`corpus`, `handle`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";
	
	
	$create_statements['annotation_template_info'] =
		"CREATE TABLE `annotation_template_info` (
			`id` int unsigned NOT NULL AUTO_INCREMENT,
			`description` varchar(255) default NULL,
			`primary_annotation` varchar(20) default NULL,
			PRIMARY KEY (`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['annotation_template_content'] =
		"CREATE TABLE `annotation_template_content` (
			`template_id` int unsigned NOT NULL,
			`order_in_template` smallint unsigned,
			`handle` varchar(20) NOT NULL,
			`description` varchar(255) default NULL,
			`is_feature_set` tinyint(1) NOT NULL default 0,
			`tagset` varchar(255) default NULL,
			`external_url` varchar(255) default NULL
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";
	
	
	$create_statements['clocurve_info'] = 
		"CREATE TABLE `clocurve_info` (
			`id` int unsigned NOT NULL AUTO_INCREMENT,
			`corpus` varchar(20) NOT NULL,
			`annotation` varchar(20) NOT NULL,
			`interval_width` int unsigned NOT NULL,
			`create_time` int default NULL,
			`create_duration` int unsigned default NULL,
			`n_datapoints` int unsigned,
			PRIMARY KEY (`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";
	
	
	$create_statements['clocurve_datapoints'] = 
		"CREATE TABLE `clocurve_datapoints` (
			`clocurve_id` int unsigned NOT NULL,
			`tokens` bigint unsigned NOT NULL,
			`types_so_far` bigint unsigned NOT NULL,
			KEY (`clocurve_id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";
	
	
	$create_statements['corpus_alignments'] = 
		"CREATE TABLE `corpus_alignments` (
			`corpus` varchar(20) NOT NULL,
			`target` varchar(20) NOT NULL
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";

	
	$create_statements['corpus_categories'] =
		"CREATE TABLE `corpus_categories` (
			`id` int NOT NULL AUTO_INCREMENT,
			`label` varchar(255) DEFAULT '',
			`sort_n` int NOT NULL DEFAULT 0,
			PRIMARY KEY (`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_general_ci";
	
	
	$create_statements['corpus_info'] =
		"CREATE TABLE `corpus_info` (
			/* 
			 * General fields: identity, CWB data addresses, && size 
			 */
			`id` int NOT NULL AUTO_INCREMENT,
			`corpus` varchar(20) NOT NULL,                                           # NB. This is always 100% the same as the handle in the PHP code.
			`cqp_name` varchar(255) NOT NULL default '',                             # needed because cwb_external might be true.........
			`date_of_indexing` timestamp NOT NULL default CURRENT_TIMESTAMP,
			`cwb_external` tinyint(1) NOT NULL default 0,
			`is_user_corpus` tinyint(1) NOT NULL default 0,
			`size_tokens` int NOT NULL DEFAULT 0,
			`size_types` int NOT NULL DEFAULT 0,
			`size_texts` int NOT NULL DEFAULT 0,
			`size_bytes_index` bigint unsigned NOT NULL DEFAULT 0,
			`size_bytes_freq`  bigint unsigned NOT NULL DEFAULT 0,

			/* 
			 * Licensing and access info
			 */
			`access_statement` TEXT default NULL,

			/* 
			 * Search & analysis settings
			 */
			`primary_classification_field` varchar(64) default NULL,
			`uses_case_sensitivity` tinyint(1) NOT NULL default 0,

			/* 
			 * System display: how the corpus is listed / appears in the interface
			 */
			`visible` tinyint(1) NOT NULL default 1,
			`title` varchar(255) default '', 
			`corpus_cat` int NOT NULL DEFAULT 1,
			`external_url` varchar(255) default NULL,
			`public_freqlist_desc` varchar(150) default NULL,
			`css_path` varchar(255) default '../css/CQPweb.css',

			/* 
			 * Annotation (p-attribute) info fields
			 */
			`primary_annotation` varchar(20) default NULL,
			`secondary_annotation` varchar(20) default NULL,
			`tertiary_annotation` varchar(20) default NULL,
			`tertiary_annotation_tablehandle` varchar(40) default NULL,
			`combo_annotation` varchar(20) default NULL,

			/* 
			 * Concordance/Context: appearance and visualisation control
			 * NB. Will it make sense to move viz to separate table?
			 */
			`main_script_is_r2l` tinyint(1) NOT NULL default 0,
			`conc_s_attribute` varchar(64) NOT NULL default '', # default for this + next translates to 12 words.
			`conc_scope` smallint NOT NULL default 12,
			`initial_extended_context` smallint NOT NULL default 100,
			`max_extended_context` smallint NOT NULL default 1100,
			`alt_context_word_att` varchar(20) default '',
			`visualise_gloss_in_concordance` tinyint(1) NOT NULL default 0,
			`visualise_gloss_in_context` tinyint(1) NOT NULL default 0,
			`visualise_gloss_annotation` varchar(20) default NULL,
			`visualise_translate_in_concordance` tinyint(1) NOT NULL default 0,
			`visualise_translate_in_context` tinyint(1) NOT NULL default 0,
			`visualise_translate_s_att` varchar(64) default NULL,
			`visualise_position_labels` tinyint(1) NOT NULL default 0,
			`visualise_position_label_attribute` varchar(64) default NULL,
			`visualise_conc_extra_js`     varchar(255) default '',
			`visualise_context_extra_js`  varchar(255) default '',
			`visualise_conc_extra_css`    varchar(255) default '',
			`visualise_context_extra_css` varchar(255) default '',
			`visualise_break_context_on_punc` tinyint(1) NOT NULL default 1,

			/* 
			 * Housekeeping
			 */
			`indexing_notes` TEXT default NULL,

			unique key (`corpus`),
			primary key (`id`)

		) $engine CHARACTER SET utf8 COLLATE utf8_general_ci";

	
	$create_statements['corpus_metadata_variable'] =
		"CREATE TABLE `corpus_metadata_variable` (
			`corpus` varchar(20) NOT NULL,
			`attribute` text NOT NULL,
			`value` text,
			key(`corpus`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['idlink_fields'] =
		"CREATE TABLE `idlink_fields` (
			`corpus` varchar(20) NOT NULL,
			`att_handle` varchar(64) NOT NULL,
			`handle` varchar(64) NOT NULL,
			`description` varchar(255) default NULL,
			`datatype` tinyint(2) NOT NULL default 0,   # uses METADATA_TYPE_* constants
			primary key (`corpus`, `att_handle`, `handle`)
	) $engine CHARACTER SET utf8 COLLATE utf8_bin";

	
	$create_statements['idlink_values'] =
		"CREATE TABLE `text_metadata_values` (
			`corpus` varchar(20) NOT NULL,
			`att_handle` varchar(64) NOT NULL,
			`field_handle` varchar(64) NOT NULL,
			`handle` varchar(200) NOT NULL,
			`description` varchar(255) default NULL,
			`category_n_items` int unsigned default NULL,
			`category_n_tokens` int unsigned default NULL,
			primary key(`corpus`, `att_handle`, `field_handle`, `handle`)
	) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['metadata_template_info'] = 
		"CREATE TABLE `metadata_template_info` (
			`id` int unsigned NOT NULL AUTO_INCREMENT,
			`description` varchar(255) default NULL,
			`primary_classification` varchar(64) default NULL,
			PRIMARY KEY (`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['metadata_template_content'] =
		"CREATE TABLE `metadata_template_content` (
			`template_id` int unsigned NOT NULL,
			`order_in_template` smallint unsigned,
			`handle` varchar(64) NOT NULL,
			`description` varchar(255) default NULL,
			`datatype`  tinyint(2) NOT NULL default " . METADATA_TYPE_FREETEXT . "
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['query_history'] =
		"create table query_history (
			`instance_name` varchar(31) default NULL,
			`user` varchar(64) default NULL,
			`corpus` varchar(20) NOT NULL default '',
			`cqp_query` text  NOT NULL,
			`query_scope` text,
			`date_of_query` timestamp NOT NULL default CURRENT_TIMESTAMP,
			`hits` int(11) default NULL,
			`simple_query` text,
			`query_mode` varchar(12) default NULL,
			KEY `user` (`user`),
			KEY `corpus` (`corpus`),
			KEY `cqp_query` (`cqp_query`(255))
		) $engine CHARACTER SET utf8 collate utf8_bin";

	
	$create_statements['saved_catqueries'] =
		"CREATE TABLE `saved_catqueries` (
			`catquery_name` varchar(150) NOT NULL,
			`user` varchar(64) default NULL,
			`corpus` varchar(20) NOT NULL  default '',
			`dbname` varchar(150) NOT NULL  default '',
			`category_list` TEXT,
			KEY `catquery_name` (`catquery_name`),
			KEY `user` (`user`),
			KEY `corpus` (`corpus`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['saved_dbs'] =
		"CREATE TABLE `saved_dbs` (
			`dbname` varchar(200) NOT NULL,
			`user` varchar(64) default NULL,
			`create_time` int(11) default NULL,
			`cqp_query` text NOT NULL,
			`query_scope` text,
			`postprocess` text,
			`corpus` varchar (20) NOT NULL default '',
			`db_type` varchar(15) default NULL,
			`colloc_atts` varchar(200) default '',
			`colloc_range` int default 0,
			`sort_position` int default 0,
			`db_size` bigint UNSIGNED default NULL,
			`saved` tinyint(1) NOT NULL default 0,
			primary key(`dbname`),
			key (`user`),
			key(`corpus`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";
	
	
	$create_statements['saved_freqtables'] =
		"CREATE TABLE `saved_freqtables` (
			`freqtable_name` varchar(150) NOT NULL,
			`corpus` varchar(20) NOT NULL default '',
			`user` varchar(64) default NULL,
			`query_scope` text,
			`create_time` int(11) default NULL,
			`ft_size` bigint UNSIGNED default NULL,
			`public` tinyint(1) default 0,
			primary key (`freqtable_name`),
			key `query_scope` (`query_scope`(255))
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";
	

	$create_statements['saved_matrix_info'] =
		"CREATE TABLE `saved_matrix_info` (
			`id` int NOT NULL AUTO_INCREMENT,
			`savename` varchar(200),
			`user` varchar(64) default NULL,
			`corpus` varchar(20) NOT NULL default '',
			`subcorpus` varchar(200) NOT NULL default '',   #TODO this could be changed to an integer, couldn't it?? 
			`unit` varchar(200) default NULL,
			`create_time` int(11) default NULL,
			primary key(`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";
	
	
	$create_statements['saved_matrix_features'] = 
		"CREATE TABLE `saved_matrix_features` (
			`id` int NOT NULL AUTO_INCREMENT,
			`matrix_id` int NOT NULL,
			`label` varchar(255) NOT NULL,
			`source_info` varchar(255) default NULL,
			primary key(`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['saved_queries'] =
		"CREATE TABLE `saved_queries` (
			`query_name` varchar(150) NOT NULL,
			`user` varchar(64) default NULL,
			`corpus` varchar(20) NOT NULL default '',
			`query_mode` varchar(12) default NULL,
			`simple_query` text,
			`cqp_query` text NOT NULL,
			`query_scope` text,
			`postprocess` text,
			`hits_left` text,
			`time_of_query` int(11) default NULL,
			`hits` int(11) default NULL,
			`hit_texts` int(11) default NULL,
			`file_size` int(10) unsigned default NULL,
			`saved` tinyint(1) default 0,
			`save_name` varchar(200) default NULL,
			KEY `query_name` (`query_name`),
			KEY `user` (`user`),
			KEY `corpus` (`corpus`),
			FULLTEXT KEY `query_scope` (`query_scope`),
			FULLTEXT KEY `postprocess` (`postprocess`(100)),
			KEY `time_of_query` (`time_of_query`),
			FULLTEXT KEY `cqp_query` (`cqp_query`)
		) $engine_if_fulltext_key_needed CHARACTER SET utf8 COLLATE utf8_bin";
	
	
	$create_statements['saved_restrictions'] =
		"CREATE TABLE `saved_restrictions` (
			`id` bigint unsigned NOT NULL AUTO_INCREMENT,
			`cache_time` bigint unsigned NOT NULL default 0,
			`corpus` varchar(20) NOT NULL default '',
			`serialised_restriction` text,
			`n_items` int unsigned,
			`n_tokens` bigint unsigned,
			`data` longblob,
			primary key (`id`),
			key(`corpus`),
			key(`serialised_restriction`(255))
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['saved_subcorpora'] =
		"CREATE TABLE `saved_subcorpora` (
			`id` bigint unsigned NOT NULL AUTO_INCREMENT,
			`name` varchar(200) NOT NULL default '',
			`corpus` varchar(20) NOT NULL default '',
			`user` varchar(64) default NULL,
			`content` mediumtext,
			`n_items` int(11) unsigned default NULL,
			`n_tokens` bigint(21) unsigned default NULL,
			primary key (`id`),
			key(`corpus`, `user`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";
	

	$create_statements['system_info'] =
		"CREATE TABLE `system_info` (
			setting_name varchar(20) NOT NULL collate utf8_bin,
			value varchar(255),
			primary key(`setting_name`)
		) CHARACTER SET utf8 COLLATE utf8_general_ci"; /* note that for this one we don't care about the engine */


	$create_statements['system_longvalues'] =
		"CREATE TABLE `system_longvalues` (
			`timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			`id` varchar(40) NOT NULL,
			`value` longtext NOT NULL,
			primary key(`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";
	
	
	$create_statements['system_messages'] =
		"CREATE TABLE `system_messages` (
			`message_id` varchar(150) NOT NULL,
			`timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			`header` varchar(150) default '',
			`content` text character set utf8 collate utf8_bin,
			`fromto` varchar(150) default NULL,
			primary key (`message_id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_general_ci";
	
	
	$create_statements['system_processes'] =
		"CREATE TABLE `system_processes` (
			`dbname` varchar(200) NOT NULL,
			`begin_time` int(11) default NULL,
			`process_type` varchar(15) default NULL,
			`process_id` varchar(15) default NULL,
			primary key (`dbname`)
		) CHARACTER SET utf8 COLLATE utf8_bin"; /* note that for this one we don't care about the engine */
	

	$create_statements['text_metadata_fields'] =
		"CREATE TABLE `text_metadata_fields` (
			`corpus` varchar(20) NOT NULL,
			`handle` varchar(64) NOT NULL,
			`description` varchar(255) default NULL,
			`datatype` tinyint(2) NOT NULL default 0,   # uses METADATA_TYPE_* constants
			primary key (`corpus`, `handle`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";

	
	$create_statements['text_metadata_values'] =
		"CREATE TABLE `text_metadata_values` (
			`corpus` varchar(20) NOT NULL,
			`field_handle` varchar(64) NOT NULL,
			`handle` varchar(200) NOT NULL,
			`description` varchar(255) default NULL,
			`category_num_words` int unsigned default NULL,
			`category_num_files` int unsigned default NULL,
			primary key(`corpus`, `field_handle`, `handle`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['user_captchas'] = 
		"CREATE TABLE `user_captchas` (
			`id` bigint unsigned NOT NULL AUTO_INCREMENT,
			`captcha` char(6),
			`expiry_time` int unsigned,
			primary key (`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";

	
	$create_statements['user_cookie_tokens'] =
		"CREATE TABLE `user_cookie_tokens` (
			`token` char(64) NOT NULL DEFAULT '',
			`user_id` int NOT NULL,
			`creation`  int UNSIGNED NOT NULL default 0,
			`expiry`  int UNSIGNED NOT NULL default 0,
			key(`token`, `user_id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['user_grants_to_users'] =
		"CREATE TABLE `user_grants_to_users` (
			`user_id` int NOT NULL,
			`privilege_id` int NOT NULL,
			`expiry_time` int UNSIGNED NOT NULL default 0
		) $engine CHARACTER SET utf8 COLLATE utf8_general_ci";
	
	
	$create_statements['user_grants_to_groups'] =
		"CREATE TABLE `user_grants_to_groups` (
			`group_id` int NOT NULL,
			`privilege_id` int NOT NULL,
			`expiry_time` int UNSIGNED NOT NULL default 0
		) $engine CHARACTER SET utf8 COLLATE utf8_general_ci";
	
	
	$create_statements['user_groups'] =
		"CREATE TABLE `user_groups` (
			`id` int NOT NULL AUTO_INCREMENT,
			`group_name` varchar(20) NOT NULL UNIQUE COLLATE utf8_bin,
			`description` varchar(255) NOT NULL default '',
			`autojoin_regex` text,
			primary key (`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_general_ci";


	$create_statements['user_info'] =
		"CREATE TABLE `user_info` (
			`id` int NOT NULL AUTO_INCREMENT,
			`username` varchar(64) NOT NULL,
			`realname` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
			`email` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
			`affiliation` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
			`country` char(2) default '00',
			`passhash` char(61),
			`acct_status` tinyint(1) NOT NULL default 0,
			`verify_key` varchar(32) default NULL,
			`expiry_time` int UNSIGNED NOT NULL default 0,
			`password_expiry_time` int UNSIGNED NOT NULL default 0,
			`last_seen_time` timestamp NOT NULL default 0,
			`acct_create_time` timestamp NOT NULL default CURRENT_TIMESTAMP,
			`conc_kwicview` tinyint(1),
			`conc_corpus_order` tinyint(1),
			`cqp_syntax` tinyint(1),
			`context_with_tags` tinyint(1),
			`use_tooltips` tinyint(1),
			`css_monochrome` tinyint(1) NOT NULL default 0,
			`thin_default_reproducible` tinyint(1),
			`coll_statistic` tinyint,
			`coll_freqtogether` int,
			`coll_freqalone` int,
			`coll_from` tinyint,
			`coll_to` tinyint,
			`max_dbsize` int(10) unsigned default NULL,
			`linefeed` char(2) default NULL,
			unique key(`username`),
			primary key (`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";

	
	$create_statements['user_macros'] =
		"CREATE TABLE `user_macros` (
			`id` int NOT NULL AUTO_INCREMENT,
			`user` varchar(64) NOT NULL,
			`macro_name` varchar(20) NOT NULL default '',
			`macro_num_args` int,
			`macro_body` text,
			unique key(`user`, `macro_name`),
			primary key(`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['user_memberships'] = 
		"CREATE TABLE `user_memberships` (
			`user_id` int NOT NULL,
			`group_id` int NOT NULL,
			`expiry_time` int UNSIGNED NOT NULL default 0
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";
	
	
	$create_statements['user_privilege_info'] =
		"CREATE TABLE `user_privilege_info` (
			`id` int NOT NULL AUTO_INCREMENT,
			`description` varchar(255) default '',
			`type` tinyint(1) unsigned default NULL,
			`scope` text,
			primary key(`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";
	

	$create_statements['xml_metadata'] = 
		"CREATE TABLE `xml_metadata` (
			`id` int NOT NULL AUTO_INCREMENT,      # note: we can use the numeric id in situations where the handle won't fit. int base 36 = max 13 chars
			`corpus` varchar(20) NOT NULL,
			`handle` varchar(64) NOT NULL,
			`att_family` varchar(64) NOT NULL default '',
			`description` varchar(255) default NULL,
			`datatype`  tinyint(2) NOT NULL default " . METADATA_TYPE_NONE . ",
			primary key(`id`),
			unique key (`corpus`, `handle`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['xml_metadata_values'] = 
		"CREATE TABLE `xml_metadata_values` (
			`corpus` varchar(20) NOT NULL,
			`att_handle` varchar(64) NOT NULL,
			`handle` varchar(200) NOT NULL,
			`description` varchar(255) default NULL, 
			`category_num_words` int unsigned default NULL,
			`category_num_segments` int unsigned default NULL,
			primary key(`corpus`, `att_handle`, `handle`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['xml_template_info'] = 
		"CREATE TABLE `xml_template_info` (
			`id` int unsigned NOT NULL AUTO_INCREMENT,
			`description` varchar(255) default NULL,
			PRIMARY KEY (`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['xml_template_content'] =
		"CREATE TABLE `xml_template_content` (
			`template_id` int unsigned NOT NULL,
			`order_in_template` smallint unsigned,
			`handle` varchar(64) NOT NULL,
			`att_family` varchar(64) NOT NULL default '',
			`description` varchar(255) default NULL,
			`datatype`  tinyint(2) NOT NULL default " . METADATA_TYPE_NONE . "
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['xml_visualisations'] =
		"CREATE TABLE `xml_visualisations` (
			`id` int NOT NULL AUTO_INCREMENT,
			`corpus` varchar(20) NOT NULL default '',
			`element` varchar(70) NOT NULL default '',     # length is maxlength of handle + suffix '~start/~end'
			`conditional_on` varchar(1024) NOT NULL default '',
			`in_concordance` tinyint(1) NOT NULL default 1,
			`in_context` tinyint(1) NOT NULL default 1,
			`in_download` tinyint(1) NOT NULL default 0,
			`html` varchar(1024) NOT NULL default '',
			primary key (`id`),
			key(`corpus`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";	
	
	return $create_statements;
}


/**
 * Returns an array of statements that should be run
 * to put the system into initial state, AFTER creation of the tables.
 */ 
function cqpweb_mysql_recreate_extras()
{
	return array(
		'insert into user_groups (group_name,description)values("superusers","Users with admin power")',
		'insert into user_groups (group_name,description)values("everybody","Group to which all users automatically belong")',
		'insert into system_info (setting_name, value)values("db_version","' . CQPWEB_VERSION . '")',
		'insert into system_info (setting_name, value)values("install_date", "' .  @date('Y-m-d H:i') . '") ',
		'insert into system_info (setting_name, value)values("db_updated", "'   .  @date('Y-m-d H:i') . '") ',
		);
}





