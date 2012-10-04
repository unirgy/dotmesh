<?php return array('modules' => array('BCustomTheme' => array(
    'depends' => array('DotMesh'),
    'bootstrap' => array('callback' => function() {
        BLayout::i()->addAllViews('views')->addLayout(array(
            'base' => array(
                array('view', 'head', 'do'=>array(
                    array('css', '{BCustomTheme}/css/custom.css'),
                    array('js', '{BCustomTheme}/js/custom.js'),
                )),
            ),
        ));
    }),
)));