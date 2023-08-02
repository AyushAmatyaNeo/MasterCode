<?php
namespace Application\Helper;

use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;

class DeleteHelper{
    public static function deleteContent(AdapterInterface $adapter,$tableName,$columnName,$id){
        $tableGateway = new TableGateway($tableName,$adapter);
        $result = $tableGateway->update(['STATUS'=>'D'],[$columnName."=".$id]);
        return 1;
    }
}