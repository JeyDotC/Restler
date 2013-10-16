<?php

namespace Luracast\Restler\ClientSideConnector\Basic;

use Luracast\Restler\ClientSideConnector\iModelBuilder;

/**
 * Description of ModelBuilder
 *
 * @author jguevara
 */
class ModelBuilder implements iModelBuilder {

    public function build($model) {
        ob_start();
        ?>function <?php echo $model->id ?>(<?php echo implode(", ", array_keys($model->properties)) ?>) {
            var self = this;

        <?php foreach ($model->properties as $name => $metadata): ?>    /**
            * <?php echo $metadata["description"] ?> 
            * @var <?php echo $metadata["type"] ?> 
            */
            this.<?php echo $name ?> = <?php echo $name ?>;
        <?php endforeach; ?>}
        <?php
        return ob_get_clean();
    }

    public function buildRoot() {
        ob_start();
        ?>
        //Utility to make inheritance easier. http://phrogz.net/JS/classes/OOPinJS2.html
        Function.prototype.inheritsFrom = function( parentClassOrObject ) { 
            if ( parentClassOrObject.constructor == Function )  { 
                //Normal Inheritance 
                this.prototype = new parentClassOrObject;
                this.prototype.constructor = this;
                this.prototype.parent = parentClassOrObject.prototype;
            }  else  { 
                //Pure Virtual Inheritance 
                this.prototype = parentClassOrObject;
                this.prototype.constructor = this;
                this.prototype.parent = parentClassOrObject;
            } 
            return this;
        }

        <?php
        return ob_get_clean();
    }

}
