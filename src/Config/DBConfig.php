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
                'user' => 'mavinhub_user1',
                'password' => 'mavinhub123', 
                'host' => 'localhost',
                'database' => 'mavinhub_staging-virtual-lab',
                'port' => 3306
            )
        );
    }
?>
