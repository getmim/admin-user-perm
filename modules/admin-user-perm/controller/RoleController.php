<?php
/**
 * RoleController
 * @package admin-user-perm
 * @version 0.0.1
 */

namespace AdminUserPerm\Controller;

use LibFormatter\Library\Formatter;
use LibForm\Library\Form;
use LibForm\Library\Combiner;
use LibPagination\Library\Paginator;
use LibUserPerm\Model\{
    UserPerm as UPerm,
    UserPermChain as UPChain,
    UserPermRole as UPRole,
    UserPermRoleChain as UPRChain
};
use LibUser\Library\Fetcher;

class RoleController extends \Admin\Controller
{
    private function getParams(string $title): array{
        return [
            '_meta' => [
                'title' => $title,
                'menus' => ['user', 'role']
            ],
            'subtitle' => $title,
            'pages' => null
        ];
    }

    public function editAction(){
        if(!$this->user->isLogin())
            return $this->loginFirst(1);
        if(!$this->can_i->manage_user_role)
            return $this->show404();

        $role = (object)[];

        $all_curr_perms = [];
        $act_curr_perms = [];
        $usr_post_perms = [];
        $usable_perms   = [];
        $usable_perms_id= [];

        $id = $this->req->param->id;
        if($id){
            $role = UPRole::getOne(['id'=>$id]);
            if(!$role)
                return $this->show404();
            $params = $this->getParams('Edit User Role');

            $role_perms = UPRChain::get(['role'=>$id]);
            if($role_perms)
                $all_curr_perms = $act_curr_perms = $usr_post_perms = array_column($role_perms, 'perm');
        }else{
            $params = $this->getParams('Create New User Role');
        }

        if($this->req->method === 'POST')
            $usr_post_perms = $this->req->get('perms') ?? [];

        $form            = new Form('admin.user-role.edit');
        $params['form']  = $form;
        $params['perms'] = [];

        if($this->user->role){
            $perm_by_role = UPRChain::get(['role'=>$this->user->role]);
            if($perm_by_role){
                $perm_by_role = array_column($perm_by_role, 'perm');
                $usable_perms = UPerm::get(['id'=>$perm_by_role]);
            }
        }else{
            $perm_by_user = UPChain::get(['user'=>$this->user->id]);
            if($perm_by_user){
                $perm_by_user = array_column($perm_by_user, 'perm');
                $usable_perms = UPerm::get(['id'=>$perm_by_user]);
            }
        }

        if($usable_perms){
            $usable_perms_id = array_column($usable_perms, 'id');
            foreach($usable_perms as &$perm){
                $perm->active = in_array($perm->id, $usr_post_perms);
                $curr_usable_perms = $perm->id;
            }
            unset($perm);

            $usable_perms = Formatter::formatMany('user-perm', $usable_perms);
            $params['perms'] = group_by_prop($usable_perms, 'group');

            // remove from $act_curr_perms if it's not allowed for current user to handle
            $new_act_curr_perms = [];
            foreach($act_curr_perms as $perm){
                if(in_array($perm, $usable_perms_id))
                    $new_act_curr_perms[] = $perm;
            }
            $act_curr_perms = $new_act_curr_perms;
        }

        if(!($valid = $form->validate($role))/* || !$form->csrfTest('noob') */)
            return $this->resp('user/role/edit', $params);

        $to_add = array_values(array_diff($usr_post_perms, $act_curr_perms));
        $to_rem = array_values(array_diff($act_curr_perms, $usr_post_perms));

        if(isset($valid->perms))
            unset($valid->perms);

        if($id){
            if(!UPRole::set((array)$valid, ['id'=>$id]))
                deb(UPRole::lastError());
        }else{
            $valid->user = $this->user->id;
            if(!($id = UPRole::create((array)$valid)))
                deb(UPRole::lastError());
        }

        if($to_rem)
            UPRChain::remove(['perm'=>$to_rem, 'role'=>$id]);
        if($to_add){
            $cmany = [];
            foreach($to_add as $add){
                $cmany[] = [
                    'user' => $this->user->id,
                    'role' => $id,
                    'perm' => $add
                ];
            }

            UPRChain::createMany($cmany);
        }

        $valid->perms = $usr_post_perms;

        // add the log
        $this->addLog([
            'user'   => $this->user->id,
            'object' => $id,
            'parent' => 0,
            'method' => $id ? 2 : 1,
            'type'   => 'user-perm-role',
            'original' => $role,
            'changes'  => $valid
        ]);

        $next = $this->router->to('adminUserRole');
        $this->res->redirect($next);
    }

    public function indexAction(){
        if(!$this->user->isLogin())
            return $this->loginFirst(1);
        if(!$this->can_i->manage_user_role)
            return $this->show404();

        $cond = $pcond = [];
        if($q = $this->req->getQuery('q'))
            $pcond['q'] = $cond['q'] = $q;

        list($page, $rpp) = $this->req->getPager(25, 50);

        $roles = UPRole::get($cond, $rpp, $page, ['created'=>false]) ?? [];
        if($roles)
            $roles = Formatter::formatMany('user-perm-role', $roles, ['user']);

        $params          = $this->getParams('User Roles');
        $params['roles'] = $roles;
        $params['form']  = new Form('admin.user-role.index');

        $params['form']->validate( (object)$this->req->get() );

        // pagination
        $params['total'] = $total = UPRole::count($cond);
        if($total > $rpp){
            $params['pages'] = new Paginator(
                $this->router->to('adminUserRole'),
                $total,
                $page,
                $rpp,
                10,
                $pcond
            );
        }

        $this->resp('user/role/index', $params);
    }

    public function removeAction(){
        if(!$this->user->isLogin())
            return $this->loginFirst(1);
        if(!$this->can_i->manage_user_role)
            return $this->show404();

        $id    = $this->req->param->id;
        $role  = UPRole::getOne(['id'=>$id]);
        $next  = $this->router->to('adminUserRole');
        $form  = new Form('admin.user-role.index');

        if(!$form->csrfTest('noob'))
            return $this->res->redirect($next);

        // add the log
        $this->addLog([
            'user'   => $this->user->id,
            'object' => $id,
            'parent' => 0,
            'method' => 3,
            'type'   => 'user-perm-role',
            'original' => $role,
            'changes'  => null
        ]);

        UPRole::remove(['id'=>$id]);
        UPRChain::remove(['role'=>$id]);
        Fetcher::set(['role'=>0], ['role'=>$id]);

        $this->res->redirect($next);
    }
}