<?php

// Admin user management routes
$app->get('/admin/users', 'AdminController:usersPage');
$app->get('/admin/api/users', 'AdminController:getAllUsers');
$app->get('/admin/api/users/{id}', 'AdminController:getUserById');
$app->post('/admin/api/users', 'AdminController:createUser');
$app->put('/admin/api/users/{id}', 'AdminController:updateUser');
$app->delete('/admin/api/users/{id}', 'AdminController:deleteUser');
$app->put('/admin/api/users/{id}/status', 'AdminController:toggleUserStatus');
$app->put('/admin/api/users/{id}/role', 'AdminController:updateUserRole');
