<?php
/**
 * Created by PhpStorm.
 * User: Antoine
 * Date: 20/09/2016
 * Time: 14:51.
 */

namespace Mgate\PubliBundle\Manager;

/**
 * Class BaseImporter.
 */
class CsvImporter
{

    /**
     * Returns the field of an object
     *
     * @param $object
     * @param $field
     * @param bool $clean
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function readField($object, $field, $clean = true)
    {
        if (!property_exists($object, $field)) {
            throw new \Exception($field . ' do not exists on' . get_class($object));
        }
        if ($clean) {
            return stripslashes(utf8_decode($object->$field));
        } else {
            return $object->$field;
        }
    }

    /**
     * @param $date string representing a date
     *
     * @return \DateTime|null
     */
    protected function stringToDateTime(string $date): ?\Datetime
    {
        if ($date === '0000-00-00 00:00:00') {
            return null;
        }
        return new \DateTime($date);
    }

    /**
     * Converts a french formatted string containing a float to a float.
     *
     * @param $float string representing a float
     *
     * @return float
     */
    protected function floatManager($float)
    {
        return floatval(str_replace(' ', '', str_replace(',', '.', $float)));
    }

    /**
     * slugify a text.
     *
     * @param $string
     *
     * @return string
     */
    protected function normalize($string)
    {
        $table = [
            'Š' => 'S', 'š' => 's', 'Đ' => 'Dj', 'đ' => 'dj', 'Ž' => 'Z', 'ž' => 'z', 'Č' => 'C', 'č' => 'c', 'Ć' => 'C', 'ć' => 'c',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O',
            'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e',
            'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o',
            'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b',
            'ÿ' => 'y', 'Ŕ' => 'R', 'ŕ' => 'r',
        ];

        return strtr($string, $table);
    }
}
