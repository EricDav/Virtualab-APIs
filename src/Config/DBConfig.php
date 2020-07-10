<?php
    namespace VirtualLab\Config;
    
    class DBConfig {
        const  dbConfig = array(
            'development' => array(
                'user' => 'root',
                'password' => 'root',
                'host' => 'localhost',
                'database' => 'virtualab',
                'port' => 8889
            ),
    
            'production' => array(
                'user' => '',
                'password' => '',
                'host' => '',
                'database' => '',
                'port' => 3306
            )
        );
    }
?>
