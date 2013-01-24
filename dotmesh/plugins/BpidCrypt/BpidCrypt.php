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

class BpidCrypt extends BClass
{
    public static function bootstrap()
    {
        BLayout::i()
            ->addAllViews('views')
            ->addLayout(array(
                'base' => array(
                    array('view', 'head', 'do'=>array(
                        array('js', '{BpidCrypt}/lib/pidcrypt_util_c.js'),
                        array('js', '{BpidCrypt}/lib/pidcrypt_c.js'),
                        array('js', '{BpidCrypt}/lib/md5_c.js'),
                        array('js', '{BpidCrypt}/lib/aes_core_c.js'),
                        array('js', '{BpidCrypt}/lib/aes_cbc_c.js'),
                    )),
                ),
            ));
    }

    public static function migrate()
    {
        BMigrate::install('0.1.0', function() {
            $post = DotMesh_Model_Post::table();
            $postCrypt = BpidCrypt_Model_Post::table();
            BDb::ddlTable($postCrypt, array(
                'id' => 'int unsigned not null',
                'post_id' => 'int unsigned not null',
            ), array('primary'=>'(`id`)'));
            BDb::ddlTableColumns($postCrypt, array(), array(), array(
                'FK_dm_post_crypt_post' => "FOREIGN KEY (`post_id`) REFERENCES `{$post}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ));
        });
    }
}

class BpidCrypt_Model_Post extends BModel
{
    protected static $_table = 'dm_post_crypt';

}