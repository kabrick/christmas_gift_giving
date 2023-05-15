<?php

namespace Tests;

use App\Http\Controllers\HomeController;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class GiftAssignmentTest extends TestCase
{

    public function test_that_route_returns_invalid_employee(){
        $return_value = (new \App\Http\Controllers\HomeController)->select_gift('tom');
        $this->assertSame(["Please enter a valid employee ID!", 400], $return_value);
    }

    public function test_that_route_returns_correct_gift(){
        $organise_data_value = (new \App\Http\Controllers\HomeController)->organise_data();
        $select_gift_value = (new \App\Http\Controllers\HomeController)->select_gift('oliver');

        $this->assertSame(["netflix card", 200], $select_gift_value);
    }

    public function test_that_route_returns_no_gift(){
        $organise_data_value = (new \App\Http\Controllers\HomeController)->organise_data();
        $select_gift_value = (new \App\Http\Controllers\HomeController)->select_gift('oliver');
        $second_select_gift_value = (new \App\Http\Controllers\HomeController)->select_gift('oliver');

        $this->assertSame(["You already received netflix card", 200], $second_select_gift_value);
    }
}
