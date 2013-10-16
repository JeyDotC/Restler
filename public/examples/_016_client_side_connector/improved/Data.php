<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace improved;

/**
 * Description of Data
 *
 * @author jguevara
 */
class Data {
    
    public $id;
    public $property1;

    public $property2;

    function __construct($id) {
        $this->id = $id;
    }

}
