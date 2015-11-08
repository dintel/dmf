'use strict';

/* App Module */
var app = angular.module('app', [
  'ngRoute',
  'ngStorage',
  'ui.bootstrap',
  'angular-loading-bar',
  'cfp.hotkeys',
  //'app.animations',
  //'app.filters',
  //'app.services'
  'ngToast',
  'jsonResponse',
  'app.controllers'
]);

angular.module("app.controllers", []);

app.config(['$routeProvider', 'cfpLoadingBarProvider',
  function($routeProvider, cfpLoadingBarProvider) {
    cfpLoadingBarProvider.includeSpinner = false;

    $routeProvider.
      when('/', {
        templateUrl: 'partials/dashboard.html',
        controller: 'DashboardController'
      }).
      when('/bootstrap', {
        templateUrl: 'partials/bootstrap.html',
        controller: 'BootstrapController'
      }).
      when('/login', {
        templateUrl: 'partials/login.html',
        controller: 'LoginController'
      }).
      when('/users', {
        templateUrl: 'partials/user/list.html',
        controller: 'UserListController'
      }).
      when('/user/:userId', {
        templateUrl: 'partials/user/detail.html',
        controller: 'UserDetailController'
      }).
      otherwise({
        redirectTo: '/'
      });
  }]);

app.run(function($rootScope, $http, $location, $localStorage){
  $rootScope.isLoggedIn = function () {
    return $http.defaults.headers.common['X-Auth-Token'];
  };

  $rootScope.go = function (path) {
    $location.path(path);
  }

  $rootScope.$on("$routeChangeStart", function(event, next, current) {
    if (!$http.defaults.headers.common['X-Auth-Token']) {
      // check if there is a token in local storage
      if ($localStorage.token) {
        $http.defaults.headers.common['X-Auth-Token'] = $localStorage.token;
        $rootScope.user = $localStorage.user;
        return;
      }
      // no user logged in, show login
      if (next.templateUrl != "partials/login.html") {
        $location.path("/login");
      }
    }
  });
});
