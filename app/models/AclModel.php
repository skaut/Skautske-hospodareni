<?php
class AclModel extends Object {

    const ACL_TABLE = 'acl';
    const PRIVILEGES_TABLE = 'acl_privileges';
    const RESOURCES_TABLE = 'acl_resources';
    const ROLES_TABLE = 'acl_roles';
    private $cache;
    private $cacheParams;

    function __construct() {
        $this->cache = Environment::getCache(__CLASS__);
        $this->cacheParams = array(
            "tags" => "acl",
        );
    }
    public function getRoles() {

        if(isset ($this->cache[__FUNCTION__])) {
            return $this->cache[__FUNCTION__];
        }
        else {
            $result =  dibi::fetchAll('SELECT r1.label as name, r2.label as parent_name
                               FROM ['. self::ROLES_TABLE . '] as r1
                               LEFT JOIN ['. self::ROLES_TABLE . '] as r2 ON (r1.parentId = r2.id)
                              ');
            return $this->cache->save(__FUNCTION__, $result, $this->cacheParams);
        }

    }

    public function getResources() {
        if(isset ($this->cache[__FUNCTION__])) {
            return $this->cache[__FUNCTION__];
        }
        else {
            $result =  dibi::fetchAll('SELECT label as name FROM ['. self::RESOURCES_TABLE . '] ');
            return $this->cache->save(__FUNCTION__, $result, $this->cacheParams);
        }

    }

    public function getPrivileges() {
        if(isset ($this->cache[__FUNCTION__])) {
            return $this->cache[__FUNCTION__];
        }
        else {
            $result =  dibi::fetchAll('SELECT label as name FROM ['. self::PRIVILEGES_TABLE . '] ');
            return $this->cache->save(__FUNCTION__, $result, $this->cacheParams);
        }
    }

    public function getRules() {
        if(isset ($this->cache[__FUNCTION__])) {
            return $this->cache[__FUNCTION__];
        }
        else {
            $result = dibi::fetchAll('
            SELECT
                a.allowed as allowed,
                ro.label as role,
                re.label as resource,
                p.label as privilege
                FROM [' . self::ACL_TABLE . '] as a
                JOIN [' . self::ROLES_TABLE . '] as ro ON (a.role_id = ro.id)
                LEFT JOIN [' . self::RESOURCES_TABLE . '] as re ON (a.resource_id = re.id)
                LEFT JOIN [' . self::PRIVILEGES_TABLE . '] as p ON (a.privilege_id = p.id)
                ORDER BY a.id ASC
        ');
            return $this->cache->save(__FUNCTION__, $result, $this->cacheParams);
        }

    }

    public function removeCache() {
        if(isset ($this->cache)){
            return $this->cache->clean();
            $cache->clean(array(
                    Cache::TAGS => 'acl'
            ));
            return TRUE;
        }
        return FALSE;
    }

    public function getRolesInPairs() {
        if(isset ($this->cache[__FUNCTION__])) {
            return $this->cache[__FUNCTION__];
        }
        else {
            $result = dibi::fetchPairs('SELECT id, label FROM ['. self::ROLES_TABLE . ']');
            return $this->cache->save(__FUNCTION__, $result, $this->cacheParams);
        }
    }

}