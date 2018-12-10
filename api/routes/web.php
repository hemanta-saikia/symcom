<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$api=app("Dingo\Api\Routing\Router");

$api->version('v1', ['prefix' => 'v1'], function($api){
	
	/**
	* Oauth routs
	**/
	$api->group(['prefix' => 'oauth'], function($api){
		$api->post('token', '\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken');
	});


	/**
	* Note : To meet the client requirement, made the changes in 
	* Middleware/Authenticate.php page. Now middleware auth guard api and 
	* admin (auth:api, auth:admin) both can access the same routes. 
	* For future possibilities i have created another Middleware/
	* AdminOnlyAuthenticate.php "admin_auth"(given below) for providing 
	* only admin accessing routes (route group sample given below). Same 
	* way we can create user only routs too if needed.     
	**/

	/**
	* Routes that can only be access with Authentication 
	* both auth:api and auth:admin
	* 'middleware' => ['auth:api']] here now we can use any guard
	* auth:api or auth:admin both are able to access same routes.
	**/
	$api->group(['namespace' => 'App\Http\Controllers\V1', 'middleware' => ['auth:api', 'cors']], function($api){
		//User route
		$api->post('user/logout', 'UserController@logout');
		$api->post('user/change-password', 'UserController@changePassword');
		$api->post('user/update-email', 'UserController@updateUserEmail');

		$api->get('user/all', 'UserController@allUser');
		$api->get('user/view', 'UserController@viewUser');
		$api->post('user/add', 'UserController@addUser');
		$api->post('user/update', 'UserController@updateUser');
		$api->post('user/delete', 'UserController@deleteUser');
		//Autor routes
		$api->get('autor/all', 'AutorController@allAutor');
		$api->get('autor/view', 'AutorController@viewAutor');
		$api->post('autor/add', 'AutorController@addAutor');
		$api->post('autor/update', 'AutorController@updateAutor');
		$api->post('autor/delete', 'AutorController@deleteAutor');
		$api->get('autor/titels', 'AutorController@getPreDefinedTitles');
		//Herkunft routes
		$api->get('herkunft/all', 'HerkunftController@allHerkunft');
		$api->get('herkunft/view', 'HerkunftController@viewHerkunft');
		$api->post('herkunft/add', 'HerkunftController@addHerkunft');
		$api->post('herkunft/update', 'HerkunftController@updateHerkunft');
		$api->post('herkunft/delete', 'HerkunftController@deleteHerkunft');
		//Verlage routes
		$api->get('verlage/all', 'VerlageController@allVerlage');
		$api->get('verlage/view', 'VerlageController@viewVerlage');
		$api->post('verlage/add', 'VerlageController@addVerlage');
		$api->post('verlage/update', 'VerlageController@updateVerlage');
		$api->post('verlage/delete', 'VerlageController@deleteVerlage');
		$api->get('verlage/land', 'VerlageController@getPreDefinedLand');
		//Quelle routes
		$api->get('quelle/all', 'QuelleController@allQuelle');
		$api->post('quelle/add', 'QuelleController@addQuelle');
		$api->get('quelle/view', 'QuelleController@viewQuelle');
		$api->post('quelle/update', 'QuelleController@updateQuelle');
		$api->post('quelle/delete', 'QuelleController@deleteQuelle');
		$api->get('quelle/quelle-schemas', 'QuelleController@getPreDefinedQuelleSchemas');
		//Zeitschrift routes 
		$api->get('zeitschrift/all', 'ZeitschriftController@allZeitschrift');
		$api->post('zeitschrift/add', 'ZeitschriftController@addZeitschrift');
		$api->get('zeitschrift/view', 'ZeitschriftController@viewZeitschrift');
		$api->post('zeitschrift/update', 'ZeitschriftController@updateZeitschrift');
		$api->post('zeitschrift/delete', 'ZeitschriftController@deleteZeitschrift');
		//Arznei routes 
		$api->get('arznei/all', 'ArzneiController@allArznei');
		$api->post('arznei/add', 'ArzneiController@addArznei');
		$api->get('arznei/view', 'ArzneiController@viewArznei');
		$api->post('arznei/update', 'ArzneiController@updateArznei');
		$api->post('arznei/delete', 'ArzneiController@deleteArznei');
		//Pruefer routes 
		$api->get('pruefer/all', 'PrueferController@allPruefer');
		$api->post('pruefer/add', 'PrueferController@addPruefer');
		$api->get('pruefer/view', 'PrueferController@viewPruefer');
		$api->post('pruefer/update', 'PrueferController@updatePruefer');
		$api->post('pruefer/delete', 'PrueferController@deletePruefer');
		//Reference routes 
		$api->get('reference/all', 'ReferenceController@allReference');
		$api->post('reference/add', 'ReferenceController@addReference');
		$api->get('reference/view', 'ReferenceController@viewReference');
		$api->post('reference/update', 'ReferenceController@updateReference');
		$api->post('reference/delete', 'ReferenceController@deleteReference');
	});

	/**
	* Routes that can only be access with Authentication auth:admin only
	* here another another middleware is added which is blocking the
	* the request if it is not admin. see Middleware/AdminOnlyAuthenticate.php
	**/
	$api->group(['namespace' => 'App\Http\Controllers\V1', 'middleware' => ['auth:api', 'admin_auth', 'cors']], function($api){
		//Controller route
		$api->get('user/test', 'AutorController@getTokenUser');
		
	});

	/**
	* Routes that can be access without Authentication
	**/
	$api->group(['namespace' => 'App\Http\Controllers\V1'], function($api){
		$api->post('user/signup', 'UserController@signUp');
		$api->post('user/login', 'UserController@login');
		$api->post('user/send-reset-password-link', 'UserController@sendResetPasswordLink');
		$api->post('user/reset-password', 'UserController@resetPassword');
		$api->get('user/test-email', 'UserController@testEmail');
	});
	
});
