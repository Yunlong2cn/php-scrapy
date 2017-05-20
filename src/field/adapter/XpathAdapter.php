<?php
namespace yunlong2cn\ps\field\adapter;

use XPathSelector\Selector;
use yunlong2cn\ps\field\FieldInterface;
use yunlong2cn\ps\Log;
use yunlong2cn\ps\Helper;



// 'fields' => [
//     [
//         'name' => 'title',
//         'selector' => 
//     ]
// ],

class XpathAdapter implements FieldInterface
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
        if(empty($this->fields)) {// 未设置字段，返回 false
            return false;
        }

        $selector = Selector::loadHTML($this->content);
        
        $returnField = [];

        foreach ($this->fields as $field) {
            if(is_string($field['name'])) {
                $temp = $selector->find($field['selector'])->extract();
                if(isset($field['callback'])) {
                    $temp = $temp ? call_user_func($field['callback'], $temp) : call_user_func($field['callback']);
                }
                if(!empty($field['required']) && empty($temp)) return false;// 如果字段是必须的，但是获取到的数据为空，则跳过这条数据
                if(isset($field['save']) && $field['save'] == false) continue;// 不需要保存
                $returnField[$field['name']] = $temp;
            } elseif(is_array($field['name'])) {
                // todo
            }
        }
        
        return [$returnField];
    }
}