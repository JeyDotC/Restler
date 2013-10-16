<?php

namespace Luracast\Restler\ClientSideConnector;

/**
 *
 * @author jguevara
 */
interface iModelBuilder {
    public function buildRoot();
    
    public function build($model);
}
