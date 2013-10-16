<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace improved;

/**
 * Description of Complex
 *
 * @author jguevara
 */
class Complex {

    /**
     * 
     * @param int $id
     * @url GET {id}
     * 
     * @return improved\Data Description
     */
    function get($id) {
        return new Data($id);
    }

}
