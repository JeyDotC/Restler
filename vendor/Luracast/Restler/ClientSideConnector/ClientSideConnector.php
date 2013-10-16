<?php

namespace Luracast\Restler\ClientSideConnector;

use Luracast\Restler\Resources;

/**
 * Description of ClientSideConnector
 *
 * @author jguevara
 */
class ClientSideConnector extends Resources {

    /**
     *
     * @var iModelBuilder
     */
    private static $modelBuilder;
    
    /**
     *
     * @var iClientBuilder
     */
    private static $clientBuilder;

    public function index() {
        $resources = $this->getAllResources();
        $models = array();
        foreach ($resources as $resource) {
            $models = array_merge($models, get_object_vars($resource->models));
        }
        
        $this->buildModels($models);
        $this->buildClients($resources);
    }

    protected function buildModels($models) {
        echo self::getModelBuilder()->buildRoot();

        foreach ($models as $model) {
            echo self::getModelBuilder()->build($model);
        }
    }
    
    protected function buildClients($services) {
        echo self::getClientBuilder()->buildRoot();
        
    }

    public static function setModelBuilder(iModelBuilder $builder) {
        self::$modelBuilder = $builder;
    }

    /**
     * 
     * @return iModelBuilder
     */
    protected function getModelBuilder() {
        if (!isset(self::$modelBuilder)) {
            self::$modelBuilder = new Basic\ModelBuilder();
        }

        return self::$modelBuilder;
    }
    
    public static function setClientBuilder(iClientBuilder $builder) {
        self::$clientBuilder = $builder;
    }

    /**
     * 
     * @return iClientBuilder
     */
    protected function getClientBuilder() {
        if (!isset(self::$clientBuilder)) {
            self::$clientBuilder = new Basic\ClientBuilder();
        }

        return self::$clientBuilder;
    }

    private function getAllResources() {
        $result = parent::index();
        $resources = array();

        foreach ($result->apis as $api) {
            $resourceName = str_replace(array("/resources/", ".{format}"), "", $api["path"]);
            $resources[] = parent::get($resourceName);
        }
        
        return $resources;
    }

}
