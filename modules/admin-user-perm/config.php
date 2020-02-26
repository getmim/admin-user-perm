<?php

return [
    '__name' => 'admin-user-perm',
    '__version' => '0.0.2',
    '__git' => 'git@github.com:getmim/admin-user-perm.git',
    '__license' => 'MIT',
    '__author' => [
        'name' => 'Iqbal Fauzi',
        'email' => 'iqbalfawz@gmail.com',
        'website' => 'http://iqbalfn.com/'
    ],
    '__files' => [
        'modules/admin-user-perm' => ['install','update','remove'],
        'theme/admin/user/role' => ['install','update','remove']
    ],
    '__dependencies' => [
        'required' => [
            [
                'admin' => NULL
            ],
            [
                'lib-form' => NULL
            ],
            [
                'lib-formatter' => NULL
            ],
            [
                'lib-pagination' => NULL
            ],
            [
                'lib-user-perm' => NULL
            ],
            [
                'admin-user' => NULL
            ]
        ],
        'optional' => []
    ],
    'autoload' => [
        'classes' => [
            'AdminUserPerm\\Controller' => [
                'type' => 'file',
                'base' => 'modules/admin-user-perm/controller'
            ],
            'AdminUserPerm\\Library' => [
                'type' => 'file',
                'base' => 'modules/admin-user-perm/library'
            ]
        ],
        'files' => []
    ],
    'routes' => [
        'admin' => [
            'adminUserRole' => [
                'path' => [
                    'value' => '/user/role'
                ],
                'method' => 'GET',
                'handler' => 'AdminUserPerm\\Controller\\Role::index'
            ],
            'adminUserRoleEdit' => [
                'path' => [
                    'value' => '/user/role/(:id)',
                    'params' => [
                        'id' => 'number'
                    ]
                ],
                'method' => 'GET|POST',
                'handler' => 'AdminUserPerm\\Controller\\Role::edit'
            ],
            'adminUserRoleRemove' => [
                'path' => [
                    'value' => '/user/role/(:id)/remove',
                    'params' => [
                        'id' => 'number'
                    ]
                ],
                'method' => 'GET',
                'handler' => 'AdminUserPerm\\Controller\\Role::remove'
            ]
        ]
    ],
    'adminUi' => [
        'sidebarMenu' => [
            'items' => [
                'user' => [
                    'label' => 'User',
                    'icon' => '<i class="fas fa-user"></i>',
                    'priority' => 0,
                    'children' => [
                        'role' => [
                            'label' => 'Role',
                            'icon' => '<i></i>',
                            'route' => ['adminUserRole'],
                            'perms' => 'manage_user_role'
                        ]
                    ]
                ]
            ]
        ]
    ],
    'libForm' => [
        'forms' => [
            'admin.user.account' => [
                'role' => [
                    'label' => 'Role',
                    'type' => 'select',
                    'sl-filter' => [
                        'route' => 'adminObjectFilter',
                        'params' => [],
                        'query' => [
                            'type' => 'user-perm-role'
                        ]
                    ],
                    'rules' => [
                        'exists' => [
                            'model' => 'LibUserPerm\\Model\\UserPermRole',
                            'field' => 'id'
                        ]
                    ],
                    'position' => 'top-left',
                    'c_opt' => ['admin-user-perm', null, 'format', 'active', 'name']
                ]
            ],
            'admin.user-role.index' => [
                'q' => [
                    'label' => 'Search',
                    'type' => 'search',
                    'nolabel' => TRUE,
                    'rules' => []
                ]
            ],
            'admin.user-role.edit' => [
                'name' => [
                    'label' => 'Name',
                    'type' => 'text',
                    'rules' => [
                        'required' => TRUE,
                        'unique' => [
                            'model' => 'LibUserPerm\\Model\\UserPermRole',
                            'field' => 'name',
                            'self' => [
                                'service' => 'req.param.id',
                                'field' => 'id'
                            ]
                        ]
                    ]
                ],
                'about' => [
                    'label' => 'About',
                    'type' => 'textarea',
                    'rules' => []
                ],
                'perms' => [
                    'label' => 'Perms',
                    'type' => 'checkbox',
                    'rules' => [
                        'array' => TRUE
                    ]
                ]
            ]
        ]
    ],
    'admin' => [
        'objectFilter' => [
            'handlers' => [
                'user-perm-role' => 'AdminUserPerm\\Library\\Filter'
            ]
        ]
    ]
];