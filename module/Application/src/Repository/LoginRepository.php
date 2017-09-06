<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Application\Repository;

use Application\Model\Model;
use System\Model\UserSetup;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class LoginRepository implements RepositoryInterface {

    private $adapter;
    private $tableGateway;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(UserSetup::TABLE_NAME, $adapter);
    }

    public function add(Model $model) {
        
    }

    public function delete($id) {
        
    }

    public function edit(Model $model, $id) {
        
    }

    public function fetchAll() {
        
    }

    public function fetchById($id) {
        
    }


    public function checkPasswordExpire($userName) {
        $where = "and USER_NAME='$userName'";
        $sql = "select EMPLOYEE_ID,USER_NAME,ROLE_ID,STATUS,CREATED_DT,MODIFIED_DT,IS_LOCKED,TRUNC(SYSDATE) AS CURRENTDATE,TRUNC(SYSDATE)-CREATED_DT AS CREATED_DAYS,TRUNC(SYSDATE)-MODIFIED_DT AS MODIFIED_DAYS from hris_users where status='E' " . $where;
        $statement = $this->adapter->query($sql);
        $result = $statement->execute()->current();
        return $result;
//        $
    }

    public function getPwdByUserName($userName) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns(array(new Expression('FN_DECRYPT_PASSWORD(PASSWORD) AS PASSWORD')));
        $select->from(UserSetup::TABLE_NAME);
        $select->where(["USER_NAME='" . $userName . "'"]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current()['PASSWORD'];
    }
    
    public function updatePwdByUserName($un,$pwd){
//        $set=["PASSWORD"=>"FN_ENCRYPT_PASSWORD('".$pwd."')"];
        $set=["PASSWORD"=>new Expression("FN_ENCRYPT_PASSWORD('$pwd')"),'MODIFIED_DT'=>new Expression('TRUNC(SYSDATE)')];
        $where=['USER_NAME'=>$un];
        $result=$this->tableGateway->update($set, $where);
    }

}