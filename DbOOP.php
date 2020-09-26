<?php


namespace Neoan3\Apps;


class DbOOP
{
    function __construct($environmentVariables = [])
    {
        if(!empty($environmentVariables)){
            $this->setEnvironment($environmentVariables);
        }
    }

    /**
     * @param $vars
     * @throws DbException
     */
    function setEnvironment($vars)
    {
        Db::setEnvironment($vars);
    }
    function easy($selectString, $conditions = [], $callFunctions = [])
    {
        return Db::easy($selectString, $conditions, $callFunctions);
    }
    function smart($tableOrString, $conditions = null, $callFunctions = null)
    {
        return Db::ask($tableOrString, $conditions, $callFunctions);
    }
}