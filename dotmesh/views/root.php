<?php defined('DOTMESH_ROOT_DIR') || die ?>
<!DOCTYPE html>
<html>
    <head>
        <?=$this->hook('head')?>
    </head>
    <body>
        <?=$this->hook('header')?>
        <div class="site-main-container">
	        <?=$this->hook('main')?>
	    </div>
        <?=$this->hook('footer')?>
    </body>
</html>