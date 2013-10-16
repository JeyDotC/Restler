<?php

namespace Luracast\Restler\ClientSideConnector;

/**
 *
 * @author jguevara
 */
interface iClientBuilder {

    public function buildRoot();

    public function build($service);
}
