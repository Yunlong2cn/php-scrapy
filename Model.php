<?php

namespace yunlong2cn\spider;

use yii\mongodb\ActiveQuery;


class Model extends ActiveQuery
{
    public static function collectionName()
    {
        return 'wangdai_platforms';
    }
}