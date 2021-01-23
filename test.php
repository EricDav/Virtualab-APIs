<?php           $groupIds = ['123', '456'];
                $groupIdStr = '(';
                for ($i = 0; $i < sizeof($groupIds); $i++) {
                    if ($i == 0) {
                        $groupIdStr.="'". $groupIds[$i] . "'";
                    } else  {
                        $groupIdStr.="," . "'". $groupIds[$i] . "'";
                    }
                }

                $groupIdStr.=')';


                var_dump($groupIdStr)

?>