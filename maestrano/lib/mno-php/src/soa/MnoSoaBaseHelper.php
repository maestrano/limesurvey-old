<?php

/**
 * Helper functions
 *
 * @author root
 */
class MnoSoaBaseHelper 
{
    function getNumeric($str) 
    {
        $result = preg_replace("/[^0-9.]/","",$str);
        if (empty($result) || !is_numeric($result)) return 0;
        return intval($result);
    }

    function array_key_has_value($key, $array)
    {
        return array_key_exists($key, $array) && $array->$key != null;
    }

    function set_if_array_key_has_value(&$target, $key, &$array)
    {
        if ($this->array_key_has_value($key, $array)) {
            $target = $array->$key;
        }
    }

    function push_set_or_delete_value(&$source, $empty_value="")
    {
        if (!empty($source)) { return $source; }
        else { return $empty_value; }
    }

    function pull_set_or_delete_value(&$source, $empty_value="")
    {
        if ($source == null) { MnoSoaLogger::debug('source==null'); return null; }
        else if (!empty($source)) { MnoSoaLogger::debug('!empty(source)'); return $source; }
        else { MnoSoaLogger::debug('empty(source)'); return $empty_value; }
    }

    function isValidIdentifier($id_obj) 
    {
        MnoSoaLogger::debug(__FUNCTION__ . " in is valid identifier");
        return !empty($id_obj) && (!empty($id_obj->_id) || (array_key_exists('_id',$id_obj) && $id_obj->_id == 0)) && 
                array_key_exists('_deleted_flag',$id_obj) && $id_obj->_deleted_flag == 0;
    }

    function isDeletedIdentifier($id_obj) 
    {
        MnoSoaLogger::debug(__FUNCTION__ . " in is deleted identifier");
        return !empty($id_obj) && (!empty($id_obj->_id) || (array_key_exists('_id',$id_obj) && $id_obj->_id == 0)) && 
                array_key_exists('_deleted_flag',$id_obj) && $id_obj->_deleted_flag == 1;
    }
}