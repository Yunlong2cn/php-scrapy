<?php
namespace yunlong2cn\ps\field\adapter;

use Flow\JSONPath\JSONPath;
use yunlong2cn\ps\field\FieldInterface;
use yunlong2cn\ps\Log;
use yunlong2cn\ps\Helper;

class JsonAdapter implements FieldInterface
{
    protected $content;
    protected $fields;

    public function __construct($content, $fields)
    {
        $this->content = $content;
        $this->fields = $fields;
    }

    public function execute()
    {
        if(empty($this->fields)) {// 未设置字段，JSONPATH 模式下返回 false
            return false;
        }

        $data = json_decode($this->content, 1);
        $query = new JSONPath($data);
        
        $returnFields = [];
        $value = NULL;
        foreach ($this->fields as $field) {
            if(empty($field['selector'])) {
                if(!is_array($value)) {
                    Log::debug('不能将未设置 selector 的字段放在第一位');exit;
                }
            } else {
                $value = $query->find($field['selector'])->data();
            }

            $tempValue = NULL;
            foreach ($value as $k => $v) {

                $tempValue = empty($field['selector']) ? empty($field['value']) ? '' : $field['value'] : $v;

                if(isset($field['required']) && $field['required'] && empty($tempValue)) {
                    Log::debug('必需的字段 '. $field['name'] .' 为空，跳过此数据');
                    return false;
                }

                if(isset($field['save']) && $field['save'] == false) {
                    Log::debug('不保存的字段 name = ' . $field['name']);
                    continue;
                }

                if(is_null($tempValue)) {
                    Log::debug('字段 name = ' . $field['name'] . ' 为 NULL');
                    continue;
                }

                $tempValue = method_exists($tempValue, 'data') ? $tempValue->data() : $tempValue;

                if(isset($field['callback'])) {
                    $callback = Helper::unserialize($field['callback']);
                    $tempValue = $callback($tempValue);
                }

                $tempValue = empty($tempValue) ? empty($field['value']) ? '' : $field['value'] : $tempValue;

                $returnFields[$k][$field['name']] = $tempValue;
            }
            unset($tempValue);
        }
        unset($value);
        
        return $returnFields;
    }
}