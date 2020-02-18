<?php
/**
 * Filter
 * @package admin-user-perm
 * @version 0.0.1
 */

namespace AdminUserPerm\Library;

use LibUserPerm\Model\UserPermRole as UPRole;

class Filter
{
    static function filter(array $cond): ?array{
        $cnd = [];
        if(isset($cond['q']) && $cond['q'])
            $cnd['q'] = (string)$cond['q'];
        $roles = UPRole::get($cnd, 15, 1, ['name'=>true]);
        if(!$roles)
            return [];

        $result = [];
        foreach($roles as $role){
            $result[] = [
                'id'    => (int)$role->id,
                'label' => $role->name,
                'info'  => $role->name,
                'icon'  => NULL
            ];
        }

        return $result;
    }

    static function lastError(): ?string{
        return null;
    }
}