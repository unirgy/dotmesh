<?php

/**
* This file is part of DotMesh.
*
* DotMesh is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* any later version.
*
* Foobar is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with DotMesh.  If not, see <http://www.gnu.org/licenses/>.
*
* @package DotMesh (tm)
* @link http://dotmesh.org
* @author Boris Gurvich <boris@unirgy.com>
* @copyright (c) 2012 Boris Gurvich
* @license http://www.gnu.org/licenses/gpl.txt
*/

class DotMesh_Migrate extends BClass
{
    public static function run()
    {
        BMigrate::i()->install('0.1.0', 'DotMesh_Migrate::install');
    }

    public static function install()
    {
        BDb::ddlStart();

        $nodeTable = DotMesh_Model_Node::table();
        $userTable = DotMesh_Model_User::table();
        $tagTable = DotMesh_Model_Tag::table();
        $postTable = DotMesh_Model_Post::table();

        BDb::ddlTable($nodeTable, array(
            'id' => "int(10) unsigned NOT NULL AUTO_INCREMENT",
            'uri' => "varchar(100) NOT NULL",
            'api_version' => "smallint(5) unsigned NOT NULL DEFAULT '1'",
            'is_local' => "tinyint(3) unsigned NOT NULL DEFAULT '0'",
            'is_https' => "tinyint(3) unsigned NOT NULL DEFAULT '0'",
            'is_rewrite' => "tinyint(3) unsigned NOT NULL DEFAULT '0'",
            'authorized_ips' => "text",
            'secret_key' => "varchar(64) CHARACTER SET latin1 COLLATE latin1_bin DEFAULT NULL",
            'score' => "int(10) unsigned NOT NULL",
            'is_blocked' => "tinyint(3) unsigned NOT NULL DEFAULT '0'",
            'last_postname' => "varchar(10) DEFAULT NULL",
            'support_email' => "varchar(100) DEFAULT NULL",
        ), array('primary'=>'(`id`)'));
        BDb::ddlTableColumns($nodeTable, array(), array(
            'IDX_uri' => 'UNIQUE (`uri`)',
        ));

        BDb::ddlTable($userTable, array(
            'id' => "int(10) unsigned NOT NULL AUTO_INCREMENT",
            'node_id' => "int(10) unsigned NOT NULL",
            'username' => "varchar(32) NOT NULL",
            'password_hash' => "varchar(64) CHARACTER SET latin1 COLLATE latin1_bin DEFAULT NULL",
            'password_nonce' => "varchar(32) CHARACTER SET latin1 COLLATE latin1_bin DEFAULT NULL",
            'secret_key' => "varchar(64) CHARACTER SET latin1 COLLATE latin1_bin DEFAULT NULL",
            'remote_signature' => "varchar(100) CHARACTER SET latin1 COLLATE latin1_bin DEFAULT NULL",
            'firstname' => "varchar(32) DEFAULT NULL",
            'lastname' => "varchar(32) DEFAULT NULL",
            'email' => "varchar(100) DEFAULT NULL",
            'short_bio' => "text",
            'is_admin' => "tinyint(3) unsigned NOT NULL",
            'is_confirmed' => "tinyint(3) unsigned NOT NULL",
            'is_blocked' => "tinyint(3) unsigned NOT NULL",
            'thumb_provider' => "enum('file','link','gravatar') NOT NULL DEFAULT 'file'",
            'thumb_filename' => "varchar(100) DEFAULT NULL",
            'thumb_uri' => "varchar(255) DEFAULT NULL",
            'last_login' => "datetime DEFAULT NULL",
            'preferences_data' => "text",
            'twitter_screenname' => "varchar(50) DEFAULT NULL",
            'twitter_data' => "text",
        ), array('primary'=>'(`id`)'));
        BDb::ddlTableColumns($userTable, array(), array(
            'IDX_node_username' => "UNIQUE (`node_id`,`username`)",
            'IDX_node_twitter_screenname' => "UNIQUE (`node_id`,`twitter_screenname`)",
        ), array(
            'FK_dm_user_node' => "FOREIGN KEY (`node_id`) REFERENCES `{$nodeTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
        ));

        BDb::ddlTable($tagTable, array(
            'id' => "int(10) unsigned NOT NULL AUTO_INCREMENT",
            'node_id' => "int(10) unsigned NOT NULL",
            'tagname' => "varchar(32) NOT NULL",
            'score' => "int(11) NOT NULL",
        ), array('primary'=>'(`id`)'));
        BDb::ddlTableColumns($tagTable, array(), array(
            'IDX_node_tagname' => "UNIQUE (`node_id`,`tagname`)",
        ), array(
            'FK_dm_tag_node' => "FOREIGN KEY (`node_id`) REFERENCES `{$nodeTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
        ));

        BDb::ddlTable($postTable, array(
            'id' => "int(10) unsigned NOT NULL AUTO_INCREMENT",
            'node_id' => "int(10) unsigned NOT NULL",
            'postname' => "varchar(20) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL",
            'thread_id' => "int(10) unsigned DEFAULT NULL",
            'user_id' => "int(10) unsigned NOT NULL",
            'echo_user_id' => "int(10) unsigned DEFAULT NULL",
            'preview' => "varchar(255) NOT NULL",
            'contents' => "text",
            'create_dt' => "datetime DEFAULT NULL",
            'update_dt' => "datetime DEFAULT NULL",
            'is_private' => "tinyint(4) NOT NULL DEFAULT '0'",
            'is_tweeted' => "tinyint(4) NOT NULL DEFAULT '0'",
            'is_pinned' => "tinyint(4) NOT NULL DEFAULT '0'",
        ), array('primary'=>'(`id`)'));
        BDb::ddlTableColumns($postTable, array(), array(
            'IDX_node_postname' => "UNIQUE (`node_id`,`postname`)",
            'IDX_thread' => "(`thread_id`)",
            'IDX_pinned_created' => "(`is_pinned`,`create_dt`)",
            'FK_dm_post_user' => "(`user_id`)",
            'FK_dm_post_echo_user' => "(`echo_user_id`)",
        ), array(
            'FK_dm_post_node' => "FOREIGN KEY (`node_id`) REFERENCES `{$nodeTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            'FK_dm_post_user' => "FOREIGN KEY (`user_id`) REFERENCES `{$userTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            'FK_dm_post_echo_user' => "FOREIGN KEY (`echo_user_id`) REFERENCES `{$userTable}` (`id`) ON DELETE SET NULL ON UPDATE CASCADE",
        ));

        BDb::ddlTable(DotMesh_Model_NodeBlock::table(), array(
            'user_id' => "int(10) unsigned NOT NULL",
            'block_node_id' => "int(10) unsigned NOT NULL",
        ), array('primary'=>'(`user_id`, `block_node_id`)'));
        BDb::ddlTableColumns(DotMesh_Model_NodeBlock::table(), array(), array(
            'FK_node_block_block_node' => "(`block_node_id`)",
        ), array(
            'FK_node_block_block_node' => "FOREIGN KEY (`block_node_id`) REFERENCES `{$nodeTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            'FK_node_block_user' => "FOREIGN KEY (`user_id`) REFERENCES `{$userTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
        ));

        BDb::ddlTable(DotMesh_Model_PostFeedback::table(), array(
            'post_id' => "int(10) unsigned NOT NULL",
            'user_id' => "int(10) unsigned NOT NULL",
            'echo' => "tinyint(4) NOT NULL DEFAULT '0'",
            'star' => "tinyint(4) NOT NULL DEFAULT '0'",
            'flag' => "tinyint(4) NOT NULL DEFAULT '0'",
            'vote_up' => "tinyint(4) NOT NULL DEFAULT '0'",
            'vote_down' => "tinyint(4) NOT NULL DEFAULT '0'",
            'vote_up_dt' => "datetime DEFAULT NULL COMMENT 'For hot sorting'",
        ), array('primary'=>'(`post_id`, `user_id`)'));
        BDb::ddlTableColumns(DotMesh_Model_PostFeedback::table(), array(), array(
            'IDX_by_user' => "(`user_id`,`post_id`)",
        ), array(
            'FK_post_feedback_post' => "FOREIGN KEY (`post_id`) REFERENCES `{$postTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            'FK_post_feedback_user' => "FOREIGN KEY (`user_id`) REFERENCES `{$userTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
        ));

        BDb::ddlTable(DotMesh_Model_PostFile::table(), array(
            'id' => "int(10) unsigned NOT NULL AUTO_INCREMENT",
            'post_id' => "int(10) unsigned NOT NULL",
            'filename' => "varchar(50) DEFAULT NULL",
            'filetype' => "varchar(10) DEFAULT NULL",
            'label' => "varchar(255) DEFAULT NULL",
        ), array('primary'=>'(`id`)'));
        BDb::ddlTableColumns(DotMesh_Model_PostFile::table(), array(), array(
            'FK_post_file_post' => "(`post_id`)",
        ), array(
            'FK_post_file_post' => "FOREIGN KEY (`post_id`) REFERENCES `{$postTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
        ));

        BDb::ddlTable(DotMesh_Model_PostTag::table(), array(
            'post_id' => "int(10) unsigned NOT NULL",
            'tag_id' => "int(10) unsigned NOT NULL",
        ), array('primary'=>'(`post_id`, `tag_id`)'));
        BDb::ddlTableColumns(DotMesh_Model_PostTag::table(), array(), array(
            'IDX_by_tag' => "(`tag_id`,`post_id`)",
        ), array(
            'FK_post_tag_post' => "FOREIGN KEY (`post_id`) REFERENCES `{$postTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            'FK_post_tag_tag' => "FOREIGN KEY (`tag_id`) REFERENCES `{$tagTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
        ));

        BDb::ddlTable(DotMesh_Model_PostUser::table(), array(
            'post_id' => "int(10) unsigned NOT NULL",
            'user_id' => "int(10) unsigned NOT NULL",
        ), array('primary'=>'(`post_id`, `user_id`)'));
        BDb::ddlTableColumns(DotMesh_Model_PostUser::table(), array(), array(
            'IDX_by_user' => "(`user_id`,`post_id`)",
        ), array(
            'FK_post_user_post' => "FOREIGN KEY (`post_id`) REFERENCES `{$postTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            'FK_post_user_user' => "FOREIGN KEY (`user_id`) REFERENCES `{$userTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
        ));

        BDb::ddlTable(DotMesh_Model_TagSub::table(), array(
            'pub_tag_id' => "int(10) unsigned NOT NULL",
            'sub_user_id' => "int(10) unsigned NOT NULL",
        ), array('primary'=>'(`pub_tag_id`, `sub_user_id`)'));
        BDb::ddlTableColumns(DotMesh_Model_TagSub::table(), array(), array(
            'IDX_by_subscriber' => "(`sub_user_id`)",
        ), array(
            'FK_tag_sub_tag' => "FOREIGN KEY (`pub_tag_id`) REFERENCES `{$tagTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            'FK_tag_sub_user' => "FOREIGN KEY (`sub_user_id`) REFERENCES `{$userTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
        ));

        BDb::ddlTable(DotMesh_Model_UserSub::table(), array(
            'pub_user_id' => "int(10) unsigned NOT NULL",
            'sub_user_id' => "int(10) unsigned NOT NULL",
        ), array('primary'=>'(`pub_user_id`, `sub_user_id`)'));
        BDb::ddlTableColumns(DotMesh_Model_UserSub::table(), array(), array(
            'IDX_by_subscriber' => "(`sub_user_id`,`pub_user_id`)",
        ), array(
            'FK_dm_user_sub_pub' => "FOREIGN KEY (`pub_user_id`) REFERENCES `{$userTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            'FK_dm_user_sub_sub' => "FOREIGN KEY (`sub_user_id`) REFERENCES `{$userTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
        ));

        BDb::ddlTable(DotMesh_Model_UserBlock::table(), array(
            'user_id' => "int(10) unsigned NOT NULL",
            'block_user_id' => "int(10) unsigned NOT NULL",
        ), array('primary'=>'(`user_id`, `block_user_id`)'));
        BDb::ddlTableColumns(DotMesh_Model_UserBlock::table(), array(), array(
            'FK_user_block_block_user' => "(`block_user_id`)",
        ), array(
            'FK_user_block_block_user' => "FOREIGN KEY (`block_user_Id`) REFERENCES `{$userTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            'FK_user_block_user' => "FOREIGN KEY (`user_id`) REFERENCES `{$userTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
        ));

        BDb::ddlFinish();
    }
}