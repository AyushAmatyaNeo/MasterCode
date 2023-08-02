<?php
/**
 * Created by PhpStorm.
 * User: ukesh
 * Date: 9/12/16
 * Time: 3:01 PM
 */

namespace Application\Helper;

use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;

class ConstraintHelper
{
    const STATUS = "STATUS";
    const HALFDAY="HALFDAY";

    const CONSTRAINTS = [
        'YN' => [
            'Y' => 'YES',
            'N' => 'NO'
        ],
        'ED'=>[
            'E'=>'ENABLED',
            'D'=>'DISABLED'
        ],
        'FSN'=>[
            'F'=>'FIRST',
            'S'=>'SECOND',
            'N'=>'FULL'
            ]
    ];

    public static function getConstraints($constraintName,$key=null){
        $tempConstraint=[];
        switch ($constraintName){
            case self::STATUS:
                $tempConstraint=self::CONSTRAINTS['YN'];
                break;
            case self::HALFDAY:
                $tempConstraint=self::CONSTRAINTS['FSN'];
                break;
            default:
                throwException("Constraint Name provided not registered");
                break;
        };

        if($key===null){
            return $tempConstraint;
        }else{
            try{
                return $tempConstraint[$key];
            }catch (Exception $exception){
                throwException($exception->getMessage());
            }
        }
        return null;
    }
    
    public static function checkUniqueConstraint(AdapterInterface $adapter,$tableName,array $columnsWidValues,$checkColumnName,$selfId,$requestTbl){
        $tableGateway = new TableGateway($tableName,$adapter);
        
        $uniqueConstraintsError = array();
        $column = key($columnsWidValues);
        $value = "'".$columnsWidValues[$column]."'";
        if($selfId!=0){
            $sql = " AND ".$checkColumnName."!=".$selfId;
        }else{
            $sql="";
        }
        
        if($requestTbl==1){
            $status = "";
        }else{
            $status = " AND STATUS='E'";
        }

        $result =Helper::extractDbData($tableGateway->select([$column."=".$value.$status.$sql]));
        $num = count($result);       
        return $num;
    }
}