<?php

/**
 * Helper functions
 *
 * @author micobg
 */
class Helper {

    /**
     * Return list of all files in given dir (and sub dirs recursively)
     *
     * @param $dir
     * @return array
     */
    public static function dirToArray($dir) {
        $result = array();

        $ls = scandir($dir);
        foreach ($ls as $value)
            {
            if (!in_array($value, array('.', '..')))
                {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
                {
                    $subDirFiles = self::dirToArray($dir . DIRECTORY_SEPARATOR . $value);
                    foreach ($subDirFiles as $file)
                    {
                        $result[] = $value . DIRECTORY_SEPARATOR . $file;
                    }
                }
                else
                {
                    $result[] = $value;
                }
            }
        }

        return $result;
    }

}
