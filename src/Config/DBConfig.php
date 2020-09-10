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
                'user' => 'exclus36_user1',
                'password' => 'Ilovedavid123', 
                'host' => 'localhost',
                'database' => 'exclus36_visual-lab',
                'port' => 3306
            )
        );
    }
?>
