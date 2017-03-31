<?php

namespace yunlong2cn\ps\demo;

use yunlong2cn\ps\Scheduler;
use yunlong2cn\ps\Work;
use yunlong2cn\ps\Helper;


use MongoDB\Client;
use SuperClosure\Serializer;
use yunlong2cn\ps\callback\Base;


class Test
{
    public function index($id = 1)
    {
        $client = new Client('mongodb://192.168.0.136:17017');
        $sites = $client->main->sites->find();

        $scheduler = new Scheduler;

        foreach ($sites as $site) {
            $site = json_decode(json_encode($site), 1);
            $scheduler->newTask(Work::execute($site));
        }
        
        $scheduler->run();
    }
}





